<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Http\Request;

class PublicVerificationController extends Controller
{
    /**
     * Cari tagihan berdasarkan bill_number ATAU receipt_number (NTPD).
     *
     * Warga memegang struk termal dari bank yang isinya NTPD, sedangkan
     * QR dokumen berisi bill_number. Keduanya harus bisa diverifikasi.
     */
    protected function findBillByAnyNumber(string $number, bool $withPayments = false)
    {
        $with = ['retributionType', 'opd', 'taxObject', 'taxpayer'];
        if ($withPayments) {
            $with[] = 'payments';
        }

        // 1) coba bill_number dulu (kasus normal QR dokumen)
        $bill = Bill::with($with)->where('bill_number', $number)->first();
        if ($bill) {
            return $bill;
        }

        // 2) fallback: cari lewat receipt_number / NTPD (struk bank/termal)
        $payment = Payment::where('receipt_number', $number)
            ->orWhere('reference_number', $number)
            ->orderByDesc('id')
            ->first();

        if (! $payment) {
            return null;
        }

        return Bill::with($with)->where('id', $payment->bill_id)->first();
    }

    /**
     * Publicly verify a bill via QR code
     */
    public function verifyBill($number)
    {
        $bill = $this->findBillByAnyNumber($number);

        if (!$bill) {
            return view('verification.invalid', ['number' => $number, 'title' => 'Dokumen Tidak Ditemukan']);
        }

        // --- Advanced Billing V2: Real-time Penalty Sync on Verification ---
        if ($bill->status !== 'lunas' && $bill->taxObject) {
            $service = app(\App\Services\BillingService::class);
            $periods = $service->getPendingPeriods($bill->taxObject);
            $matchingPeriod = $periods->firstWhere('period', $bill->period);
            
            if ($matchingPeriod) {
                // READ-ONLY: jangan tulis DB dari endpoint publik.
                // Nilai denda dipakai untuk tampilan saja; penyimpanan dilakukan
                // oleh job/scheduler internal, bukan saat warga membuka halaman.
                $bill->penalty_amount = $matchingPeriod['penalty_amount'];
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
        $bill = $this->findBillByAnyNumber($number, withPayments: true);

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
