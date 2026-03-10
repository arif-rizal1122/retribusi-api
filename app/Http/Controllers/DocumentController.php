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
        return response()->json([
            'message' => 'SKT berhasil dibuat',
            'data' => $this->docService->generateSKT($taxpayer),
        ]);
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
        $bill = Bill::with('payments')->findOrFail($billId);
        return response()->json([
            'message' => 'SSRD berhasil dibuat',
            'data' => $this->docService->generateSSRD($bill),
        ]);
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
        return response()->json([
            'message' => 'SKPDN berhasil dibuat',
            'data' => $this->docService->generateSKPDN($bill, $request->audit_notes),
        ]);
    }

    /**
     * Generate STRD (Surat Tagihan Retribusi Daerah)
     * GET /api/documents/strd/{billId}
     */
    public function strd($billId)
    {
        $bill = Bill::findOrFail($billId);
        return response()->json([
            'message' => 'STRD berhasil dibuat',
            'data' => $this->docService->generateSTRD($bill),
        ]);
    }

    /**
     * Generate SPMP (Surat Perintah Melaksanakan Penyitaan)
     * GET /api/documents/spmp/{noticeId}
     */
    public function spmp($noticeId)
    {
        $notice = EnforcementNotice::findOrFail($noticeId);
        return response()->json([
            'message' => 'SPMP berhasil dibuat',
            'data' => $this->docService->generateSPMP($notice),
        ]);
    }

    /**
     * Generate LKOK (Lembar Kerja Objek Khusus)
     * GET /api/documents/lkok/{taxObjectId}
     */
    public function lkok($taxObjectId)
    {
        $taxObject = TaxObject::findOrFail($taxObjectId);
        return response()->json([
            'message' => 'LKOK berhasil dibuat',
            'data' => $this->docService->generateLKOK($taxObject),
        ]);
    }

    /**
     * Generate SPP (Surat Perintah Pemeriksaan)
     * GET /api/documents/spp/{noticeId}
     */
    public function spp($noticeId)
    {
        $notice = EnforcementNotice::findOrFail($noticeId);
        return response()->json([
            'message' => 'SPP berhasil dibuat',
            'data' => $this->docService->generateSPP($notice),
        ]);
    }
}
