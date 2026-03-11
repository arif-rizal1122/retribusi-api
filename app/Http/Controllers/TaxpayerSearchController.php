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
        $taxpayers = Taxpayer::where('nik', $nik)
            ->with(['opd', 'retributionTypes', 'retributionClassifications'])
            ->get();

        if ($taxpayers->isEmpty()) {
            return response()->json([
                'message' => 'Data wajib pajak belum terdaftar',
                'found' => false,
                'count' => 0,
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => 'Data wajib pajak ditemukan',
            'found' => true,
            'debug_check' => 'V3',
            'count' => $taxpayers->count(),
            'data' => $taxpayers->first(), // For backward compatibility with simpler auto-fill
            'all_assets' => $taxpayers // The full list for multi-asset lookup
        ]);
    }
}
