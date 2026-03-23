<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\EnforcementNotice;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Services\OfficialDocumentService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    protected $docService;

    public function __construct(OfficialDocumentService $docService)
    {
        $this->docService = $docService;
    }

    /**
     * Generate SKT (Surat Keterangan Terdaftar)
     * GET /api/documents/skt/{taxpayerId}
     */
    public function skt($taxpayerId)
    {
        $taxpayer = Taxpayer::findOrFail($taxpayerId);
        $data = $this->docService->generateSKT($taxpayer);
        return $this->docService->renderPDF('pdf.skt', $data, "SKT-{$taxpayer->id}.pdf");
    }

    /**
     * Generate SKRD (Surat Ketetapan Retribusi Daerah)
     * GET /api/documents/skrd/{billId}
     */
    public function skrd($billId)
    {
        $bill = Bill::with(['taxpayer', 'retributionType'])->findOrFail($billId);
        $data = $this->docService->generateSKRD($bill);
        return $this->docService->renderPDF('pdf.skrd', $data, "SKRD-{$bill->bill_number}.pdf");
    }

    /**
     * Generate SKPD (Surat Ketetapan Pajak Daerah)
     * GET /api/documents/skpd/{billId}
     */
    public function skpd($billId)
    {
        $bill = Bill::with(['taxpayer', 'retributionType'])->findOrFail($billId);
        $data = $this->docService->generateSKPD($bill);
        return $this->docService->renderPDF('pdf.skpd', $data, "SKPD-{$bill->bill_number}.pdf");
    }

    /**
     * Generate SSPD (Surat Setoran Pajak Daerah)
     * GET /api/documents/sspd/{billId}
     */
    public function sspd($billId)
    {
        $bill = Bill::with(['taxpayer', 'retributionType', 'payments'])->findOrFail($billId);
        $data = $this->docService->generateSSPD($bill);
        return $this->docService->renderPDF('pdf.sspd', $data, "SSPD-{$bill->bill_number}.pdf");
    }

    /**
     * Generate SSRD (Surat Setoran Retribusi Daerah)
     * GET /api/documents/ssrd/{billId}
     */
    public function ssrd($billId)
    {
        $bill = Bill::with(['taxpayer', 'retributionType', 'payments'])->findOrFail($billId);
        $data = $this->docService->generateSSRD($bill);
        return $this->docService->renderPDF('pdf.ssrd', $data, "SSRD-{$bill->bill_number}.pdf");
    }

    /**
     * Generate SPPT (Surat Pemberitahuan Pajak Terutang - PBB)
     * GET /api/documents/sppt/{billId}
     */
    public function sppt($billId)
    {
        $bill = Bill::findOrFail($billId);
        $data = $this->docService->generateSPPT($bill);
        return $this->docService->renderPDF('pdf.sppt', $data, "SPPT-{$data['nop']}-{$data['year']}.pdf");
    }

    /**
     * Generate SKPDKBT (Kurang Bayar Tambahan)
     * POST /api/documents/skpdkbt/{billId}
     */
    public function skpdkbt(Request $request, $billId)
    {
        $request->validate([
            'additional_amount' => 'required|numeric|min:1',
            'audit_notes' => 'required|string',
        ]);

        $bill = Bill::findOrFail($billId);
        return response()->json([
            'message' => 'SKPDKBT berhasil dibuat',
            'data' => $this->docService->generateSKPDKBT(
                $bill,
                (float) $request->additional_amount,
                $request->audit_notes
            ),
        ]);
    }

    /**
     * Generate SKPDN (Nihil)
     * POST /api/documents/skpdn/{billId}
     */
    public function skpdn(Request $request, $billId)
    {
        $request->validate([
            'audit_notes' => 'required|string',
        ]);

        $bill = Bill::findOrFail($billId);
        $data = $this->docService->generateSKPDN($bill, $request->audit_notes);
        // SKPDN use skrd template with status NIHIL or its own if preferred, but for now we follow the pattern
        return response()->json([
            'message' => 'SKPDN berhasil dibuat (Logic Ready)',
            'data' => $data,
        ]);
    }

    /**
     * Generate STRD (Surat Tagihan Retribusi Daerah)
     * GET /api/documents/strd/{billId}
     */
    public function strd($billId)
    {
        $bill = Bill::with(['taxpayer', 'retributionType'])->findOrFail($billId);
        $data = $this->docService->generateSTRD($bill);
        return $this->docService->renderPDF('pdf.strd', $data, "STRD-{$bill->bill_number}.pdf");
    }

    /**
     * Generate SPMP (Surat Perintah Melaksanakan Penyitaan)
     * GET /api/documents/spmp/{noticeId}
     */
    public function spmp($noticeId)
    {
        $notice = EnforcementNotice::with(['taxObject.taxpayer', 'creator'])->findOrFail($noticeId);
        $data = $this->docService->generateSPMP($notice);
        return $this->docService->renderPDF('pdf.spmp', $data, "SPMP-{$notice->number}.pdf");
    }

    /**
     * Generate LKOK (Lembar Kerja Objek Khusus)
     * GET /api/documents/lkok/{taxObjectId}
     */
    public function lkok($taxObjectId)
    {
        $taxObject = TaxObject::with(['taxpayer', 'retributionType', 'classification', 'zone'])->findOrFail($taxObjectId);
        $data = $this->docService->generateLKOK($taxObject);
        return $this->docService->renderPDF('pdf.lkok', $data, "LKOK-{$taxObject->id}.pdf");
    }

    /**
     * Generate SPP (Surat Perintah Pemeriksaan)
     * GET /api/documents/spp/{noticeId}
     */
    public function spp($noticeId)
    {
        $notice = EnforcementNotice::with(['taxObject.taxpayer', 'taxObject.retributionType'])->findOrFail($noticeId);
        $data = $this->docService->generateSPP($notice);
        return $this->docService->renderPDF('pdf.spp', $data, "SPP-{$notice->number}.pdf");
    }

    /**
     * Generate Surat Teguran
     * GET /api/public/pdf/surat-teguran/{noticeId}
     */
    public function suratTeguran($noticeId)
    {
        $notice = EnforcementNotice::with(['taxObject.taxpayer', 'bill.retributionType'])->findOrFail($noticeId);
        $data = $this->docService->generateTeguran($notice);
        return $this->docService->renderPDF('pdf.teguran', $data, "Teguran-{$notice->number}.pdf");
    }
}
