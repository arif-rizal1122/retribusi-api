<?php

namespace App\Http\Controllers;

use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Services\AutoDeductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AutoDeductController extends Controller
{
    protected AutoDeductService $autoDeductService;

    public function __construct(AutoDeductService $autoDeductService)
    {
        $this->autoDeductService = $autoDeductService;
    }

    /**
     * LAPORKAN TRANSAKSI DARI POS / MERCHANT
     * Endpoint untuk restoran/parkir/dll melaporkan transaksi real-time
     *
     * POST /api/v1/auto-deduct/transaction
     * Body: {
     *   "tax_object_nop": "NOP-001",
     *   "taxpayer_npwpd": "NPWPD-xxx",
     *   "amount": 100000,
     *   "source": "pos|qris|va|transfer",
     *   "description": "Transaksi restoran - nota #123"
     * }
     */
    public function recordTransaction(Request $request)
    {
        $request->validate([
            'tax_object_nop' => 'required_without:tax_object_id|string',
            'tax_object_id' => 'required_without:tax_object_nop|exists:tax_objects,id',
            'amount' => 'required|numeric|min:0',
            'source' => 'required|in:pos,qris,va,transfer',
            'description' => 'nullable|string',
        ]);

        // Cari tax object
        if ($request->tax_object_id) {
            $taxObject = TaxObject::with(['taxpayer', 'classification'])->findOrFail($request->tax_object_id);
        } elseif ($request->tax_object_nop) {
            $taxObject = TaxObject::with(['taxpayer', 'classification'])
                ->where('nop', $request->tax_object_nop)
                ->first();
            if (!$taxObject) {
                return response()->json(['message' => 'Tax object with NOP not found'], 404);
            }
        } else {
            return response()->json(['message' => 'tax_object_id or tax_object_nop required'], 422);
        }

        // Optional: override taxpayer
        $taxpayer = null;
        if ($request->taxpayer_npwpd) {
            $taxpayer = Taxpayer::where('npwpd', $request->taxpayer_npwpd)->first();
        }

        try {
            $result = $this->autoDeductService->recordTransaction(
                $taxObject,
                (float) $request->amount,
                $request->source,
                $request->description,
                $taxpayer
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('AutoDeduct Transaction Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * BAYAR PAJAK OTOMATIS
     * Endpoint untuk auto-charge via VA/QRIS
     *
     * POST /api/v1/auto-deduct/pay
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'tax_object_id' => 'required|exists:tax_objects,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:va,qris,transfer',
        ]);

        $taxObject = TaxObject::with(['taxpayer'])->findOrFail($request->tax_object_id);

        try {
            $result = $this->autoDeductService->processAutoPayment(
                $taxObject,
                (float) $request->amount,
                $request->payment_method
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('AutoDeduct Pay Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CEK STATUS AUTO-DEDUCT
     * GET /api/v1/auto-deduct/status?tax_object_id=X&period=2026-07
     */
    public function getStatus(Request $request)
    {
        $request->validate([
            'tax_object_id' => 'required|exists:tax_objects,id',
            'period' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = \App\Models\AutoDeductLog::where('tax_object_id', $request->tax_object_id);

        if ($request->period) {
            $query->where('created_at', 'like', $request->period . '%');
        }

        $logs = $query->latest()->paginate($request->get('limit', 20));

        return response()->json([
            'data' => $logs,
            'stats' => [
                'total_transactions' => $logs->total(),
                'total_tax_collected' => (float) $query->sum('tax_amount'),
                'total_deducted' => (float) $query->sum('deducted_amount'),
            ]
        ]);
    }

    /**
     * RIWAYAT NOTIFIKASI
     * GET /api/v1/auto-deduct/notifications?taxpayer_id=X
     */
    public function getNotifications(Request $request)
    {
        $request->validate([
            'taxpayer_id' => 'nullable|exists:taxpayers,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = \App\Models\NotificationLog::query();

        if ($request->taxpayer_id) {
            $query->where('taxpayer_id', $request->taxpayer_id);
        }

        return response()->json(
            $query->latest()->paginate($request->get('limit', 20))
        );
    }

    /**
     * TRIGGER BILL REMINDER MANUAL
     * POST /api/v1/auto-deduct/send-reminders
     */
    public function sendReminders()
    {
        $result = $this->autoDeductService->sendBulkBillReminders();

        return response()->json([
            'status' => 'success',
            'data' => $result,
            'message' => "Notifikasi terkirim: {$result['sent']}, gagal: {$result['failed']}"
        ]);
    }
}
