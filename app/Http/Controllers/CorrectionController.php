<?php

namespace App\Http\Controllers;

use App\Models\TaxTransaction;
use App\Services\CorrectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CorrectionController extends Controller
{
    protected CorrectionService $correctionService;

    public function __construct(CorrectionService $correctionService)
    {
        $this->correctionService = $correctionService;
    }

    /**
     * VOID TRANSAKSI
     * POST /api/v1/correction/{transaction}/void
     */
    public function void(TaxTransaction $transaction, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        // Cek apakah sudah di-void sebelumnya
        if ($transaction->correction_type === 'void') {
            return response()->json(['message' => 'Transaksi sudah di-void sebelumnya'], 422);
        }

        try {
            $result = $this->correctionService->voidTransaction(
                $transaction,
                $request->reason,
                $request->details ?? []
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Void Transaction Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * ADJUSTMENT HARGA
     * POST /api/v1/correction/{transaction}/adjust
     */
    public function adjustPrice(TaxTransaction $transaction, Request $request)
    {
        $request->validate([
            'new_amount' => 'required|numeric|min:0',
            'reason' => 'required|string|min:5',
        ]);

        if ($transaction->correction_type === 'void') {
            return response()->json(['message' => 'Tidak bisa menyesuaikan transaksi yang sudah di-void'], 422);
        }

        try {
            $result = $this->correctionService->adjustTransactionPrice(
                $transaction,
                (float) $request->new_amount,
                $request->reason,
                $request->details ?? []
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Adjust Transaction Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PARTIAL REFUND
     * POST /api/v1/correction/{transaction}/refund
     */
    public function refund(TaxTransaction $transaction, Request $request)
    {
        $request->validate([
            'refund_amount' => 'required|numeric|min:0',
            'reason' => 'required|string|min:5',
        ]);

        if ($transaction->correction_type === 'void') {
            return response()->json(['message' => 'Tidak bisa refund transaksi yang sudah di-void'], 422);
        }

        try {
            $result = $this->correctionService->partialRefund(
                $transaction,
                (float) $request->refund_amount,
                $request->reason
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Refund Transaction Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * RIWAYAT KOREKSI
     * GET /api/v1/correction/{transaction}/history
     */
    public function history(TaxTransaction $transaction)
    {
        return response()->json(
            $this->correctionService->getCorrectionHistory($transaction)
        );
    }
}
