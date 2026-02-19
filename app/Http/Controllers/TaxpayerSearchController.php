<?php

namespace App\Http\Controllers;

use App\Models\Taxpayer;
use Illuminate\Http\Request;

class TaxpayerSearchController extends Controller
{
    /**
     * Search taxpayer by NIK
     */
    public function searchByNik(Request $request, $nik)
    {
        $taxpayer = Taxpayer::where('nik', $nik)
            ->with(['opd', 'retributionTypes', 'retributionClassifications'])
            ->first();

        if (!$taxpayer) {
            return response()->json([
                'message' => 'Data wajib pajak belum terdaftar',
                'found' => false
            ], 200);
        }

        return response()->json([
            'message' => 'Data wajib pajak ditemukan',
            'found' => true,
            'data' => $taxpayer
        ]);
    }
}
