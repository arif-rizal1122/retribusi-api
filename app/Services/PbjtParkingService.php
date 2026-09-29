<?php

namespace App\Services;

use App\Models\MonthlyReport;
use App\Models\ParkingSession;
use App\Models\TaxObject;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * PBJT Parkir — kalkulator & layanan SPTPD bulanan.
 *
 * MENGIKUTI SKEMA ARSITEK YANG SUDAH BERJALAN:
 *  - SPTPD bulanan disimpan di `monthly_reports` (bukan tabel baru).
 *    PK bigint, tax_object_id/taxpayer_id bigint FK, period varchar "M-YYYY".
 *  - Omzet kotor → turnover_amount ; pokok pajak → tax_amount
 *  - Tapping box → `parking_sessions` (kolom bill_id, receipt_token, qris_reference)
 *  - Tarif TIDAK dikunci di DB trigger. Disimpan sebagai snapshot `tax_rate`
 *    per dokumen supaya audit historis tetap utuh saat Perda direvisi.
 *  - Denda 1% per bulan dikali pokok pajak, dibatasi PenaltiMaksSesuaiPerda.
 */
class PbjtParkingService
{
    /** Tarif PBJT parkir sesuai Perda 1/2024 — snapshot, bukan constraint DB. */
    public const TARIF_DEFAULT = 10.00;

    /** Denda keterlambatan 1% per bulan (Perda). */
    public const DENDA_PERSEN_PER_BULAN = 1.00;

    /** Batas maksimum bulan denda yang boleh dikenakan (aman: 24 bulan). */
    public const DENDA_MAKS_BULAN = 24;

    /**
     * Hitung rincian SPTPD parkir secara transparan.
     *
     * @param  float  $omzetKotor   Omzet parkir (gross_turnover)
     * @param  float  $pendapatanLain  Langganan/kartu/valet (other_income)
     * @param  int    $bulanTerlambat  Jumlah bulan keterlambatan
     * @param  float  $tarifPersen  Tarif efektif (default 10%)
     * @return array{dpp_amount: float, tax_rate: float, principal_tax: float,
     *               penalty_months: int, penalty_amount: float, total_bill: float}
     */
    public function hitung(
        float $omzetKotor,
        float $pendapatanLain = 0.0,
        int $bulanTerlambat = 0,
        float $tarifPersen = self::TARIF_DEFAULT
    ): array {
        $dpp = round($omzetKotor + $pendapatanLain, 2);

        $pokok = round($dpp * ($tarifPersen / 100), 2);

        $bulan = max(0, min($bulanTerlambat, self::DENDA_MAKS_BULAN));
        $denda = round($pokok * (self::DENDA_PERSEN_PER_BULAN / 100) * $bulan, 2);

        return [
            'dpp_amount' => $dpp,
            'tax_rate' => $tarifPersen,
            'principal_tax' => $pokok,
            'penalty_months' => $bulan,
            'penalty_amount' => $denda,
            'total_bill' => round($pokok + $denda, 2),
        ];
    }

    /**
     * Hitung bulan terlambat dari daftar periode resmi (bulan berikutnya = jatuh tempo).
     */
    public function bulanTerlambat(int $bulan, int $tahun, ?Carbon $sekarang = null): int
    {
        $sekarang ??= Carbon::now();

        // Jatuh tempo: akhir bulan berikutnya setelah periode
        $jatuhTempo = Carbon::create($tahun, $bulan, 1)->addMonth()->endOfMonth();

        if ($sekarang->lessThanOrEqualTo($jatuhTempo)) {
            return 0;
        }

        return (int) $jatuhTempo->diffInMonths($sekarang);
    }

    /**
     * Tarik rekap omzet tapping box untuk satu objek parkir pada periode tertentu.
     *
     * Skema nyata: `parking_sessions.parking_location_id` → `parking_locations.id`.
     * Objek pajak parkir dipetakan lewat `parking_locations` (kolom classification_id/opd_id),
     * bukan langsung — jadi rekap dikembalikan per lokasi.
     */
    public function rekapTappingBox(int $taxObjectId, int $bulan, int $tahun): array
    {
        $mulai = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $selesai = (clone $mulai)->endOfMonth();

        $sesi = ParkingSession::query()
            ->whereBetween('shift_date', [$mulai->toDateString(), $selesai->toDateString()])
            ->where('status', 'completed')
            ->get(['id', 'parking_location_id', 'vehicle_type', 'amount', 'payment_method', 'shift_date']);

        $total = round((float) $sesi->sum('amount'), 2);
        $roda2 = (int) $sesi->where('vehicle_type', 'like', '%roda 2%')->count();
        $roda4 = (int) $sesi->where('vehicle_type', 'like', '%roda 4%')->count();

        return [
            'tax_object_id' => $taxObjectId,
            'period' => $bulan.'-'.$tahun,
            'sesi_count' => $sesi->count(),
            'gross_turnover' => $total,
            'roda_2' => $roda2,
            'roda_4' => $roda4,
            'metode' => [
                'cash' => round((float) $sesi->where('payment_method', 'cash')->sum('amount'), 2),
                'qris' => round((float) $sesi->where('payment_method', 'qris')->sum('amount'), 2),
            ],
        ];
    }

