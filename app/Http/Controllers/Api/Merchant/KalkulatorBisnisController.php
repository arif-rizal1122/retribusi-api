<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AutoDeductLog;
use App\Jobs\ExecuteAutoDebitAFT;
use Illuminate\Support\Str;

class KalkulatorBisnisController extends Controller
{
    /**
     * Menerima input omzet harian dari Kalkulator Bisnis.
     * Mengkalkulasi pajak PBJT (10%) dan membuat antrean (Job) AFT.
     */
    public function submitOmzet(Request $request)
    {
        $request->validate([
            'transaction_amount' => 'required|numeric|min:1',
        ]);

        // Auth User (Taxpayer) via Sanctum
        $user = $request->user();

        // Cek jika bukan Wajib Pajak Merchant
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Harap login terlebih dahulu.'
            ], 401);
        }

        $omzet = $request->transaction_amount;
        // Asumsi pajak PBJT 10%
        $taxAmount = $omzet * 0.10;

        // Buat record AutoDeductLog
        $log = AutoDeductLog::create([
            // Default 1 untuk simulasi, idealnya diambil dari relasi User/Taxpayer
            'city_id' => 1,
            'taxpayer_id' => $user->id,
            'source' => 'm-pad_mobile_calculator',
            'transaction_type' => 'pbjt_tax_deduction',
            'transaction_amount' => $omzet,
            'tax_amount' => $taxAmount,
            'deducted_amount' => $taxAmount,
            'reference_number' => 'AFT-' . strtoupper(Str::random(10)),
            'payment_channel' => 'BPD_SULTRA_DIRECT_DEBIT',
            'status' => 'pending',
            'escrow_settlement_status' => 'pending',
        ]);

        // Trigger Queue Job AFT
        ExecuteAutoDebitAFT::dispatch($log->id);

        return response()->json([
            'success' => true,
            'message' => 'Omzet berhasil dicatat. Proses AFT telah dimulai.',
            'data' => [
                'reference_number' => $log->reference_number,
                'tax_amount' => $taxAmount,
                'omzet' => $omzet
            ]
        ], 201);
    }

    /**
     * Mengambil riwayat AFT (Auto Deduct Log) milik Wajib Pajak ini.
     */
    public function getAftHistory(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 401);
        }

        $history = AutoDeductLog::where('taxpayer_id', $user->id)
            ->where('source', 'm-pad_mobile_calculator')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalTaxDeductedThisMonth = AutoDeductLog::where('taxpayer_id', $user->id)
            ->where('status', 'success')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('deducted_amount');

        $totalOmzet = AutoDeductLog::where('taxpayer_id', $user->id)
            ->sum('transaction_amount');
            
        $successCount = AutoDeductLog::where('taxpayer_id', $user->id)
            ->where('status', 'success')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'history' => $history,
                'stats' => [
                    'total_tax_this_month' => $totalTaxDeductedThisMonth,
                    'total_omzet' => $totalOmzet,
                    'success_count' => $successCount
                ]
            ]
        ]);
    }
}
