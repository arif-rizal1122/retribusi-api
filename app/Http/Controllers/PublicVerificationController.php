<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Http\Request;

class PublicVerificationController extends Controller
{
    /**
     * Publicly verify a bill via QR code
     */
    public function verifyBill($number)
    {
        $bill = Bill::with(['retributionType', 'opd', 'taxObject', 'taxpayer'])
            ->where('bill_number', $number)
            ->first();

        if (!$bill) {
            return view('verification.invalid', ['number' => $number, 'title' => 'Dokumen Tidak Ditemukan']);
        }

        // --- Advanced Billing V2: Real-time Penalty Sync on Verification ---
        if ($bill->status !== 'lunas' && $bill->taxObject) {
            $service = app(\App\Services\BillingService::class);
            $periods = $service->getPendingPeriods($bill->taxObject);
            $matchingPeriod = $periods->firstWhere('period', $bill->period);
            
            if ($matchingPeriod) {
                $bill->penalty_amount = $matchingPeriod['penalty_amount'];
                // Persist sync result so other platforms (Admin Dashboard) see updated data
                $bill->save(); 
            }
        }

        // Ambil bukti pembayaran (kalau ada) supaya status yang ditampilkan JUJUR.
        // Tanpa ini, halaman selalu bilang "MENUNGGU PEMBAYARAN" walau sudah lunas.
        $bill->loadMissing('payments');
        $payment = null;
        if ($bill->status === 'lunas' || $bill->status === 'paid') {
            $payment = $bill->payments
                ->sortByDesc(fn($p) => $p->paid_at ?? $p->created_at)
                ->first();
        }

        return view('verification.bill', [
            'bill' => $bill,
            'payment' => $payment,
            'title' => 'Verifikasi Tagihan (SKRD)',
            'is_valid' => true,
        ]);
    }

    /**
     * Publicly verify a payment via QR code
     */
    public function verifyPayment($number)
    {
        $bill = Bill::with(['retributionType', 'opd', 'taxObject', 'taxpayer', 'payments'])
            ->where('bill_number', $number)
            ->first();

        if (!$bill || $bill->status !== 'lunas' && $bill->status !== 'paid') {
            return view('verification.invalid', ['number' => $number, 'title' => 'Dokumen Tidak Ditemukan']);
        }

        return view('verification.payment', [
            'bill' => $bill,
            'payment' => $bill->payments->first(),
            'title' => 'Verifikasi Bukti Bayar (SSPD)',
            'is_valid' => true,
        ]);
    }
}
