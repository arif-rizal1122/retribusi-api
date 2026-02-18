<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\EnforcementNotice;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class OfficialDocumentService
{
    protected $tteService;

    public function __construct(\App\Services\TTEService $tteService)
    {
        $this->tteService = $tteService;
    }
    /**
     * Generate SPP (Surat Perintah Pemeriksaan)
     */
    public function generateSPP(EnforcementNotice $notice)
    {
        return [
            'number' => $notice->number,
            'taxpayer' => $notice->taxObject->taxpayer->name,
            'tax_object' => $notice->taxObject->name,
            'address' => $notice->taxObject->address ?? 'Kota Kendari',
            'notes' => $notice->notes,
            'date' => $notice->created_at->translatedFormat('d F Y'),
            'qr_url' => url("/verify/spp/{$notice->number}"),
        ];
    }

    /**
     * Generate SKRD (Surat Ketetapan Retribusi Daerah)
     */
    public function generateSKRD(Bill $bill)
    {
        $total = (float) $bill->amount + (float) $bill->penalty_amount + (float) $bill->fixed_fine_amount + (float) $bill->surcharge_amount;

        return [
            'title' => $bill->retributionType->name,
            'number' => $bill->bill_number,
            'taxpayer' => $bill->taxpayer->name,
            'address' => $bill->taxpayer->address ?? 'Kota Baubau',
            'amount' => $bill->amount,
            'penalty_amount' => $bill->penalty_amount,
            'fixed_fine_amount' => $bill->fixed_fine_amount,
            'surcharge_amount' => $bill->surcharge_amount,
            'total_amount' => $total,
            'terbilang' => self::terbilang($total) . " Rupiah",
            'period' => $bill->period,
            'due_date' => $bill->due_date,
            'qr_url' => url("/api/verify/bill/{$bill->bill_number}"),
        ];
    }

    /**
     * Generate SSPD (Surat Setoran Pajak Daerah)
     */
    public function generateSSPD(Bill $bill)
    {
        if ($bill->status !== 'paid' && $bill->status !== 'lunas') {
            throw new \Exception("Hanya tagihan LUNAS yang bisa mencetak SSPD.");
        }

        $total = (float) $bill->amount + (float) $bill->penalty_amount + (float) $bill->fixed_fine_amount + (float) $bill->surcharge_amount;

        return [
            'title' => $bill->retributionType->name,
            'number' => $bill->bill_number,
            'taxpayer' => $bill->taxpayer->name,
            'address' => $bill->taxpayer->address ?? 'Kota Baubau',
            'amount' => $bill->amount,
            'penalty_amount' => $bill->penalty_amount,
            'fixed_fine_amount' => $bill->fixed_fine_amount,
            'surcharge_amount' => $bill->surcharge_amount,
            'total_amount' => $total,
            'terbilang' => self::terbilang($total) . " Rupiah",
            'period' => $bill->period,
            'paid_at' => $bill->payments->first()->paid_at ?? now(),
            'qr_url' => url("/api/verify/payment/{$bill->bill_number}"),
        ];
    }

    /**
     * Sign an official document (TTE)
     */
    public function signDocument($type, $id, \App\Models\User $signer, $notes = null)
    {
        $documentable = null;
        if ($type === 'bill') {
            $documentable = Bill::findOrFail($id);
        } elseif ($type === 'notice') {
            $documentable = EnforcementNotice::findOrFail($id);
        }

        if (!$documentable) {
            throw new \Exception("Document type not supported for signing.");
        }

        return $this->tteService->signDocument($documentable, $signer, $notes);
    }

    /**
     * Indonesian Number to Words Helper
     */
    public static function terbilang($angka)
    {
        $angka = abs($angka);
        $baca = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
        $terbilang = "";
        
        if ($angka < 12) {
            $terbilang = " " . $baca[$angka];
        } else if ($angka < 20) {
            $terbilang = self::terbilang($angka - 10) . " Belas";
        } else if ($angka < 100) {
            $terbilang = self::terbilang($angka / 10) . " Puluh" . self::terbilang($angka % 10);
        } else if ($angka < 200) {
            $terbilang = " Seratus" . self::terbilang($angka - 100);
        } else if ($angka < 1000) {
            $terbilang = self::terbilang($angka / 100) . " Ratus" . self::terbilang($angka % 100);
        } else if ($angka < 2000) {
            $terbilang = " Seribu" . self::terbilang($angka - 1000);
        } else if ($angka < 1000000) {
            $terbilang = self::terbilang($angka / 1000) . " Ribu" . self::terbilang($angka % 1000);
        } else if ($angka < 1000000000) {
            $terbilang = self::terbilang($angka / 1000000) . " Juta" . self::terbilang($angka % 1000000);
        }
        
        return trim($terbilang);
    }
}
