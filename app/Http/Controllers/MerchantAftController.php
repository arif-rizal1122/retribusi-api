<?php

namespace App\Http\Controllers;

use App\Models\AftRegistration;
use App\Models\AftTransaction;
use App\Models\Taxpayer;
use App\Services\AftSettlementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MerchantAftController extends Controller
{
    public function __construct(protected AftSettlementService $service)
    {
    }

    /**
     * POST /api/citizen/merchant/enroll
     * Daftarkan rekening AFT (Bank Kolaborator) untuk wajib pajak.
     */
    public function enroll(Request $request)
    {
        $taxpayer = $request->user();

        if (!$taxpayer instanceof Taxpayer) {
            return response()->json(['success' => false, 'message' => 'Hanya akun wajib pajak.'], 403);
        }

        $request->validate([
            'bank' => 'required|string|max:100',
            'bank_code' => 'nullable|string|max:50',
            'beneficiary_account' => 'required|string|max:50',
            'beneficiary_name' => 'required|string|max:255',
            'surat_kuasa_url' => 'nullable|string|max:2048',
        ]);

        $existing = $taxpayer->aftRegistration;

        if ($existing && in_array($existing->approval_status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => $existing->approval_status === 'approved'
                    ? 'AFT sudah aktif untuk akun Anda.'
                    : 'Pengajuan AFT Anda masih menunggu persetujuan admin.',
            ], 422);
        }

        $registration = AftRegistration::updateOrCreate(
            ['taxpayer_id' => $taxpayer->id],
            [
                'status' => 'active',
                'approval_status' => 'pending',
                'bank' => $request->input('bank'),
                'bank_code' => $request->input('bank_code'),
                'beneficiary_account' => $request->input('beneficiary_account'),
                'beneficiary_name' => $request->input('beneficiary_name'),
                'surat_kuasa_url' => $request->input('surat_kuasa_url'),
                'submitted_at' => Carbon::now(),
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan AFT berhasil dikirim dan menunggu persetujuan admin.',
            'data' => $this->registrationPayload($taxpayer, $registration),
        ], 201);
    }

    /**
     * GET /api/citizen/merchant/aft-status
     * Status registrasi AFT wajib pajak.
     */
    public function status(Request $request)
    {
        $taxpayer = $request->user();

        if (!$taxpayer instanceof Taxpayer) {
            return response()->json(['success' => false, 'message' => 'Hanya akun wajib pajak.'], 403);
        }

        $registration = $taxpayer->aftRegistration;

        return response()->json([
            'success' => true,
            'data' => $this->registrationPayload($taxpayer, $registration),
        ]);
    }

    /**
     * GET /api/citizen/merchant/aft-history
     * Riwayat pemotongan AFT + statistik bulan berjalan.
     */
    public function history(Request $request)
    {
        $taxpayer = $request->user();

        if (!$taxpayer instanceof Taxpayer) {
            return response()->json(['success' => false, 'message' => 'Hanya akun wajib pajak.'], 403);
        }

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $transactions = AftTransaction::where('taxpayer_id', $taxpayer->id)
            ->latest('id')
            ->limit(50)
            ->get();

        $monthly = AftTransaction::where('taxpayer_id', $taxpayer->id)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'history' => $transactions->map(function (AftTransaction $tx) {
                    return [
                        'id' => $tx->id,
                        'status' => $tx->status,
                        'created_at' => $tx->created_at->toISOString(),
                        'transaction_amount' => (float) $tx->transaction_amount,
                        'tax_amount' => (float) $tx->tax_amount,
                    ];
                })->values()->all(),
                'stats' => [
                    'total_tax_this_month' => round($monthly->sum(fn ($tx) => (float) $tx->tax_amount), 2),
                    'total_omzet' => round($monthly->sum(fn ($tx) => (float) $tx->transaction_amount), 2),
                    'success_count' => $monthly->whereIn('status', ['success', 'settled'])->count(),
                ],
            ],
        ]);
    }

    /**
     * POST /api/citizen/merchant/submit-omzet
     * Wajib pajak (self-assessment) melaporkan omzet harian; AFT memotong pajak otomatis.
     */
    public function submitOmzet(Request $request)
    {
        $taxpayer = $request->user();

        if (!$taxpayer instanceof Taxpayer) {
            return response()->json(['success' => false, 'message' => 'Hanya akun wajib pajak.'], 403);
        }

        $request->validate([
            'transaction_amount' => 'required|numeric|min:0',
        ]);

        $registration = $this->service->getActiveRegistration($taxpayer);

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'AFT belum aktif. Silakan daftarkan rekening dan tunggu persetujuan admin.',
            ], 422);
        }

        $transactionAmount = (float) $request->input('transaction_amount');

        if ($transactionAmount <= 0) {
            return response()->json(['success' => false, 'message' => 'Nominal omzet harus lebih dari nol.'], 422);
        }

        $transaction = $this->service->recordOmzet($taxpayer, $transactionAmount);

        return response()->json([
            'success' => true,
            'message' => 'Omzet tercatat. Pemotongan titipan pajak AFT diproses otomatis.',
            'data' => [
                'transaction_id' => $transaction->id,
                'transaction_amount' => (float) $transaction->transaction_amount,
                'tax_amount' => (float) $transaction->tax_amount,
                'status' => $transaction->status,
                'settled_at' => $transaction->settled_at?->toISOString(),
            ],
        ], 201);
    }

    /**
     * DELETE /api/citizen/merchant/enroll
     * Nonaktifkan AFT milik wajib pajak.
     */
    public function deactivate(Request $request)
    {
        $taxpayer = $request->user();

        if (!$taxpayer instanceof Taxpayer) {
            return response()->json(['success' => false, 'message' => 'Hanya akun wajib pajak.'], 403);
        }

        AftRegistration::where('taxpayer_id', $taxpayer->id)->update(['status' => 'inactive']);

        $taxpayer->update(['aft_enabled' => false]);

        return response()->json([
            'success' => true,
            'message' => 'AFT dinonaktifkan.',
        ]);
    }

    protected function registrationPayload(Taxpayer $taxpayer, ?AftRegistration $registration): array
    {
        return [
            'enabled' => (bool) $taxpayer->aft_enabled,
            'approval_status' => $registration?->approval_status,
            'status' => $registration?->status,
            'bank' => $registration?->bank,
            'bank_code' => $registration?->bank_code,
            'beneficiary_account' => $registration?->beneficiary_account,
            'beneficiary_name' => $registration?->beneficiary_name,
            'submitted_at' => $registration?->submitted_at?->toISOString(),
            'approved_at' => $registration?->approved_at?->toISOString(),
            'rejection_reason' => $registration?->rejection_reason,
        ];
    }
}
