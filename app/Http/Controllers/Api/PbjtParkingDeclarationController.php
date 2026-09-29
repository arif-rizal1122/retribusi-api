<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlyReport;
use App\Services\PbjtParkingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SPTPD PBJT Parkir — Mobile Warga & Antrean Verifikasi Bapenda.
 *
 * MENGIKUTI SKEMA ARSITEK YANG SUDAH BERJALAN (29 Sep 2026):
 *  - Akun warga  = tabel `taxpayers` (punya password/ikd_id/auth_provider), BUKAN `users`
 *  - SPTPD       = `monthly_reports` (60.336 baris existing), period "M-YYYY"
 *  - Tapping box = `parking_sessions`
 *  - Tagihan     = `bills` (bill_number format BIL-YYYY-NNNNN, wajib terisi)
 *  - Pembayaran  = webhook existing `api/h2h/bank-sultra/callback`
 *
 * Rute (pola existing, bukan /api/v1/* baru):
 *  POST api/parking/declarations
 *  GET  api/parking/declarations
 *  POST api/parking/declarations/fetch-tapbox
 *  GET  api/parking/admin/declarations
 *  POST api/parking/admin/declarations/{id}/approve
 *  POST api/parking/admin/declarations/{id}/reject
 */
class PbjtParkingDeclarationController extends Controller
{
    public function __construct(private readonly PbjtParkingService $svc) {}

    /**
     * POST api/parking/declarations — Warga kirim SPTPD bulanan.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tax_object_id' => ['required', 'integer', 'exists:tax_objects,id'],
            'tax_period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'tax_period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'gross_turnover' => ['required', 'numeric', 'min:0'],
            'other_income' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'device_sync_payload' => ['nullable', 'array'],
        ]);

        $warga = $request->user();
        if (! $warga) {
            return response()->json(['message' => 'Tidak terautentikasi.'], 401);
        }

        // Akun warga = taxpayer. Objek wajib milik dia sendiri.
        $taxpayerId = (int) $warga->id;

        if (! $this->svc->objekMilikWajibPajak((int) $data['tax_object_id'], $taxpayerId)) {
            return response()->json([
                'message' => 'Objek pajak ini bukan milik akun Anda.',
            ], 403);
        }

        $period = $data['tax_period_month'].'-'.$data['tax_period_year'];

        $sudahAda = MonthlyReport::query()
            ->where('tax_object_id', $data['tax_object_id'])
            ->where('period', $period)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($sudahAda) {
            return response()->json([
                'message' => 'Periode ini sudah dilaporkan sebelumnya.',
            ], 422);
        }

        $bulanTerlambat = $this->svc->bulanTerlambat(
            (int) $data['tax_period_month'],
            (int) $data['tax_period_year']
        );

        $hitung = $this->svc->hitung(
            (float) $data['gross_turnover'],
            (float) ($data['other_income'] ?? 0),
            $bulanTerlambat
        );

        $banding = $this->svc->bandingkanOmzet((int) $data['tax_object_id']);
        $anomali = $this->svc->periksaAnomali((float) $data['gross_turnover'], $banding['rata_rata']);

        $payload = $this->svc->susunPayload(
            $data + ['taxpayer_id' => $taxpayerId],
            $hitung
        );

        $laporan = DB::transaction(function () use ($payload, $anomali, $bulanTerlambat) {
            $catatan = (string) ($payload['notes'] ?? '');
            if ($anomali['anomali']) {
                $catatan = trim($catatan.' [ANOMALI: '.$anomali['alasan'].'] rasio='.$anomali['rasio'].'%');
            }
            if ($bulanTerlambat > 0) {
                $catatan = trim($catatan.' [TERLAMBAT '.$bulanTerlambat.' bulan]');
            }
            $payload['notes'] = $catatan ?: null;

            return MonthlyReport::create($payload);
        });

        return response()->json([
            'message' => 'SPTPD parkir berhasil dikirim. Menunggu verifikasi Bapenda.',
            'data' => [
                'id' => $laporan->id,
                'period' => $laporan->period,
                'turnover_amount' => (float) $laporan->turnover_amount,
                'other_income' => (float) $laporan->other_income,
                'dpp_amount' => (float) $laporan->dpp_amount,
                'tax_rate' => (float) $laporan->tax_rate,
                'tax_amount' => (float) $laporan->tax_amount,
                'penalty_months' => (int) $laporan->penalty_months,
                'penalty_amount' => (float) $laporan->penalty_amount,
                'total_bill' => (float) $laporan->total_bill,
                'status' => $laporan->status,
                'anomali' => $anomali,
                'pembanding' => [
                    'rata_rata' => $banding['rata_rata'],
                    'bulan_lalu' => $banding['bulan_lalu'],
                    'tren' => $banding['tren'],
                ],
            ],
        ], 201);
    }

    /**
     * GET api/parking/declarations — Riwayat pelaporan + pembanding omzet.
     */
    public function history(Request $request): JsonResponse
    {
        $warga = $request->user();
        if (! $warga) {
            return response()->json(['message' => 'Tidak terautentikasi.'], 401);
        }

        $daftar = MonthlyReport::query()
            ->where('taxpayer_id', (int) $warga->id)
            ->orderByDesc('id')
            ->limit((int) $request->query('limit', 24))
            ->get([
                'id', 'tax_object_id', 'period', 'turnover_amount', 'other_income',
                'dpp_amount', 'tax_rate', 'tax_amount', 'penalty_months',
                'penalty_amount', 'total_bill', 'status', 'notes', 'created_at',
            ]);

        return response()->json([
            'data' => $daftar,
            'meta' => ['total' => $daftar->count()],
        ]);
    }

    /**
     * POST api/parking/declarations/fetch-tapbox — Tarik omzet dari tapping box.
     */
    public function fetchTapbox(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tax_object_id' => ['required', 'integer', 'exists:tax_objects,id'],
            'tax_period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'tax_period_month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $warga = $request->user();
        if (! $warga || ! $this->svc->objekMilikWajibPajak((int) $data['tax_object_id'], (int) $warga->id)) {
            return response()->json(['message' => 'Objek pajak ini bukan milik akun Anda.'], 403);
        }

        $rekap = $this->svc->rekapTappingBox(
            (int) $data['tax_object_id'],
            (int) $data['tax_period_month'],
            (int) $data['tax_period_year']
        );

        if ($rekap['sesi_count'] === 0) {
            return response()->json([
                'message' => 'Tidak ada data tapping box pada periode ini. Silakan isi manual.',
                'data' => $rekap,
            ], 404);
        }

        return response()->json([
            'message' => 'Data tapping box berhasil ditarik.',
            'data' => $rekap,
        ]);
    }

    /**
     * GET api/parking/admin/declarations — Antrean verifikasi Bapenda.
     */
    public function queue(Request $request): JsonResponse
    {
        $q = MonthlyReport::query()
            ->with(['taxObject:id,nop,name', 'taxpayer:id,name,nik'])
            ->orderBy('id');

        $q->where('status', $request->query('status') ?: 'pending');

        if ($period = $request->query('period')) {
            $q->where('period', $period);
        }

        $rows = $q->limit((int) $request->query('limit', 50))->get();

        return response()->json([
            'data' => $rows,
            'meta' => ['total' => $rows->count()],
        ]);
    }

    /**
     * POST api/parking/admin/declarations/{id}/approve — Setujui & terbitkan tagihan.
     *
     * INSERT ke `bills` mengikuti kolom NOT NULL yang ada: bill_number, amount,
     * status, pax_count, penalty_type, dll. Nomor tagihan format BIL-YYYY-NNNNN
     * diambil dari urutan id berikutnya (pola yang sudah dipakai sistem).
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $laporan = MonthlyReport::find($id);
        if (! $laporan) {
            return response()->json(['message' => 'Laporan tidak ditemukan.'], 404);
        }
        if ($laporan->status === 'approved') {
            return response()->json(['message' => 'Laporan sudah disetujui.'], 422);
        }

        $petugas = $request->user();

        $hasil = DB::transaction(function () use ($laporan, $petugas) {
            $pokok = (float) $laporan->tax_amount;
            $denda = (float) ($laporan->penalty_amount ?? 0);
            $total = (float) ($laporan->total_bill ?: ($pokok + $denda));

            $objek = DB::table('tax_objects')->where('id', $laporan->tax_object_id)->first();

            $billId = DB::table('bills')->insertGetId([
                'taxpayer_id' => $laporan->taxpayer_id,
                'tax_object_id' => $laporan->tax_object_id,
                'opd_id' => $objek->opd_id ?? null,
                'user_id' => $petugas?->id,
                'retribution_type_id' => $objek->retribution_type_id ?? null,
                'retribution_classification_id' => $objek->retribution_classification_id ?? null,
                'bill_number' => 'BIL-'.now()->format('Y').'-'.str_pad((string) (DB::table('bills')->max('id') + 1), 5, '0', STR_PAD_LEFT),
                'amount' => $pokok,
                'penalty_amount' => $denda,
                'pax_count' => 1,
                'status' => 'pending',
                'period' => $laporan->period,
                'metadata' => json_encode([
                    'monthly_report_id' => $laporan->id,
                    'dpp_amount' => (float) $laporan->dpp_amount,
                    'tax_rate' => (float) $laporan->tax_rate,
                    'total_bill' => $total,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $laporan->update([
                'status' => 'approved',
                'validated_at' => now(),
                'validated_by' => $petugas?->id,
            ]);

            return ['bill_id' => $billId, 'amount' => $pokok, 'penalty_amount' => $denda, 'total_bill' => $total];
        });

        return response()->json([
            'message' => 'Laporan disetujui. Tagihan diterbitkan dan siap bayar via SNAP BI Bank Sultra.',
            'data' => $hasil,
        ]);
    }

    /**
     * POST api/parking/admin/declarations/{id}/reject — Tolak dengan catatan wajib.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $laporan = MonthlyReport::find($id);
        if (! $laporan) {
            return response()->json(['message' => 'Laporan tidak ditemukan.'], 404);
        }

        $petugas = $request->user();

        $laporan->update([
            'status' => 'rejected',
            'validated_at' => now(),
            'validated_by' => $petugas?->id,
            'notes' => trim(($laporan->notes ?? '').' [DITOLAK: '.$data['reason'].']'),
        ]);

        return response()->json([
            'message' => 'Laporan ditolak. Wajib pajak dapat memperbaiki dan mengirim ulang.',
        ]);
    }
}