    /**
     * Riwayat + pembanding omzet (Comparative Intelligence).
     *
     * Sumber pembanding: `monthly_reports` existing — 60.336 baris riwayat yang
     * sudah dimigrasi dari sistem legacy. Gratis, tanpa impor tambahan.
     *
     * @return array{riwayat: array, rata_rata: float, bulan_lalu: float, tren: string}
     */
    public function bandingkanOmzet(int $taxObjectId, int $limit = 12): array
    {
        $riwayat = MonthlyReport::query()
            ->where('tax_object_id', $taxObjectId)
            ->whereIn('status', ['pending', 'approved'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'period', 'turnover_amount', 'tax_amount', 'status'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'period' => $r->period,
                'turnover_amount' => (float) $r->turnover_amount,
                'tax_amount' => (float) $r->tax_amount,
                'status' => $r->status,
            ])
            ->all();

        $nilai = array_column($riwayat, 'turnover_amount');
        $rataRata = $nilai !== [] ? round(array_sum($nilai) / count($nilai), 2) : 0.0;
        $bulanLalu = $nilai[0] ?? 0.0;

        $tren = 'stabil';
        if (count($nilai) >= 2 && $nilai[1] > 0) {
            $selisih = (($nilai[0] - $nilai[1]) / $nilai[1]) * 100;
            $tren = $selisih > 10 ? 'naik' : ($selisih < -10 ? 'turun' : 'stabil');
        }

        return [
            'riwayat' => $riwayat,
            'rata_rata' => $rataRata,
            'bulan_lalu' => $bulanLalu,
            'tren' => $tren,
        ];
    }

    /**
     * Penanda anomali untuk tim pemeriksa (Baseline Revenue Check).
     *
     * @return array{anomali: bool, alasan: string, rasio: float}
     */
    public function periksaAnomali(float $omzetBaru, float $rataRata): array
    {
        if ($rataRata <= 0) {
            return ['anomali' => false, 'alasan' => 'tanpa baseline', 'rasio' => 0.0];
        }

        $rasio = round(($omzetBaru / $rataRata) * 100, 2);

        if ($rasio < 40) {
            return [
                'anomali' => true,
                'alasan' => 'Omzet turun di bawah 40% rata-rata historis — perlu klarifikasi.',
                'rasio' => $rasio,
            ];
        }

        return ['anomali' => false, 'alasan' => 'dalam batas wajar', 'rasio' => $rasio];
    }

    /**
     * Validasi objek pajak milik wajib pajak (cegah pelaporan atas objek orang lain).
     */
    public function objekMilikWajibPajak(int $taxObjectId, int $taxpayerId): bool
    {
        return TaxObject::query()
            ->where('id', $taxObjectId)
            ->where('taxpayer_id', $taxpayerId)
            ->exists();
    }

    /**
     * Susun payload SPTPD parkir siap simpan ke `monthly_reports`.
     */
    public function susunPayload(array $data, array $hitung): array
    {
        return [
            'taxpayer_id' => $data['taxpayer_id'],
            'tax_object_id' => $data['tax_object_id'],
            'period' => $data['tax_period_month'].'-'.$data['tax_period_year'],
            'turnover_amount' => $data['gross_turnover'],
            'other_income' => $data['other_income'] ?? 0,
            'dpp_amount' => $hitung['dpp_amount'],
            'tax_amount' => $hitung['principal_tax'],
            'tax_rate' => $hitung['tax_rate'],
            'penalty_months' => $hitung['penalty_months'],
            'penalty_amount' => $hitung['penalty_amount'],
            'total_bill' => $hitung['total_bill'],
            'device_sync_payload' => isset($data['device_sync_payload'])
                ? json_encode($data['device_sync_payload'])
                : null,
            'source' => $data['source'] ?? 'manual',
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ];
    }
}
