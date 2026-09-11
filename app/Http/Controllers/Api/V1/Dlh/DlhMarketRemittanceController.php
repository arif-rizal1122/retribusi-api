<?php

namespace App\Http\Controllers\Api\V1\Dlh;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DlhMarketRemittanceController extends Controller
{
    /**
     * Juru pungut pasar menarik karcis sampah harian (Rp 3.000 atau Rp 6.000)
     */
    public function issueMarketTicket(Request $request)
    {
        $validated = $request->validate([
            'market_name' => 'required|string|max:100',
            'stall_name_or_number' => 'nullable|string|max:100',
            'merchant_name' => 'nullable|string|max:150',
            'amount' => 'required|numeric|in:3000,6000',
            'payment_method' => 'required|string|in:TUNAI,QRIS_INSTANT',
        ]);

        $userId = $request->user()?->id ?? 1;
        $ticketCode = 'KRC-SMP-' . date('ymd') . '-' . strtoupper(Str::random(6));
        $qrToken = 'MPAD-DLH-TICKET-' . md5($ticketCode . now()->timestamp);

        $ticketId = DB::table('dlh_market_trash_tickets')->insertGetId([
            'ticket_code' => $ticketCode,
            'collector_user_id' => $userId,
            'remittance_id' => null, // Belum disetor ke RKUD
            'market_name' => $validated['market_name'],
            'stall_name_or_number' => $validated['stall_name_or_number'] ?? null,
            'merchant_name' => $validated['merchant_name'] ?? null,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'qr_token' => $qrToken,
            'issued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ticket = DB::table('dlh_market_trash_tickets')->where('id', $ticketId)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Karcis sampah pasar berhasil diterbitkan.',
            'data' => $ticket,
        ], 201);
    }

    /**
     * Dapatkan saldo akumulasi karcis harian yang sedang dipegang juru pungut (Holding Balance)
     */
    public function getCollectorHoldingBalance(Request $request)
    {
        $userId = $request->user()?->id ?? 1;
        $month = $request->query('month', date('n'));
        $year = $request->query('year', date('Y'));

        $tickets = DB::table('dlh_market_trash_tickets')
            ->where('collector_user_id', $userId)
            ->whereNull('remittance_id') // Yang belum masuk setoran
            ->whereMonth('issued_at', $month)
            ->whereYear('issued_at', $year)
            ->get();

        $totalAmount = $tickets->sum('amount');
        $totalCount = $tickets->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'collector_user_id' => $userId,
                'period_month' => (int) $month,
                'period_year' => (int) $year,
                'unremitted_ticket_count' => $totalCount,
                'holding_balance' => (float) $totalAmount,
                'tickets_summary' => [
                    'karcis_3000' => $tickets->where('amount', 3000)->count(),
                    'karcis_6000' => $tickets->where('amount', 6000)->count(),
                    'total_cash' => (float) $tickets->where('payment_method', 'TUNAI')->sum('amount'),
                    'total_qris' => (float) $tickets->where('payment_method', 'QRIS_INSTANT')->sum('amount'),
                ],
            ],
        ]);
    }

    /**
     * Juru pungut menyerahkan setoran akhir bulan (Submit Remittance)
     */
    public function submitRemittance(Request $request)
    {
        $validated = $request->validate([
            'period_month' => 'required|integer|between:1,12',
            'period_year' => 'required|integer|min:2024',
            'remitted_amount' => 'required|numeric|min:1',
            'bank_reference_number' => 'nullable|string|max:100',
            'sts_proof_url' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $userId = $request->user()?->id ?? 1;

        return DB::transaction(function () use ($validated, $userId) {
            $unremittedTickets = DB::table('dlh_market_trash_tickets')
                ->where('collector_user_id', $userId)
                ->whereNull('remittance_id')
                ->whereMonth('issued_at', $validated['period_month'])
                ->whereYear('issued_at', $validated['period_year'])
                ->get();

            if ($unremittedTickets->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada karcis belum disetor untuk periode tersebut.',
                ], 422);
            }

            $totalCollected = $unremittedTickets->sum('amount');
            $remittanceNumber = 'REM-DLH-' . date('ymd') . '-' . strtoupper(Str::random(5));

            $remittanceId = DB::table('dlh_collector_remittances')->insertGetId([
                'remittance_number' => $remittanceNumber,
                'collector_user_id' => $userId,
                'opd_id' => 4,
                'period_month' => $validated['period_month'],
                'period_year' => $validated['period_year'],
                'total_tickets_issued' => $unremittedTickets->count(),
                'total_amount_collected' => $totalCollected,
                'remitted_amount' => $validated['remitted_amount'],
                'remittance_date' => now()->toDateString(),
                'bank_reference_number' => $validated['bank_reference_number'] ?? null,
                'sts_proof_url' => $validated['sts_proof_url'] ?? null,
                'status' => 'SUBMITTED',
                'notes' => json_encode(['collector_notes' => $validated['notes'] ?? '']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Kunci karcis-karcis tersebut ke remittance_id ini
            DB::table('dlh_market_trash_tickets')
                ->whereIn('id', $unremittedTickets->pluck('id'))
                ->update([
                    'remittance_id' => $remittanceId,
                    'updated_at' => now(),
                ]);

            $remittance = DB::table('dlh_collector_remittances')->where('id', $remittanceId)->first();

            return response()->json([
                'status' => 'success',
                'message' => 'Setoran karcis pasar berhasil diajukan untuk verifikasi bendahara penerimaan DLH.',
                'data' => $remittance,
            ], 201);
        });
    }

    /**
     * Bendahara penerimaan DLH memverifikasi setoran kasda (Verify Remittance)
     */
    public function verifyRemittance(Request $request, $remittanceId)
    {
        $remittance = DB::table('dlh_collector_remittances')->where('id', $remittanceId)->first();
        if (!$remittance) {
            return response()->json(['status' => 'error', 'message' => 'Data remittance tidak ditemukan.'], 404);
        }

        $verifierId = $request->user()?->id ?? 1;

        DB::table('dlh_collector_remittances')->where('id', $remittanceId)->update([
            'status' => 'RECONCILED',
            'verified_by' => $verifierId,
            'verified_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Setoran juru pungut telah diverifikasi dan direkonsiliasi ke Kasda RKUD.',
        ]);
    }
}
