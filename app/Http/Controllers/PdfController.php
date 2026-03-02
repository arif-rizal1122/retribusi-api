<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Billing;
use App\Models\Enforcement;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfController extends Controller
{
    /**
     * Generate NPWPD (Kartu Wajib Pajak Daerah)
     */
    public function generateNpwpd($id)
    {
        $taxpayer = Taxpayer::with('opd')->findOrFail($id);

        $pdf = Pdf::loadView('pdf.npwpd', compact('taxpayer'))
            ->setPaper([0, 0, 240.94, 153.07], 'landscape'); // ID Card size ~ 85x54mm

        return $pdf->stream('NPWPD_' . $taxpayer->npwpd . '.pdf');
    }

    /**
     * Generate NOPD (Kartu Objek Pajak/Retribusi Daerah)
     */
    public function generateNopd($id)
    {
        $taxObject = TaxObject::with(['taxpayer', 'opd', 'retributionTypes', 'retributionClassifications'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.nopd', ['taxObject' => $taxObject])
            ->setPaper('A4', 'portrait'); // Letter/A4 for Certificate of Object
        return $pdf->download('NOPD-' . $taxObject->id . '.pdf');
    }

    public function generateSkpd($billing_id)
    {
        $billing = Billing::with(['taxObject.taxpayer', 'taxObject.retributionType'])->findOrFail($billing_id);

        $pdf = Pdf::loadView('pdf.skpd', ['billing' => $billing]);
        // Format A4 portrait usually for standard letters
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('SKPD-' . $billing->id . '.pdf');
    }

    public function generateSkrd($billing_id)
    {
        $billing = Billing::with(['taxObject.taxpayer', 'taxObject.retributionType'])->findOrFail($billing_id);

        $pdf = Pdf::loadView('pdf.skrd', ['billing' => $billing]);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('SKRD-' . $billing->id . '.pdf');
    }

    public function generateSuratTeguran($id)
    {
        $enforcement = Enforcement::with('taxObject.taxpayer')->findOrFail($id);

        $pdf = Pdf::loadView('pdf.surat_teguran', ['enforcement' => $enforcement]);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download(str_replace('/', '_', $enforcement->number) . '.pdf');
    }
}
