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
     * Generate SPPT (Surat Pemberitahuan Pajak Terutang) for PBB-P2
     */
    public function generateSPPT(Bill $bill)
    {
        $bill->load(['taxpayer', 'taxObject.classification', 'retributionType']);
        
        $metadata = array_merge($bill->taxObject->metadata ?? [], $bill->metadata ?? []);
        
        $luasBumi = (float) ($metadata['luas_bumi'] ?? $metadata['luas_tanah'] ?? 0);
        $kelasBumi = (string) ($metadata['kelas_bumi'] ?? '');
        $luasBangunan = (float) ($metadata['luas_bangunan'] ?? 0);
        $kelasBangunan = (string) ($metadata['kelas_bangunan'] ?? '');
        
        $pbbService = app(\App\Services\PbbCalculationService::class);
        $njoptkp = (float) ($metadata['njoptkp'] ?? 10000000);
        $tariff = (float) ($metadata['tariff'] ?? 0.001);

        $calc = $pbbService->calculate($luasBumi, $kelasBumi, $luasBangunan, $kelasBangunan, $njoptkp, $tariff);

        $totalPbb = (float) $calc['pbb_terhutang'];

        $qrBase64 = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(200)->generate(url("/api/verify/bill/{$bill->bill_number}")));

        return [
            'nop' => $bill->taxObject->nop ?? 'BELUM ADA NOP',
            'year' => date('Y', strtotime($bill->period_start ?? $bill->created_at)),
            'taxpayer_name' => $bill->taxpayer->name,
            'taxpayer_address' => $bill->taxpayer->address ?? 'Kota Baubau',
            'luas_tanah' => $luasBumi,
            'kelas_bumi' => $kelasBumi,
            'njop_bumi_m2' => $calc['njop_bumi_per_m2'],
            'total_njop_bumi' => $calc['total_njop_bumi'],
            'luas_bangunan' => $luasBangunan,
            'kelas_bangunan' => $kelasBangunan,
            'njop_bangunan_m2' => $calc['njop_bangunan_per_m2'],
            'total_njop_bangunan' => $calc['total_njop_bangunan'],
            'total_njop' => $calc['total_njop'],
            'njoptkp' => $njoptkp,
            'njop_kp' => $calc['total_njop'] - $njoptkp,
            'tariff_percent' => $tariff * 100,
            'pbb_terhutang' => $totalPbb,
            'terbilang' => self::terbilang($totalPbb),
            'due_date' => $bill->due_date ? $bill->due_date->isoFormat('D MMMM YYYY') : '-',
            'qr_url' => url("/api/verify/bill/{$bill->bill_number}"),
            'qr_base64' => $qrBase64,
        ];
    }

    /**
     * Generate SK Penghapusan Denda (Surat Keputusan Penghapusan Denda)
     * Based on approved PenaltyWaiver record
     */
    public function generateSKPenghapusanDenda(\App\Models\PenaltyWaiver $waiver)
    {
        $waiver->load(['bill.taxpayer', 'bill.retributionType', 'requester', 'approver']);
        $bill = $waiver->bill;

        $basePenalty = (float) $bill->penalty_amount + (float) $bill->fixed_fine_amount + (float) $bill->surcharge_amount;
        $waivedAmount = (float) $bill->waived_penalty_amount;
        $remainingPenalty = max(0, $basePenalty - $waivedAmount);

        $skNumber = 'SK-PHD/' . date('Y') . '/' . str_pad($waiver->id, 4, '0', STR_PAD_LEFT);

        return [
            'document_type' => 'SK Penghapusan Denda',
            'sk_number' => $skNumber,
            'bill_number' => $bill->bill_number,
            'taxpayer_name' => $bill->taxpayer->name ?? 'N/A',
            'taxpayer_address' => $bill->taxpayer->address ?? 'Kota Baubau',
            'taxpayer_nik' => $bill->taxpayer->nik ?? '-',
            'retribution_type' => $bill->retributionType->name ?? 'N/A',
            'period' => $bill->period,
            'bill_amount' => (float) $bill->amount,
            'penalty_details' => [
                'bunga' => (float) $bill->penalty_amount,
                'denda_tetap' => (float) $bill->fixed_fine_amount,
                'kenaikan' => (float) $bill->surcharge_amount,
                'total_denda' => $basePenalty,
            ],
            'waiver_details' => [
                'reduction_type' => $waiver->reduction_type,
                'reduction_value' => (float) $waiver->reduction_value,
                'waived_amount' => $waivedAmount,
                'remaining_penalty' => $remainingPenalty,
            ],
            'reason' => $waiver->reason,
            'approval_notes' => $waiver->approval_notes,
            'requested_by' => $waiver->requester->name ?? 'N/A',
            'approved_by' => $waiver->approver->name ?? 'N/A',
            'approved_at' => $waiver->updated_at->translatedFormat('d F Y'),
            'terbilang_waived' => self::terbilang($waivedAmount) . ' Rupiah',
            'total_after_waiver' => (float) $bill->amount + $remainingPenalty,
            'terbilang_total' => self::terbilang((float) $bill->amount + $remainingPenalty) . ' Rupiah',
            'qr_url' => url("/api/verify/waiver/{$skNumber}"),
        ];
    }

    /**
     * Generate SKT (Surat Keterangan Terdaftar)
     * Issued when taxpayer first registers with NPWPD
     */
    public function generateSKT(\App\Models\Taxpayer $taxpayer)
    {
        $taxpayer->load('opd');
        $sktNumber = 'SKT/' . date('Y') . '/' . str_pad($taxpayer->id, 5, '0', STR_PAD_LEFT);

        return [
            'document_type' => 'Surat Keterangan Terdaftar',
            'skt_number' => $sktNumber,
            'npwpd' => $taxpayer->npwpd ?? 'NPWPD-' . str_pad($taxpayer->id, 6, '0', STR_PAD_LEFT),
            'taxpayer_name' => $taxpayer->name,
            'taxpayer_nik' => $taxpayer->nik ?? '-',
            'taxpayer_address' => $taxpayer->address ?? 'Kota Baubau',
            'taxpayer_phone' => $taxpayer->phone ?? '-',
            'opd_name' => $taxpayer->opd->name ?? 'BAPENDA Kota Baubau',
            'registered_at' => $taxpayer->created_at->translatedFormat('d F Y'),
            'tax_objects_count' => $taxpayer->taxObjects()->count(),
            'qr_url' => url("/api/verify/skt/{$sktNumber}"),
        ];
    }

    /**
     * Generate SKPDKBT (Surat Ketetapan Pajak Daerah Kurang Bayar Tambahan)
     * Issued when audit finds additional underpayment after initial SKPDKB
     */
    public function generateSKPDKBT(Bill $bill, float $additionalAmount, string $auditNotes = '')
    {
        $bill->load(['taxpayer', 'retributionType', 'taxObject']);
        $skNumber = 'SKPDKBT/' . date('Y') . '/' . str_pad($bill->id, 5, '0', STR_PAD_LEFT);
        $total = (float) $bill->amount + $additionalAmount;

        return [
            'document_type' => 'Surat Ketetapan Pajak Daerah Kurang Bayar Tambahan',
            'sk_number' => $skNumber,
            'bill_number' => $bill->bill_number,
            'taxpayer_name' => $bill->taxpayer->name ?? 'N/A',
            'taxpayer_address' => $bill->taxpayer->address ?? 'Kota Baubau',
            'retribution_type' => $bill->retributionType->name ?? 'N/A',
            'period' => $bill->period,
            'original_amount' => (float) $bill->amount,
            'additional_amount' => $additionalAmount,
            'total_amount' => $total,
            'terbilang' => self::terbilang($total) . ' Rupiah',
            'audit_notes' => $auditNotes,
            'due_date' => now()->addDays(30)->translatedFormat('d F Y'),
            'qr_url' => url("/api/verify/bill/{$bill->bill_number}"),
        ];
    }

    /**
     * Generate SKPDN (Surat Ketetapan Pajak Daerah Nihil)
     * Issued when audit confirms no tax liability or payment is correct
     */
    public function generateSKPDN(Bill $bill, string $auditNotes = '')
    {
        $bill->load(['taxpayer', 'retributionType']);
        $skNumber = 'SKPDN/' . date('Y') . '/' . str_pad($bill->id, 5, '0', STR_PAD_LEFT);

        return [
            'document_type' => 'Surat Ketetapan Pajak Daerah Nihil',
            'sk_number' => $skNumber,
            'bill_number' => $bill->bill_number,
            'taxpayer_name' => $bill->taxpayer->name ?? 'N/A',
            'taxpayer_address' => $bill->taxpayer->address ?? 'Kota Baubau',
            'retribution_type' => $bill->retributionType->name ?? 'N/A',
            'period' => $bill->period,
            'amount' => (float) $bill->amount,
            'status' => 'NIHIL',
            'audit_notes' => $auditNotes,
            'issued_at' => now()->translatedFormat('d F Y'),
            'qr_url' => url("/api/verify/bill/{$bill->bill_number}"),
        ];
    }

    /**
     * Generate SSRD (Surat Setoran Retribusi Daerah)
     * Payment receipt for retribution (non-tax) - mirrors SSPD for retribusi
     */
    public function generateSSRD(Bill $bill)
    {
        if ($bill->status !== 'paid' && $bill->status !== 'lunas') {
            throw new \Exception("Hanya tagihan LUNAS yang bisa mencetak SSRD.");
        }

        $bill->load(['taxpayer', 'retributionType', 'payments']);
        $total = (float) $bill->amount + (float) $bill->penalty_amount + (float) $bill->fixed_fine_amount + (float) $bill->surcharge_amount;

        return [
            'document_type' => 'Surat Setoran Retribusi Daerah',
            'number' => $bill->bill_number,
            'title' => $bill->retributionType->name ?? 'Retribusi',
            'taxpayer' => $bill->taxpayer->name ?? 'N/A',
            'address' => $bill->taxpayer->address ?? 'Kota Baubau',
            'amount' => (float) $bill->amount,
            'penalty_amount' => (float) $bill->penalty_amount,
            'fixed_fine_amount' => (float) $bill->fixed_fine_amount,
            'surcharge_amount' => (float) $bill->surcharge_amount,
            'total_amount' => $total,
            'terbilang' => self::terbilang($total) . ' Rupiah',
            'period' => $bill->period,
            'paid_at' => $bill->payments->first()->paid_at ?? now(),
            'payment_method' => $bill->payments->first()->payment_method ?? 'CASH',
            'qr_url' => url("/api/verify/payment/{$bill->bill_number}"),
        ];
    }

    /**
     * Generate STRD (Surat Tagihan Retribusi Daerah)
     * Demand letter for overdue retribution payments - mirrors STPD for retribusi
     */
    public function generateSTRD(Bill $bill)
    {
        $bill->load(['taxpayer', 'retributionType']);
        $strdNumber = 'STRD/' . date('Y') . '/' . str_pad($bill->id, 5, '0', STR_PAD_LEFT);

        $total = (float) $bill->amount + (float) $bill->penalty_amount + (float) $bill->fixed_fine_amount + (float) $bill->surcharge_amount;
        $monthsLate = $bill->due_date ? max(1, $bill->due_date->diffInMonths(now())) : 0;

        return [
            'document_type' => 'Surat Tagihan Retribusi Daerah',
            'strd_number' => $strdNumber,
            'bill_number' => $bill->bill_number,
            'title' => $bill->retributionType->name ?? 'Retribusi',
            'taxpayer' => $bill->taxpayer->name ?? 'N/A',
            'address' => $bill->taxpayer->address ?? 'Kota Baubau',
            'amount' => (float) $bill->amount,
            'penalty_amount' => (float) $bill->penalty_amount,
            'total_amount' => $total,
            'terbilang' => self::terbilang($total) . ' Rupiah',
            'period' => $bill->period,
            'due_date' => $bill->due_date?->translatedFormat('d F Y') ?? '-',
            'months_late' => $monthsLate,
            'payment_deadline' => now()->addDays(14)->translatedFormat('d F Y'),
            'qr_url' => url("/api/verify/bill/{$bill->bill_number}"),
        ];
    }

    /**
     * Generate SPMP (Surat Perintah Melaksanakan Penyitaan)
     * Asset seizure order - final enforcement step
     */
    public function generateSPMP(EnforcementNotice $notice)
    {
        $notice->load(['taxObject.taxpayer', 'auditor']);
        $spmpNumber = 'SPMP/' . date('Y') . '/' . str_pad($notice->id, 5, '0', STR_PAD_LEFT);

        $taxpayer = $notice->taxObject->taxpayer ?? null;

        return [
            'document_type' => 'Surat Perintah Melaksanakan Penyitaan',
            'spmp_number' => $spmpNumber,
            'enforcement_number' => $notice->number,
            'taxpayer_name' => $taxpayer->name ?? 'N/A',
            'taxpayer_nik' => $taxpayer->nik ?? '-',
            'taxpayer_address' => $taxpayer->address ?? 'Kota Baubau',
            'tax_object' => $notice->taxObject->name ?? 'N/A',
            'object_address' => $notice->taxObject->address ?? '-',
            'deficit_amount' => (float) $notice->deficit_amount,
            'terbilang' => self::terbilang((float) $notice->deficit_amount) . ' Rupiah',
            'notes' => $notice->notes,
            'auditor_name' => $notice->auditor->name ?? 'N/A',
            'issued_at' => now()->translatedFormat('d F Y'),
            'qr_url' => url("/api/verify/spmp/{$spmpNumber}"),
        ];
    }

    /**
     * Generate LKOK (Lembar Kerja Objek Khusus)
     * Special assessment worksheet for complex/unique tax objects
     */
    public function generateLKOK(\App\Models\TaxObject $taxObject)
    {
        $taxObject->load(['taxpayer', 'retributionType', 'classification', 'zone']);
        $lkokNumber = 'LKOK/' . date('Y') . '/' . str_pad($taxObject->id, 5, '0', STR_PAD_LEFT);
        $metadata = $taxObject->metadata ?? [];

        return [
            'document_type' => 'Lembar Kerja Objek Khusus',
            'lkok_number' => $lkokNumber,
            'taxpayer_name' => $taxObject->taxpayer->name ?? 'N/A',
            'object_name' => $taxObject->name,
            'object_address' => $taxObject->address ?? '-',
            'retribution_type' => $taxObject->retributionType->name ?? 'N/A',
            'classification' => $taxObject->classification->name ?? '-',
            'zone' => $taxObject->zone->name ?? '-',
            'coordinates' => [
                'latitude' => $taxObject->latitude ?? null,
                'longitude' => $taxObject->longitude ?? null,
            ],
            'metadata' => $metadata,
            'status' => $taxObject->status,
            'audit_status' => $taxObject->audit_status ?? 'pending',
            'registered_at' => $taxObject->created_at->translatedFormat('d F Y'),
            'assessed_at' => now()->translatedFormat('d F Y'),
            'qr_url' => url("/api/verify/lkok/{$lkokNumber}"),
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
