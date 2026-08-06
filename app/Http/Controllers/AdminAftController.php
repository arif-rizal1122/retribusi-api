<?php

namespace App\Http\Controllers;

use App\Models\AftRegistration;
use App\Models\AftTransaction;
use App\Models\Taxpayer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminAftController extends Controller
{
    /**
     * GET /api/admin/aft/registrations
     * Daftar registrasi AFT dengan status filter.
     */
    public function registrations(Request $request)
    {
        $query = AftRegistration::with('taxpayer:id,name,npwpd,nik,phone');

        if ($request->has('approval_status')) {
            $query->where('approval_status', $request->input('approval_status'));
        }

        $items = $query->orderByDesc('id')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => collect($items->items())->map(function (AftRegistration $registration) {
                return [
                    'id' => $registration->id,
                    'taxpayer' => $registration->taxpayer?->only(['id', 'name', 'npwpd', 'nik', 'phone']),
                    'bank' => $registration->bank,
                    'bank_code' => $registration->bank_code,
                    'beneficiary_account' => $registration->beneficiary_account,
                    'beneficiary_name' => $registration->beneficiary_name,
                    'surat_kuasa_url' => $registration->surat_kuasa_url,
                    'approval_status' => $registration->approval_status,
                    'status' => $registration->status,
                    'submitted_at' => $registration->submitted_at?->toISOString(),
                    'approved_at' => $registration->approved_at?->toISOString(),
                    'approved_by' => $registration->approvedBy?->only(['id', 'name']),
                    'rejection_reason' => $registration->rejection_reason,
                ];
            })->values()->all(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * POST /api/admin/aft/registrations/{id}/approve
     */
    public function approve(Request $request, $id)
    {
        $registration = AftRegistration::findOrFail($id);

        $registration->update([
            'approval_status' => 'approved',
            'approved_by' => $request->user()?->id,
            'approved_at' => Carbon::now(),
            'rejection_reason' => null,
        ]);

        Taxpayer::where('id', $registration->taxpayer_id)->update(['aft_enabled' => true]);

        return response()->json([
            'success' => true,
            'message' => 'AFT disetujui. Pemotongan otomatis kini aktif untuk wajib pajak ini.',
        ]);
    }

    /**
     * POST /api/admin/aft/registrations/{id}/reject
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $registration = AftRegistration::findOrFail($id);

        $registration->update([
            'approval_status' => 'rejected',
            'approved_by' => $request->user()?->id,
            'approved_at' => Carbon::now(),
            'rejection_reason' => $request->input('reason'),
        ]);

        Taxpayer::where('id', $registration->taxpayer_id)->update(['aft_enabled' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan AFT ditolak.',
        ]);
    }

    /**
     * GET /api/admin/aft/transactions
     * Riwayat pemotongan AFT untuk admin.
     */
    public function transactions(Request $request)
    {
        $query = AftTransaction::with(['taxpayer:id,name,npwpd', 'payment']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('search')) {
            $query->whereHas('taxpayer', fn ($t) => $t->where('name', 'like', '%' . $request->search . '%'));
        }

        $items = $query->orderByDesc('id')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => collect($items->items())->map(function (AftTransaction $tx) {
                return [
                    'id' => $tx->id,
                    'taxpayer' => $tx->taxpayer?->only(['id', 'name', 'npwpd']),
                    'payment_id' => $tx->payment_id,
                    'transaction_amount' => (float) $tx->transaction_amount,
                    'tax_amount' => (float) $tx->tax_amount,
                    'beneficiary_account' => $tx->beneficiary_account,
                    'beneficiary_bank' => $tx->beneficiary_bank,
                    'status' => $tx->status,
                    'settled_at' => $tx->settled_at?->toISOString(),
                    'created_at' => $tx->created_at?->toISOString(),
                ];
            })->values()->all(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * GET /api/admin/aft/stats
     * Ringkasan AFT untuk dashboard admin.
     */
    public function stats(Request $request)
    {
        $monthStart = Carbon::now()->startOfMonth();

        $monthlyTransactions = AftTransaction::where('created_at', '>=', $monthStart)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'pending_approvals' => AftRegistration::where('approval_status', 'pending')->count(),
                'active_taxpayers' => AftRegistration::where('approval_status', 'approved')->where('status', 'active')->count(),
                'total_tax_this_month' => round($monthlyTransactions->sum(fn ($tx) => (float) $tx->tax_amount), 2),
                'total_omzet_this_month' => round($monthlyTransactions->sum(fn ($tx) => (float) $tx->transaction_amount), 2),
                'settled_count' => $monthlyTransactions->where('status', 'settled')->count(),
                'pending_settlement_count' => $monthlyTransactions->where('status', 'pending')->count(),
            ],
        ]);
    }
}
