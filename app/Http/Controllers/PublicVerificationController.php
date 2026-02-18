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
            return view('verification.invalid', ['number' => $number]);
        }

        return view('verification.bill', [
            'bill' => $bill,
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
            return view('verification.invalid', ['number' => $number]);
        }

        return view('verification.payment', [
            'bill' => $bill,
            'payment' => $bill->payments->first(),
            'title' => 'Verifikasi Bukti Bayar (SSPD)',
            'is_valid' => true,
        ]);
    }
}
