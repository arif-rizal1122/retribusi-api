<?php

namespace App\Http\Controllers;

use App\Models\TaxpayerPbbObject;
use App\Models\TransactionPbb;
use App\Models\Bill;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestItem;
use App\Models\TaxObject;
use App\Services\BillCreationService;
use App\Services\PbbBapendaService;
use App\Services\Payment\Snap\SnapBrivaReadiness;
use App\Services\Payment\Snap\BankSultraCitizenVaReadiness;
use App\Support\SqlDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PbbBapendaController extends Controller
{
    protected PbbBapendaService $bapendaService;

    public function __construct(PbbBapendaService $bapendaService)
    {
        $this->bapendaService = $bapendaService;
    }

    // ─── Inquiry (Cek Tagihan) ──────────────────────────────────────────

    /**
     * Cek tagihan PBB langsung ke server Bapenda.
     * Bisa digunakan oleh mobile, petugas, dan admin.
     */
    public function inquiry(Request $request)
    {
        $request->validate([
            'nop' => 'required|string|size:18',
            'tahun' => 'required|string|size:4',
        ]);

        try {
            $result = $this->bapendaService->inquiryWithFallback(
                trim($request->nop),
                trim($request->tahun)
            );

            if (($result['status'] ?? 0) === 200) {
                // Skema 2026 mengembalikan data di dalam key 'data'
                $data = $result['data'] ?? [];

                return response()->json([
                    'status' => 'success',
                    'message' => 'Data tagihan ditemukan',
                    'data' => [
                        'nop' => $request->nop,
                        'tahun' => $data['tahun'] ?? $result['tahun'] ?? $request->tahun,
                        'nama_wp' => $data['nama_wp'] ?? '-',
                        'alamat_wp' => $data['alamat_wp'] ?? '-',
                        'kelurahan' => $data['kelurahan'] ?? '-',
                        'kota' => $data['kota'] ?? '-',
                        'pbb_pokok' => (float) ($data['pbb_pokok'] ?? 0),
                        'denda' => (float) ($data['denda'] ?? 0),
                        'total_harus_dibayar' => (float) ($data['total_harus_dibayar'] ?? 0),
                        'status_bayar' => $data['status_bayar'] ?? '-',
                        'source' => $result['source'] ?? 'simpbb',
                        'fallback_used' => $result['fallback_used'] ?? false,
                        'data_source' => $result['data_source'] ?? null,
                        'luas_bumi' => (float) ($data['luas_bumi'] ?? 0),
                        'luas_bangunan' => (float) ($data['luas_bangunan'] ?? 0),
                        'njop_bumi' => (float) ($data['njop_bumi'] ?? 0),
                        'njop_bangunan' => (float) ($data['njop_bangunan'] ?? 0),
                        'njop_total' => (float) ($data['njop_total'] ?? 0),
                        'njop_tkp' => (float) ($data['njop_tkp'] ?? 0),
                        'njop_kena_pajak' => (float) ($data['njop_kena_pajak'] ?? 0),
                        'tarif' => (float) ($data['tarif'] ?? 0),
                        'kelas_bumi' => $data['kelas_bumi'] ?? null,
                        'kelas_bangunan' => $data['kelas_bangunan'] ?? null,
                        // Kelengkapan verifikasi Bapenda — sebelumnya difilter habis di sini
                        // walau service sudah menyediakannya, sehingga verifikator tidak
                        // bisa menilai kewajaran NJOP objek.
                        'kecamatan' => $data['kecamatan'] ?? null,
                        'njop_bumi_per_m2' => (float) ($data['njop_bumi_per_m2'] ?? 0),
                        'njop_bangunan_per_m2' => (float) ($data['njop_bangunan_per_m2'] ?? 0),
                        'jatuh_tempo' => $data['jatuh_tempo'] ?? null,
                        'hari_terlambat' => (int) ($data['hari_terlambat'] ?? 0),
                    ],
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['msg'] ?? 'Data tidak ditemukan',
            ], ($result['status'] ?? 0) === 503 ? 503 : 404);

        } catch (\Exception $e) {
            Log::error('PBB Inquiry Service Error', [
                'nop' => $request->nop,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Layanan Bapenda sedang tidak tersedia. (Error: '.$e->getMessage().')',
            ], 503);
        }
    }

    // ─── Link NOP (Klaim NOP oleh Wajib Pajak) ─────────────────────────

    /**
     * Wajib pajak menautkan NOP ke akun mereka.
     * Sistem otomatis inquiry ke Bapenda untuk validasi.
     */
    public function linkNop(Request $request)
    {
        $request->validate([
            'nop' => 'required|string|size:18',
            'tahun' => 'nullable|string|size:4',
            'description' => 'nullable|string|max:100',
        ]);

        $taxpayer = Auth::guard('sanctum')->user();
        if (! $taxpayer || ! ($taxpayer instanceof \App\Models\Taxpayer)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // Cek duplikat
        $exists = TaxpayerPbbObject::where('taxpayer_id', $taxpayer->id)
            ->where('nop', $request->nop)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'NOP ini sudah terdaftar di akun Anda.',
            ], 422);
        }

        // Validasi ke Bapenda (dengan fallback SPPT lokal)
        $tahun = $request->tahun ?? date('Y');
        try {
            $result = $this->bapendaService->inquiryWithFallback($request->nop, $tahun);

            if (($result['status'] ?? 0) !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'NOP tidak ditemukan di database Bapenda.',
                ], 404);
            }

            $fallbackUsed = $result['fallback_used'] ?? false;
            $notes = $request->description;
            if ($fallbackUsed) {
                $notes = trim(($notes ? $notes.' | ' : '').'Sumber: SPPT lokal (fallback H2H)');
            }

            $pbbObject = TaxpayerPbbObject::create([
                'taxpayer_id' => $taxpayer->id,
                'nop' => $request->nop,
                'name_on_sppt' => $result['nama_wp'] ?? null,
                'address_on_sppt' => $result['alamat_wp'] ?? null,
                'kelurahan' => $result['kelurahan'] ?? null,
                'kota' => $result['kota'] ?? null,
                'is_verified' => true,
                'notes' => $notes,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'NOP berhasil ditautkan ke akun Anda.',
                'data' => $pbbObject,
                'source' => $result['source'] ?? 'simpbb',
                'fallback_used' => $fallbackUsed,
            ]);

        } catch (\Exception $e) {
            Log::error('PBB Link NOP Error', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memvalidasi NOP. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Hapus NOP dari akun wajib pajak.
     */
    public function unlinkNop(Request $request, $id)
    {
        $taxpayer = Auth::guard('sanctum')->user();
        if (! $taxpayer || ! ($taxpayer instanceof \App\Models\Taxpayer)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $pbbObject = TaxpayerPbbObject::where('id', $id)
            ->where('taxpayer_id', $taxpayer->id)
            ->first();

        if (! $pbbObject) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $pbbObject->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'NOP berhasil dihapus dari akun Anda.',
        ]);
    }

    // ─── My PBB Objects (Daftar NOP milik wajib pajak) ──────────────────

    /**
     * Menampilkan semua NOP yang ditautkan oleh wajib pajak
     * beserta tagihan real-time dari Bapenda.
     */
    public function myObjects(Request $request)
    {
        $taxpayer = Auth::guard('sanctum')->user();
        if (! $taxpayer || ! ($taxpayer instanceof \App\Models\Taxpayer)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $objects = TaxpayerPbbObject::where('taxpayer_id', $taxpayer->id)->get();
        $tahun = $request->tahun ?? date('Y');
        $results = [];

        foreach ($objects as $obj) {
            $tagihan = null;
            $source = 'simpbb';
            $fallbackUsed = false;
            try {
                $inquiry = $this->bapendaService->inquiryWithFallback($obj->nop, $tahun);
                if (($inquiry['status'] ?? 0) === 200) {
                    $tagihan = [
                        'pbb_pokok' => (float) ($inquiry['pbb_pokok'] ?? 0),
                        'denda' => (float) ($inquiry['denda'] ?? 0),
                        'total_harus_dibayar' => (float) ($inquiry['total_harus_dibayar'] ?? 0),
                        'status_bayar' => $inquiry['status_bayar'] ?? '-',
                    ];
                    $source = $inquiry['source'] ?? 'simpbb';
                    $fallbackUsed = $inquiry['fallback_used'] ?? false;
                }
            } catch (\Exception $e) {
                Log::warning('PBB Inquiry for my-objects failed', [
                    'nop' => $obj->nop, 'error' => $e->getMessage(),
                ]);
            }

            $results[] = [
                'id' => $obj->id,
                'nop' => $obj->nop,
                'name_on_sppt' => $obj->name_on_sppt,
                'address_on_sppt' => $obj->address_on_sppt,
                'kelurahan' => $obj->kelurahan,
                'kota' => $obj->kota,
                'tahun' => $tahun,
                'tagihan' => $tagihan,
                'source' => $source,
                'fallback_used' => $fallbackUsed,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $results,
        ]);
    }

    // ─── Payment (Pembayaran PBB) ───────────────────────────────────────

    /**
     * Proses pembayaran PBB melalui API Bapenda.
     * Bisa dipanggil oleh warga (mobile) atau petugas (via auth user).
     */
    public function pay(Request $request)
    {
        $request->validate([
            'nop' => 'required|string|size:18',
            'tahun' => 'required|string|size:4',
        ]);

        // Tentukan user & taxpayer
        $user = Auth::guard('sanctum')->user();
        $userId = null;
        $taxpayerId = null;

        if ($user instanceof \App\Models\User) {
            $userId = $user->id;
        } elseif ($user instanceof \App\Models\Taxpayer) {
            $taxpayerId = $user->id;
        }

        // Inquiry dulu untuk mendapatkan data lengkap
        try {
            $inquiry = $this->bapendaService->inquiry($request->nop, $request->tahun);

            if (($inquiry['status'] ?? 0) !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => $inquiry['msg'] ?? 'Data tagihan tidak ditemukan.',
                ], 404);
            }

            // Cek apakah sudah lunas
            $statusBayar = strtoupper($inquiry['status_bayar'] ?? '');
            if (str_contains($statusBayar, 'LUNAS') || str_contains($statusBayar, 'SDH BAYAR')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tagihan ini sudah lunas.',
                ], 422);
            }

            // Proses pembayaran ke Bapenda
            $totalTagihan = (float) ($inquiry['data']['total_harus_dibayar'] ?? 0);
            $payResult = $this->bapendaService->payment(
                $request->nop,
                $request->tahun,
                $totalTagihan,
                'Pembayaran PBB via Mobile'
            );

            $paymentStatus = ($payResult['status'] ?? 0) === 200 ? 'success' : 'failed';

            // Simpan transaksi
            $transaction = TransactionPbb::create([
                'user_id' => $userId,
                'taxpayer_id' => $taxpayerId,
                'nop' => $request->nop,
                'tahun' => $request->tahun,
                'amount' => (float) ($inquiry['pbb_pokok'] ?? 0),
                'denda' => (float) ($inquiry['denda'] ?? 0),
                'total_bayar' => (float) ($inquiry['total_harus_dibayar'] ?? 0),
                'ntpd' => $payResult['ntpd'] ?? null,
                'payment_status' => $paymentStatus,
                'wp_name' => $inquiry['nama_wp'] ?? null,
                'wp_address' => $inquiry['alamat_wp'] ?? null,
                'kelurahan' => $inquiry['kelurahan'] ?? null,
                'kota' => $inquiry['kota'] ?? null,
                'api_response' => $payResult,
            ]);

            if ($paymentStatus === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran PBB berhasil.',
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'ntpd' => $transaction->ntpd,
                        'nop' => $transaction->nop,
                        'tahun' => $transaction->tahun,
                        'total_bayar' => $transaction->total_bayar,
                        'wp_name' => $transaction->wp_name,
                        'paid_at' => $transaction->created_at,
                    ],
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $payResult['msg'] ?? 'Pembayaran gagal.',
            ], 400);

        } catch (\Exception $e) {
            Log::error('PBB Payment Error', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Layanan Bapenda sedang tidak tersedia (H2H). Coba lagi nanti.',
            ], 503);
        }
    }

    // ─── Reversal (Pembatalan - Admin Only) ─────────────────────────────

    /**
     * Membatalkan pembayaran PBB yang sudah terjadi.
     * Hanya bisa dilakukan oleh admin/super_admin.
     */
    public function reversal(Request $request)
    {
        $user = $request->user();
        if ($user && ! \App\Support\TaxDomainHelper::canApprove($user, 'pbb_reversal')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Otorisasi ditolak: Pembatalan pembayaran PBB merupakan wewenang Bidang Pajak Daerah Wilayah I.',
            ], 403);
        }

        $request->validate([
            'nop' => 'required|string|size:18',
            'tahun' => 'required|string|size:4',
            'keterangan' => 'required|string|max:255',
        ]);

        try {
            $result = $this->bapendaService->reversal(
                $request->nop,
                $request->tahun,
                $request->keterangan
            );

            if (($result['status'] ?? 0) === 200) {
                // Update transaksi lokal jika ada
                $transaction = TransactionPbb::where('nop', $request->nop)
                    ->where('tahun', $request->tahun)
                    ->where('payment_status', 'success')
                    ->latest()
                    ->first();

                if ($transaction) {
                    $transaction->update([
                        'payment_status' => 'reversed',
                        'reversal_reason' => $request->keterangan,
                        'api_response' => $result,
                    ]);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran berhasil dibatalkan.',
                    'data' => $result,
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['msg'] ?? 'Gagal membatalkan pembayaran.',
            ], 400);

        } catch (\Exception $e) {
            Log::error('PBB Reversal Error', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pembatalan. Silakan coba lagi.',
            ], 500);
        }
    }

    // ─── Transaction History ────────────────────────────────────────────

    /**
     * Riwayat transaksi PBB (untuk admin/petugas).
     */
    public function transactions(Request $request)
    {
        $query = TransactionPbb::query()->latest();

        if ($request->has('nop')) {
            $query->where('nop', 'like', '%'.$request->nop.'%');
        }
        if ($request->has('tahun')) {
            $query->where('tahun', $request->tahun);
        }
        if ($request->has('status')) {
            $query->where('payment_status', $request->status);
        }

        $perPage = $request->per_page ?? 20;
        $transactions = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $transactions,
        ]);
    }

    /**
     * Riwayat transaksi PBB milik wajib pajak (citizen).
     */
    public function myTransactions(Request $request)
    {
        $taxpayer = Auth::guard('sanctum')->user();
        if (! $taxpayer || ! ($taxpayer instanceof \App\Models\Taxpayer)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $transactions = TransactionPbb::where('taxpayer_id', $taxpayer->id)
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => 'success',
            'data' => $transactions,
        ]);
    }

    /**
     * Download an owner-scoped PBB payment receipt.
     */
    public function downloadReceipt(Request $request, TransactionPbb $transaction)
    {
        $taxpayer = Auth::guard('sanctum')->user();
        abort_unless($taxpayer instanceof \App\Models\Taxpayer, 403);
        abort_unless($transaction->taxpayer_id === $taxpayer->id, 404);
        abort_unless(in_array($transaction->payment_status, ['success', 'reversed'], true), 422, 'Bukti belum tersedia untuk transaksi ini.');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.pbb-receipt', [
            'transaction' => $transaction,
        ]);

        return $pdf->download("Bukti-PBB-{$transaction->nop}-{$transaction->tahun}.pdf");
    }

    /**
     * Dashboard stats PBB (untuk admin).
     */
    public function stats(Request $request)
    {
        $year = $request->tahun ?? date('Y');

        $totalTransactions = TransactionPbb::where('tahun', $year)->count();
        $successTransactions = TransactionPbb::where('tahun', $year)->success()->count();
        $totalRevenue = TransactionPbb::where('tahun', $year)->success()->sum('total_bayar');
        $reversedCount = TransactionPbb::where('tahun', $year)->where('payment_status', 'reversed')->count();
        $monthSql = SqlDate::month('created_at');

        $monthlyRevenue = TransactionPbb::where('tahun', $year)
            ->success()
            ->selectRaw("$monthSql as bulan, SUM(total_bayar) as total")
            ->groupByRaw($monthSql)
            ->orderByRaw($monthSql)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_transactions' => $totalTransactions,
                'success_transactions' => $successTransactions,
                'total_revenue' => (float) $totalRevenue,
                'reversed_count' => $reversedCount,
                'monthly_revenue' => $monthlyRevenue,
            ],
        ]);
    }

    /**
     * Bulk Sync all NOPs from Bapenda (Admin Only)
     */
    public function syncAllObjects(Request $request)
    {
        $objects = TaxpayerPbbObject::all();
        $count = 0;
        $errors = 0;

        foreach ($objects as $obj) {
            try {
                $inquiry = $this->bapendaService->inquiry($obj->nop, date('Y'));
                if (($inquiry['status'] ?? 0) === 200) {
                    $obj->update([
                        'name_on_sppt' => $inquiry['nama_wp'] ?? $obj->name_on_sppt,
                        'address_on_sppt' => $inquiry['alamat_wp'] ?? $obj->address_on_sppt,
                        'kelurahan' => $inquiry['kelurahan'] ?? $obj->kelurahan,
                        'kota' => $inquiry['kota'] ?? $obj->kota,
                    ]);
                    $count++;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Sync PBB Error', ['nop' => $obj->nop, 'msg' => $e->getMessage()]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil menyinkronkan $count data NOP. ($errors gagal)",
        ]);
    }

    /**
     * Download SPPT PDF based on NOP and Year.
     */
    public function downloadSPPT(Request $request)
    {
        $request->validate([
            'nop' => 'required|string|size:18',
            'tahun' => 'required|string|size:4',
        ]);

        try {
            // Get data from Bapenda
            $inquiry = $this->bapendaService->inquiry($request->nop, $request->tahun);
            if (($inquiry['status'] ?? 0) !== 200) {
                return response()->json(['message' => 'Data SPPT tidak ditemukan di server Bapenda.'], 404);
            }

            // Create a "Virtual Bill" for compatibility with OfficialDocumentService
            // and populate it with data from Bapenda service
            $bill = new \App\Models\Bill;
            $bill->bill_number = 'V-SPPT-'.$request->nop;
            $bill->period = $request->tahun;
            $bill->amount = (float) ($inquiry['pbb_pokok'] ?? 0);

            // Temporary Taxpayer and TaxObject
            $taxpayer = new \App\Models\Taxpayer;
            $taxpayer->name = $inquiry['nama_wp'] ?? '-';
            $taxpayer->address = $inquiry['alamat_wp'] ?? '-';

            $taxObject = new \App\Models\TaxObject;
            $taxObject->nop = $request->nop;
            $taxObject->metadata = [
                'luas_bumi' => (float) ($inquiry['luas_bumi'] ?? 0),
                'luas_tanah' => (float) ($inquiry['luas_bumi'] ?? 0),
                'luas_bangunan' => (float) ($inquiry['luas_bangunan'] ?? 0),
                'kelas_bumi' => $inquiry['kelas_bumi'] ?? '',
                'kelas_bangunan' => $inquiry['kelas_bangunan'] ?? '',
            ];

            // Re-bind to Virtual Bill
            $bill->setRelation('taxpayer', $taxpayer);
            $bill->setRelation('taxObject', $taxObject);

            $docService = app(\App\Services\OfficialDocumentService::class);
            $data = $docService->generateSPPT($bill);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.sppt', $data);

            return $pdf->download("SPPT-{$request->nop}-{$request->tahun}.pdf");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Download SPPT Error', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengunduh SPPT: '.$e->getMessage()], 500);
        }
    }

    // ─── Payment Request via Gateway M-PAD (M20) ─────────────────────────

    /**
     * Membuat PaymentRequest BRIVA untuk tagihan PBB.
     *
     * Alur M20 (keputusan 02 Agu 2026):
     * 1. SIMPBB dipakai HANYA untuk validasi data (inquiry) — bukan payment.
     * 2. Tagihan dibentuk sebagai Bill internal M-PAD (fallback sppt_records).
     * 3. Pembayaran lewat Payment Gateway M-PAD (BRIVA) — NTPD dari bank persepsi.
     * 4. Callback SNAP menyimpan NTPD + sync bill internal LUNAS.
     */
    public function paymentRequest(Request $request)
    {
        $request->validate([
            'nop' => 'required|string|size:18',
            'tahun' => 'required|string|size:4',
            'method' => 'nullable|string|in:bri_va,sultra_va',
            'channel' => 'nullable|string',
        ]);

        $requestedMethod = $request->input('method');
        $requestedChannel = strtoupper((string) $request->input('channel'));
        $isSultra = ($requestedMethod === 'sultra_va' || in_array($requestedChannel, ['BANK_SULTRA', 'BANK SULTRA', 'SULTRA'], true));
        $channel = $isSultra ? 'Bank Sultra' : 'BRI';

        // --- Tentukan user & taxpayer ---
        $user = Auth::guard('sanctum')->user();
        $taxpayerId = null;

        if ($user instanceof \App\Models\Taxpayer) {
            $taxpayerId = $user->id;
        } elseif ($user instanceof \App\Models\User) {
            $taxpayer = \App\Models\Taxpayer::where('user_id', $user->id)->first();
            $taxpayerId = $taxpayer?->id;
        }

        if (! $taxpayerId) {
            return response()->json(['message' => 'Akun wajib pajak tidak ditemukan.'], 422);
        }

        // --- 1. Validasi data via SIMPBB (inquiry saja, bukan payment) ---
        try {
            $inquiry = $this->bapendaService->inquiryWithFallback($request->nop, $request->tahun);
        } catch (\Exception $e) {
            Log::warning('PBB paymentRequest inquiry gagal', ['nop' => $request->nop, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memvalidasi NOP. Silakan coba lagi.'], 502);
        }

        if (($inquiry['status'] ?? 0) !== 200) {
            return response()->json(['message' => 'Data tagihan tidak ditemukan di server Bapenda.'], 404);
        }

        $statusBayar = strtoupper($inquiry['status_bayar'] ?? '');
        if (str_contains($statusBayar, 'LUNAS') || str_contains($statusBayar, 'SDH BAYAR')) {
            return response()->json(['message' => 'Tagihan ini sudah lunas.'], 422);
        }

        $totalTagihan = (float) ($inquiry['total_harus_dibayar'] ?? $inquiry['data']['total_harus_dibayar'] ?? 0);
        if ($totalTagihan <= 0) {
            return response()->json(['message' => 'Nominal tagihan tidak valid (0).'], 422);
        }

        // --- 2. Cari / buat Bill internal M-PAD untuk NOP ini ---
        $taxObject = TaxObject::where('nop', $request->nop)->where('taxpayer_id', $taxpayerId)->first();

        if (! $taxObject) {
            $taxObject = TaxObject::create([
                'nop' => $request->nop,
                'taxpayer_id' => $taxpayerId,
                'retribution_type_id' => 16, // Pajak (PBB-P2)
                'opd_id' => 5, // Badan Pendapatan Daerah
                'name' => $inquiry['nama_wp'] ?? 'Objek PBB '.$request->nop,
                'address' => $inquiry['alamat_wp'] ?? '',
                'status' => 'active',
                'metadata' => [
                    'nop_pbb' => $request->nop,
                    'tahun_pajak' => $request->tahun,
                    'nama_wp' => $inquiry['nama_wp'] ?? '',
                    'alamat_wp' => $inquiry['alamat_wp'] ?? '',
                    'kelurahan' => $inquiry['kelurahan'] ?? '',
                    'kota' => $inquiry['kota'] ?? '',
                    'pbb_pokok' => (float) ($inquiry['pbb_pokok'] ?? 0),
                    'denda' => (float) ($inquiry['denda'] ?? 0),
                    'total_harus_dibayar' => $totalTagihan,
                    'source' => $inquiry['source'] ?? 'simpbb',
                ],
            ]);
        }

        $billService = app(BillCreationService::class);
        $bill = $billService->createForTaxObject(
            $taxObject,
            null,
            $request->tahun,
            [],
            ['source' => 'pbb_gateway'],
            null,
            'pbb_gateway',
            $totalTagihan
        );

        if (! $bill || in_array($bill->status, ['lunas', 'paid'], true)) {
            return response()->json(['message' => 'Tagihan sudah lunas.'], 422);
        }

        // --- 3. Cek kesiapan Gateway (BRI utama, Bank Sultra alternatif) ---
        if ($isSultra) {
            $sultraReadiness = app(BankSultraCitizenVaReadiness::class);
            if (! $sultraReadiness->available()) {
                return response()->json(['message' => $sultraReadiness->publicMessage()], 422);
            }
        } else {
            $brivaReadiness = app(SnapBrivaReadiness::class);
            if (! $brivaReadiness->available()) {
                return response()->json(['message' => $brivaReadiness->publicMessage()], 422);
            }
        }

        // --- 4. Buat PaymentRequest ---
        $paymentRequest = DB::transaction(function () use ($bill, $taxpayerId, $totalTagihan, $request, $channel, $isSultra) {
            // Expire PR lama yang kedaluwarsa untuk channel ini
            PaymentRequest::where('taxpayer_id', $taxpayerId)
                ->where('payment_channel', $channel)
                ->where('method', 'VA')
                ->where('status', 'pending')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->update(['status' => 'expired']);

            $expiresAt = now()->addHours(24);

            $paymentRequest = PaymentRequest::create([
                'bill_id' => $bill->id,
                'tax_object_id' => $bill->tax_object_id,
                'taxpayer_id' => $taxpayerId,
                'payment_channel' => $channel,
                'method' => 'VA',
                'amount_snapshot' => (float) $bill->amount,
                'admin_fee_snapshot' => (float) $bill->admin_fee,
                'penalty_snapshot' => 0,
                'expires_at' => $expiresAt,
                'external_id' => ($isSultra ? 'PBB-SULTRA-' : 'PBB-BRI-').Str::upper(Str::random(16)),
                'status' => 'pending',
            ]);

            $vaNumber = $isSultra
                ? $this->buildSultraVaNumber($paymentRequest->id, $bill)
                : $this->buildVaNumber($paymentRequest->id);

            $paymentRequest->update([
                'va_number' => $vaNumber,
            ]);

            PaymentRequestItem::create([
                'payment_request_id' => $paymentRequest->id,
                'bill_id' => $bill->id,
                'amount_snapshot' => (float) $bill->total_amount,
                'status' => 'pending',
            ]);

            return $paymentRequest->fresh(['items.bill.taxpayer']);
        });

        return response()->json([
            'status' => 'success',
            'message' => $isSultra
                ? 'Virtual account Bank Sultra berhasil dibuat. Silakan selesaikan pembayaran.'
                : 'Virtual account berhasil dibuat. Silakan bayar melalui BRIVA.',
            'data' => [
                'payment_request_id' => $paymentRequest->id,
                'va_number' => $paymentRequest->va_number,
                'provider' => $channel,
                'method' => $isSultra ? 'sultra_va' : 'bri_va',
                'amount' => (float) $paymentRequest->amount_snapshot,
                'total_bayar' => (float) ($paymentRequest->items->first()->amount_snapshot ?? $paymentRequest->amount_snapshot),
                'expires_at' => $paymentRequest->expires_at?->toISOString(),
                'bill_number' => $bill->bill_number,
                'nop' => $request->nop,
                'tahun' => $request->tahun,
                'wp_name' => $inquiry['nama_wp'] ?? '-',
            ],
        ], 201);
    }

    /**
     * Bangun nomor VA BRIVA dari config SNAP.
     */
    private function buildVaNumber(int $paymentRequestId): string
    {
        $partnerServiceId = preg_replace('/\D/', '', (string) config('snap.briva.partner_service_id'));
        $prefix = preg_replace('/\D/', '', (string) config('snap.briva.va_prefix'));
        $customerNoLength = (int) config('snap.briva.customer_no_length', 20);
        $length = (int) config('snap.briva.va_length', 28);

        if ($partnerServiceId !== '') {
            $customerNo = str_pad((string) $paymentRequestId, $customerNoLength, '0', STR_PAD_LEFT);

            return $partnerServiceId.$customerNo;
        }

        return $prefix.str_pad(
            (string) $paymentRequestId,
            $length - strlen($prefix),
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Bangun nomor VA Bank Sultra.
     */
    private function buildSultraVaNumber(int $paymentRequestId, ?Bill $primaryBill = null): string
    {
        $prefix = (string) config('snap.partners.BANK_SULTRA.partner_id', '7472');

        return $prefix.str_pad((string) $paymentRequestId, 8, '0', STR_PAD_LEFT);
    }

    // ─── Rekonsiliasi Harian (M20 task 1455) ────────────────────────────

    /**
     * Laporan rekonsiliasi harian transaksi PBB untuk Bapenda.
     * Menggabungkan transaksi gateway M-PAD (PaymentRequest) + H2H SIMPBB (TransactionPbb).
     *
     * ?date=YYYY-MM-DD (default hari ini) → CSV siap diserahkan ke Bapenda.
     */
    public function reconciliation(Request $request)
    {
        $date = $request->query('date', now()->format('Y-m-d'));

        $start = $date.' 00:00:00';
        $end = $date.' 23:59:59';

        // 1. Transaksi via gateway M-PAD (BRIVA/SNAP) — bill PBB lunas
        $gatewayPayments = DB::table('payments')
            ->join('bills', 'bills.id', '=', 'payments.bill_id')
            ->whereBetween('payments.paid_at', [$start, $end])
            ->where('payments.status', 'success')
            ->where(function ($q) {
                $q->where('bills.bill_number', 'like', '%-PBB')
                  ->orWhere('bills.bill_number', 'like', 'V-SPPT-%');
            })
            ->get([
                'payments.id as payment_id',
                'payments.receipt_number',
                'payments.reference_number',
                'payments.amount',
                'payments.paid_at',
                'bills.bill_number',
                'bills.period',
            ]);

        // 2. Transaksi via H2H SIMPBB (legacy, selama masa transisi)
        $h2hTransactions = TransactionPbb::whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'success')
            ->get(['nop', 'tahun', 'total_bayar', 'ntpd', 'created_at']);

        // Build CSV
        $csv = [];
        $csv[] = ['LAPORAN REKONSILIASI PBB - '.$date];
        $csv[] = ['Sumber', 'NOP', 'Periode', 'Nomor Bill', 'Receipt/NTPD', 'Reference', 'Jumlah (Rp)', 'Waktu Bayar'];

        foreach ($gatewayPayments as $p) {
            $csv[] = [
                'GATEWAY M-PAD',
                '',
                $p->period ?? '',
                $p->bill_number ?? '',
                $p->receipt_number ?? '',
                $p->reference_number ?? '',
                number_format((float) $p->amount, 0, ',', '.'),
                $p->paid_at ?? '',
            ];
        }

        foreach ($h2hTransactions as $t) {
            $csv[] = [
                'H2H SIMPBB',
                $t->nop,
                $t->tahun,
                'V-SPPT-'.$t->nop,
                $t->ntpd ?? '',
                '',
                number_format((float) $t->total_bayar, 0, ',', '.'),
                $t->created_at ?? '',
            ];
        }

        $totalGateway = $gatewayPayments->sum(fn ($p) => (float) $p->amount);
        $totalH2H = $h2hTransactions->sum(fn ($t) => (float) $t->total_bayar);
        $csv[] = [];
        $csv[] = ['TOTAL GATEWAY M-PAD', number_format($totalGateway, 0, ',', '.')];
        $csv[] = ['TOTAL H2H SIMPBB', number_format($totalH2H, 0, ',', '.')];
        $csv[] = ['GRAND TOTAL', number_format($totalGateway + $totalH2H, 0, ',', '.')];

        $lines = collect($csv)->map(fn ($row) => implode(';', $row))->implode("\n");

        return response($lines, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rekonsiliasi-pbb-'.$date.'.csv"',
        ]);
    }
}
