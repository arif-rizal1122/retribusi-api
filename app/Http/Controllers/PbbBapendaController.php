<?php

namespace App\Http\Controllers;

use App\Models\TaxpayerPbbObject;
use App\Models\TransactionPbb;
use App\Services\PbbBapendaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
            'nop'   => 'required|string|size:18',
            'tahun' => 'required|string|size:4',
        ]);

        try {
            $result = $this->bapendaService->inquiry($request->nop, $request->tahun);

            if (($result['status'] ?? 0) === 200) {
                // Skema 2026 mengembalikan data di dalam key 'data'
                $data = $result['data'] ?? [];
                
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Data tagihan ditemukan',
                    'data'    => [
                        'nop'                 => $request->nop,
                        'tahun'               => $data['tahun'] ?? $result['tahun'] ?? $request->tahun,
                        'nama_wp'             => $data['nama_wp'] ?? '-',
                        'alamat_wp'           => $data['alamat_wp'] ?? '-',
                        'kelurahan'           => $data['kelurahan'] ?? '-',
                        'kota'                => $data['kota'] ?? '-',
                        'pbb_pokok'           => (float) ($data['pbb_pokok'] ?? 0),
                        'denda'               => (float) ($data['denda'] ?? 0),
                        'total_harus_dibayar' => (float) ($data['total_harus_dibayar'] ?? 0),
                        'status_bayar'        => $data['status_bayar'] ?? '-',
                    ],
                ]);
            }

            return response()->json([
                'status'  => 'error',
                'message' => $result['msg'] ?? 'Data tidak ditemukan',
            ], 404);

        } catch (\Exception $e) {
            Log::error('PBB Inquiry Error', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal terhubung ke server Bapenda. Silakan coba lagi.',
            ], 500);
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
            'nop'   => 'required|string|size:18',
            'tahun' => 'nullable|string|size:4',
            'description' => 'nullable|string|max:100',
        ]);

        $taxpayer = Auth::guard('sanctum')->user();
        if (!$taxpayer || !($taxpayer instanceof \App\Models\Taxpayer)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // Cek duplikat
        $exists = TaxpayerPbbObject::where('taxpayer_id', $taxpayer->id)
            ->where('nop', $request->nop)
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => 'error',
                'message' => 'NOP ini sudah terdaftar di akun Anda.',
            ], 422);
        }

        // Validasi ke Bapenda
        $tahun = $request->tahun ?? date('Y');
        try {
            $result = $this->bapendaService->inquiry($request->nop, $tahun);

            if (($result['status'] ?? 0) !== 200) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'NOP tidak ditemukan di database Bapenda.',
                ], 404);
            }

            $pbbObject = TaxpayerPbbObject::create([
                'taxpayer_id'     => $taxpayer->id,
                'nop'             => $request->nop,
                'name_on_sppt'    => $result['nama_wp'] ?? null,
                'address_on_sppt' => $result['alamat_wp'] ?? null,
                'kelurahan'       => $result['kelurahan'] ?? null,
                'kota'            => $result['kota'] ?? null,
                'is_verified'     => true,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'NOP berhasil ditautkan ke akun Anda.',
                'data'    => $pbbObject,
            ]);

        } catch (\Exception $e) {
            Log::error('PBB Link NOP Error', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
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
        if (!$taxpayer || !($taxpayer instanceof \App\Models\Taxpayer)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $pbbObject = TaxpayerPbbObject::where('id', $id)
            ->where('taxpayer_id', $taxpayer->id)
            ->first();

        if (!$pbbObject) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $pbbObject->delete();

        return response()->json([
            'status'  => 'success',
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
        if (!$taxpayer || !($taxpayer instanceof \App\Models\Taxpayer)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $objects = TaxpayerPbbObject::where('taxpayer_id', $taxpayer->id)->get();
        $tahun = $request->tahun ?? date('Y');
        $results = [];

        foreach ($objects as $obj) {
            $tagihan = null;
            try {
                $inquiry = $this->bapendaService->inquiry($obj->nop, $tahun);
                if (($inquiry['status'] ?? 0) === 200) {
                    $tagihan = [
                        'pbb_pokok'           => (float) ($inquiry['pbb_pokok'] ?? 0),
                        'denda'               => (float) ($inquiry['denda'] ?? 0),
                        'total_harus_dibayar' => (float) ($inquiry['total_harus_dibayar'] ?? 0),
                        'status_bayar'        => $inquiry['status_bayar'] ?? '-',
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('PBB Inquiry for my-objects failed', [
                    'nop' => $obj->nop, 'error' => $e->getMessage()
                ]);
            }

            $results[] = [
                'id'              => $obj->id,
                'nop'             => $obj->nop,
                'name_on_sppt'    => $obj->name_on_sppt,
                'address_on_sppt' => $obj->address_on_sppt,
                'kelurahan'       => $obj->kelurahan,
                'kota'            => $obj->kota,
                'tahun'           => $tahun,
                'tagihan'         => $tagihan,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data'   => $results,
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
            'nop'   => 'required|string|size:18',
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
                    'status'  => 'error',
                    'message' => $inquiry['msg'] ?? 'Data tagihan tidak ditemukan.',
                ], 404);
            }

            // Cek apakah sudah lunas
            $statusBayar = strtoupper($inquiry['status_bayar'] ?? '');
            if (str_contains($statusBayar, 'LUNAS') || str_contains($statusBayar, 'SDH BAYAR')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Tagihan ini sudah lunas.',
                ], 422);
            }

            // Proses pembayaran ke Bapenda
            $payResult = $this->bapendaService->payment($request->nop, $request->tahun);

            $paymentStatus = ($payResult['status'] ?? 0) === 200 ? 'success' : 'failed';

            // Simpan transaksi
            $transaction = TransactionPbb::create([
                'user_id'        => $userId,
                'taxpayer_id'    => $taxpayerId,
                'nop'            => $request->nop,
                'tahun'          => $request->tahun,
                'amount'         => (float) ($inquiry['pbb_pokok'] ?? 0),
                'denda'          => (float) ($inquiry['denda'] ?? 0),
                'total_bayar'    => (float) ($inquiry['total_harus_dibayar'] ?? 0),
                'ntpd'           => $payResult['ntpd'] ?? null,
                'payment_status' => $paymentStatus,
                'wp_name'        => $inquiry['nama_wp'] ?? null,
                'wp_address'     => $inquiry['alamat_wp'] ?? null,
                'kelurahan'      => $inquiry['kelurahan'] ?? null,
                'kota'           => $inquiry['kota'] ?? null,
                'api_response'   => $payResult,
            ]);

            if ($paymentStatus === 'success') {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Pembayaran PBB berhasil.',
                    'data'    => [
                        'transaction_id' => $transaction->id,
                        'ntpd'           => $transaction->ntpd,
                        'nop'            => $transaction->nop,
                        'tahun'          => $transaction->tahun,
                        'total_bayar'    => $transaction->total_bayar,
                        'wp_name'        => $transaction->wp_name,
                    ],
                ]);
            }

            return response()->json([
                'status'  => 'error',
                'message' => $payResult['msg'] ?? 'Pembayaran gagal.',
            ], 400);

        } catch (\Exception $e) {
            Log::error('PBB Payment Error', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memproses pembayaran. Silakan coba lagi.',
            ], 500);
        }
    }

    // ─── Reversal (Pembatalan - Admin Only) ─────────────────────────────

    /**
     * Membatalkan pembayaran PBB yang sudah terjadi.
     * Hanya bisa dilakukan oleh admin/super_admin.
     */
    public function reversal(Request $request)
    {
        $request->validate([
            'nop'        => 'required|string|size:18',
            'tahun'      => 'required|string|size:4',
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
                        'payment_status'  => 'reversed',
                        'reversal_reason' => $request->keterangan,
                        'api_response'    => $result,
                    ]);
                }

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Pembayaran berhasil dibatalkan.',
                    'data'    => $result,
                ]);
            }

            return response()->json([
                'status'  => 'error',
                'message' => $result['msg'] ?? 'Gagal membatalkan pembayaran.',
            ], 400);

        } catch (\Exception $e) {
            Log::error('PBB Reversal Error', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
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
            $query->where('nop', 'like', '%' . $request->nop . '%');
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
            'data'   => $transactions,
        ]);
    }

    /**
     * Riwayat transaksi PBB milik wajib pajak (citizen).
     */
    public function myTransactions(Request $request)
    {
        $taxpayer = Auth::guard('sanctum')->user();
        if (!$taxpayer || !($taxpayer instanceof \App\Models\Taxpayer)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $transactions = TransactionPbb::where('taxpayer_id', $taxpayer->id)
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => 'success',
            'data'   => $transactions,
        ]);
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

        $monthlyRevenue = TransactionPbb::where('tahun', $year)
            ->success()
            ->selectRaw('MONTH(created_at) as bulan, SUM(total_bayar) as total')
            ->groupByRaw('MONTH(created_at)')
            ->orderByRaw('MONTH(created_at)')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'total_transactions'   => $totalTransactions,
                'success_transactions' => $successTransactions,
                'total_revenue'        => (float) $totalRevenue,
                'reversed_count'       => $reversedCount,
                'monthly_revenue'      => $monthlyRevenue,
            ],
        ]);
    }
}
