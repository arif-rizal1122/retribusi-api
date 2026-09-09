<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TpiTransaksiLelang;
use App\Models\TpiRincianFasilitas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TpiTransactionController extends Controller
{
    /**
     * Menampilkan daftar transaksi lelang TPI.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TpiTransaksiLelang::with('rincianFasilitas', 'officer:id,name,email')
            ->orderBy('waktu_transaksi', 'desc');

        if ($request->filled('kode_tpi')) {
            $query->where('kode_tpi', $request->kode_tpi);
        }

        if ($request->filled('status_bayar')) {
            $query->where('status_bayar', $request->status_bayar);
        }

        if ($request->filled('metode_bayar')) {
            $query->where('metode_bayar', $request->metode_bayar);
        }

        if ($request->filled('date')) {
            $query->whereDate('waktu_transaksi', $request->date);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kode_transaksi', 'like', "%{$s}%")
                  ->orWhere('ntpd', 'like', "%{$s}%")
                  ->orWhere('kapal_nama', 'like', "%{$s}%")
                  ->orWhere('bakul_nama', 'like', "%{$s}%");
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar transaksi TPI berhasil dimuat.',
            'data' => $transactions,
        ]);
    }

    /**
     * Input transaksi lelang TPI via Smart Form (Dokumen 172).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kode_tpi' => 'required|string|in:TPI-WM-NEW,TPI-WM-OLD,TPI-BIMA',
            'kapal_nama' => 'required|string|max:150',
            'kapal_gt' => 'nullable|numeric|min:0',
            'nomor_kusuka' => 'nullable|string|max:50',
            'bakul_nama' => 'required|string|max:150',
            'bakul_nik_nib' => 'nullable|string|max:30',
            'volume_lelang_kg' => 'required|numeric|min:0',
            'nilai_transaksi_lelang' => 'required|numeric|min:0',
            'metode_bayar' => 'required|string|in:QRIS,VA_BANK,TUNAI_OFFICER',
            'fasilitas' => 'nullable|array',
            'fasilitas.*.jenis_fasilitas' => 'required|string',
            'fasilitas.*.qty' => 'required|numeric|min:0',
            'fasilitas.*.satuan' => 'required|string',
            'fasilitas.*.tarif_satuan' => 'required|numeric|min:0',
            'fasilitas.*.subtotal' => 'required|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi form TPI gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $data = $validator->validated();

        return DB::transaction(function () use ($data, $user) {
            $nilaiLelang = (float) $data['nilai_transaksi_lelang'];
            $retribusiLelang = round($nilaiLelang * 0.01, 2); // Jasa lelang 1%

            $biayaFasilitas = 0.00;
            $itemsFasilitas = $data['fasilitas'] ?? [];
            foreach ($itemsFasilitas as $f) {
                $biayaFasilitas += (float) $f['subtotal'];
            }

            $totalRetribusi = $biayaFasilitas + $retribusiLelang;
            $isCash = $data['metode_bayar'] === 'TUNAI_OFFICER';

            $transaksi = new TpiTransaksiLelang();
            $transaksi->id = (string) Str::uuid();
            $transaksi->kode_tpi = $data['kode_tpi'];
            $transaksi->waktu_transaksi = now();
            $transaksi->kapal_nama = $data['kapal_nama'];
            $transaksi->kapal_gt = $data['kapal_gt'] ?? 0;
            $transaksi->nomor_kusuka = $data['nomor_kusuka'] ?? null;
            $transaksi->bakul_nama = $data['bakul_nama'];
            $transaksi->bakul_nik_nib = $data['bakul_nik_nib'] ?? null;
            $transaksi->volume_lelang_kg = $data['volume_lelang_kg'];
            $transaksi->nilai_transaksi_lelang = $nilaiLelang;
            $transaksi->biaya_fasilitas = $biayaFasilitas;
            $transaksi->retribusi_lelang_persen = $retribusiLelang;
            $transaksi->total_retribusi = $totalRetribusi;
            $transaksi->metode_bayar = $data['metode_bayar'];
            $transaksi->officer_user_id = $user ? $user->id : null;
            $transaksi->catatan = $data['catatan'] ?? null;

            if ($isCash) {
                $transaksi->status_bayar = 'LUNAS';
                $transaksi->ntpd = sprintf('88%s%02d', now()->format('YmdHis'), mt_rand(10, 99));
                $transaksi->bank_ref = 'CSH-' . strtoupper(Str::random(10));
            } else {
                $transaksi->status_bayar = 'TERUTANG';
                // Simulasi payload QRIS / VA
                $transaksi->bank_ref = 'QRS-' . now()->format('ymd') . strtoupper(Str::random(8));
            }

            $transaksi->save();

            // Simpan rincian fasilitas terpakai
            foreach ($itemsFasilitas as $item) {
                TpiRincianFasilitas::create([
                    'id' => (string) Str::uuid(),
                    'transaksi_lelang_id' => $transaksi->id,
                    'jenis_fasilitas' => $item['jenis_fasilitas'],
                    'qty' => $item['qty'],
                    'satuan' => $item['satuan'],
                    'tarif_satuan' => $item['tarif_satuan'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $transaksi->load('rincianFasilitas');

            return response()->json([
                'success' => true,
                'message' => 'Transaksi pelelangan TPI berhasil dicatat.',
                'data' => [
                    'transaksi' => $transaksi,
                    'qris_payload' => $isCash ? null : [
                        'qr_string' => '00020101021226580014ID.LINKAJA.WWW011893600918000000000052045812530336054' . str_pad((string) (int) $totalRetribusi, 10, '0', STR_PAD_LEFT) . '5802ID5919BAPENDA KOTA BAUBAU6006BAUBAU62070703A016304',
                        'amount' => $totalRetribusi,
                        'expired_at' => now()->addHours(2)->toIso8601String(),
                    ],
                ],
            ], 201);
        });
    }

    /**
     * Menampilkan detail transaksi pelelangan TPI.
     */
    public function show(string $id): JsonResponse
    {
        $transaksi = TpiTransaksiLelang::with('rincianFasilitas', 'officer:id,name,email')
            ->find($id);

        if (!$transaksi) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi TPI tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaksi,
        ]);
    }

    /**
     * Konfirmasi pelunasan transaksi (QRIS / VA / Kasir).
     */
    public function settlePayment(Request $request, string $id): JsonResponse
    {
        $transaksi = TpiTransaksiLelang::with('rincianFasilitas')->find($id);

        if (!$transaksi) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi TPI tidak ditemukan.',
            ], 404);
        }

        if ($transaksi->status_bayar === 'LUNAS') {
            return response()->json([
                'success' => true,
                'message' => 'Transaksi sudah berstatus LUNAS.',
                'data' => $transaksi,
            ]);
        }

        $transaksi->status_bayar = 'LUNAS';
        if (empty($transaksi->ntpd)) {
            $transaksi->ntpd = sprintf('88%s%02d', now()->format('YmdHis'), mt_rand(10, 99));
        }
        if ($request->filled('bank_ref')) {
            $transaksi->bank_ref = $request->bank_ref;
        }
        $transaksi->save();

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran retribusi TPI berhasil dikonfirmasi dan NTPD diterbitkan.',
            'data' => $transaksi,
        ]);
    }

    /**
     * Ringkasan statistik harian operasional TPI untuk dashboard & mobile.
     */
    public function dailySummary(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());

        $query = TpiTransaksiLelang::whereDate('waktu_transaksi', $date);

        if ($request->filled('kode_tpi')) {
            $query->where('kode_tpi', $request->kode_tpi);
        }

        $totalTransaksi = (clone $query)->count();
        $totalTonaseKg = (clone $query)->sum('volume_lelang_kg');
        $totalNilaiLelang = (clone $query)->sum('nilai_transaksi_lelang');
        $totalRetribusi = (clone $query)->where('status_bayar', 'LUNAS')->sum('total_retribusi');
        $totalFasilitas = (clone $query)->where('status_bayar', 'LUNAS')->sum('biaya_fasilitas');
        $totalLelang1Persen = (clone $query)->where('status_bayar', 'LUNAS')->sum('retribusi_lelang_persen');

        $byPaymentMethod = (clone $query)->where('status_bayar', 'LUNAS')
            ->select('metode_bayar', DB::raw('count(*) as count'), DB::raw('sum(total_retribusi) as total'))
            ->groupBy('metode_bayar')
            ->get();

        $byTpi = (clone $query)
            ->select('kode_tpi', DB::raw('count(*) as count'), DB::raw('sum(volume_lelang_kg) as tonase'), DB::raw('sum(total_retribusi) as pendapatan'))
            ->groupBy('kode_tpi')
            ->get();

        return response()->json([
            'success' => true,
            'date' => $date,
            'summary' => [
                'total_transaksi' => $totalTransaksi,
                'total_tonase_kg' => (float) $totalTonaseKg,
                'total_nilai_lelang' => (float) $totalNilaiLelang,
                'total_retribusi_lunas' => (float) $totalRetribusi,
                'total_biaya_fasilitas' => (float) $totalFasilitas,
                'total_jasa_lelang_1persen' => (float) $totalLelang1Persen,
                'breakdown_metode_bayar' => $byPaymentMethod,
                'breakdown_tpi' => $byTpi,
            ],
        ]);
    }

    /**
     * Validasi publik keabsahan struk NTPD TPI (Scan QR Code BSrE).
     */
    public function verifyPublic(string $ntpd): JsonResponse
    {
        $transaksi = TpiTransaksiLelang::with('rincianFasilitas')
            ->where('ntpd', $ntpd)
            ->first();

        if (!$transaksi) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor Transaksi Penerimaan Daerah (NTPD) tidak valid atau tidak tercatat di Kas Daerah.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'NTPD VALID — Tercatat sah pada Kas Daerah Pemerintah Kota Baubau.',
            'data' => [
                'ntpd' => $transaksi->ntpd,
                'kode_transaksi' => $transaksi->kode_transaksi,
                'kode_tpi' => $transaksi->kode_tpi,
                'waktu_transaksi' => $transaksi->waktu_transaksi->toIso8601String(),
                'kapal_nama' => $transaksi->kapal_nama,
                'kapal_gt' => $transaksi->kapal_gt,
                'bakul_nama' => $transaksi->bakul_nama,
                'volume_lelang_kg' => $transaksi->volume_lelang_kg,
                'nilai_transaksi_lelang' => $transaksi->nilai_transaksi_lelang,
                'total_retribusi' => $transaksi->total_retribusi,
                'status_bayar' => $transaksi->status_bayar,
                'metode_bayar' => $transaksi->metode_bayar,
                'rincian' => $transaksi->rincianFasilitas,
            ],
        ]);
    }

    /**
     * Cross-reconciliation intelijen pasokan ikan TPI vs pelaporan PBJT restoran seafood (Bab V Blueprint).
     */
    public function crossPbjtStats(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);
        $startDate = now()->subDays($days)->toDateString();

        $totalIkanTpiKg = TpiTransaksiLelang::whereDate('waktu_transaksi', '>=', $startDate)
            ->sum('volume_lelang_kg');

        // Simulasi benchmark intelijen rantai pasok ikan Kota Baubau
        // 1 Kg Ikan Segar ~ 2.5 porsi matang @ Rp 45.000 rata-rata di restoran seafood
        $rasioPorsiPerKg = 2.5;
        $hargaRataRataPorsi = 45000;
        $estimasiOmzetIndustri = $totalIkanTpiKg * $rasioPorsiPerKg * $hargaRataRataPorsi;
        $potensiPbjt10 = $estimasiOmzetIndustri * 0.10;

        // Daftar restoran seafood simulasi sampling dengan Red Channel Alert
        $restoranSampling = [
            [
                'id' => 'RESTO-01',
                'nama' => 'Rumah Makan Ikan Bakar Kotamara',
                'pembelian_ikan_tpi_kg' => 1250,
                'estimasi_omzet_riil' => 1250 * $rasioPorsiPerKg * $hargaRataRataPorsi,
                'omzet_lapor_sptpd' => 52000000,
                'deviasi_persen' => round((( (1250 * $rasioPorsiPerKg * $hargaRataRataPorsi) - 52000000 ) / (1250 * $rasioPorsiPerKg * $hargaRataRataPorsi)) * 100, 1),
                'alert_level' => 'RED_CHANNEL',
                'catatan' => 'Deviasi omzet > 30% dari pasokan bahan baku TPI. Direkomendasikan uji petik Bapenda.',
            ],
            [
                'id' => 'RESTO-02',
                'nama' => 'Restoran Seafood Pantai Kamali',
                'pembelian_ikan_tpi_kg' => 2100,
                'estimasi_omzet_riil' => 2100 * $rasioPorsiPerKg * $hargaRataRataPorsi,
                'omzet_lapor_sptpd' => 215000000,
                'deviasi_persen' => round(abs((( (2100 * $rasioPorsiPerKg * $hargaRataRataPorsi) - 215000000 ) / (2100 * $rasioPorsiPerKg * $hargaRataRataPorsi)) * 100), 1),
                'alert_level' => 'GREEN_NORMAL',
                'catatan' => 'Pelaporan wajar sesuai tren pasokan TPI.',
            ],
            [
                'id' => 'RESTO-03',
                'nama' => 'Warung Ikan Bakar Palabusa',
                'pembelian_ikan_tpi_kg' => 840,
                'estimasi_omzet_riil' => 840 * $rasioPorsiPerKg * $hargaRataRataPorsi,
                'omzet_lapor_sptpd' => 38000000,
                'deviasi_persen' => round((( (840 * $rasioPorsiPerKg * $hargaRataRataPorsi) - 38000000 ) / (840 * $rasioPorsiPerKg * $hargaRataRataPorsi)) * 100, 1),
                'alert_level' => 'YELLOW_WARNING',
                'catatan' => 'Deviasi omzet 25-30%. Masuk daftar pantau tim verifikasi Bapenda.',
            ],
        ];

        return response()->json([
            'success' => true,
            'periode_hari' => $days,
            'summary_hulu' => [
                'total_volume_ikan_lelang_kg' => (float) $totalIkanTpiKg,
                'estimasi_nilai_omzet_hilir_rp' => $estimasiOmzetIndustri,
                'estimasi_potensi_pbjt10_rp' => $potensiPbjt10,
            ],
            'restoran_audited' => $restoranSampling,
        ]);
    }
}
