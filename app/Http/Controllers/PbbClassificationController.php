<?php

namespace App\Http\Controllers;

use App\Models\PbbNjopClassification;
use App\Services\PbbCalculationService;
use Illuminate\Http\Request;

class PbbClassificationController extends Controller
{
    /**
     * List all NJOP classifications, optionally filtered by type.
     * GET /api/pbb/classifications?type=bumi|bangunan
     */
    public function index(Request $request)
    {
        $query = PbbNjopClassification::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('class_code')->get()
        ]);
    }

    /**
     * Get specific classification by type and class_code.
     * GET /api/pbb/classifications/{type}/{code}
     */
    public function showByCode(string $type, string $code)
    {
        $classification = PbbNjopClassification::where('type', $type)
            ->where('class_code', $code)
            ->first();

        if (!$classification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kelas NJOP tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $classification
        ]);
    }

    /**
     * Lookup NJOP class by value per m².
     * POST /api/pbb/lookup-class
     * Body: { "type": "bumi"|"bangunan", "value_per_m2": 5500000 }
     */
    public function lookupByValue(Request $request)
    {
        $request->validate([
            'type' => 'required|in:bumi,bangunan',
            'value_per_m2' => 'required|numeric|min:0',
        ]);

        $service = app(PbbCalculationService::class);
        $cls = $service->findClassByValue($request->type, (float) $request->value_per_m2);

        if (!$cls) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ditemukan kelas NJOP yang sesuai',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $cls,
            'message' => "Kelas {$cls->class_code} — NJOP: Rp " . number_format($cls->njop_value, 0, ',', '.') . "/m²",
        ]);
    }

    /**
     * Calculate PBB based on kelas bumi & bangunan.
     * POST /api/pbb/calculate
     * 
     * Primary flow (user pilih kelas):
     *   { "luas_bumi": 200, "kelas_bumi": "045",
     *     "luas_bangunan": 100, "kelas_bangunan": "015",
     *     "njoptkp": 10000000, "tariff": 0.001 }
     * 
     * Auto flow (user input nilai per m²):
     *   { "luas_bumi": 200, "nilai_bumi_per_m2": 5500000,
     *     "luas_bangunan": 100, "nilai_bangunan_per_m2": 3500000,
     *     "njoptkp": 10000000, "tariff": 0.001 }
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'luas_bumi' => 'required|numeric|min:0',
            'luas_bangunan' => 'required|numeric|min:0',
            'njoptkp' => 'nullable|numeric|min:0',
            'tariff' => 'nullable|numeric|min:0',
        ]);

        $service = app(PbbCalculationService::class);
        $njoptkp = (float) ($request->njoptkp ?? 10000000);
        $tariff = (float) ($request->tariff ?? 0.001);

        // Check which mode: kelas-based (primary) or value-based (auto)
        if ($request->has('kelas_bumi') && $request->has('kelas_bangunan')) {
            // Primary flow: user selected kelas from dropdown
            $result = $service->calculate(
                (float) $request->luas_bumi,
                (string) $request->kelas_bumi,
                (float) $request->luas_bangunan,
                (string) $request->kelas_bangunan,
                $njoptkp,
                $tariff
            );
        } elseif ($request->has('nilai_bumi_per_m2') && $request->has('nilai_bangunan_per_m2')) {
            // Auto flow: lookup kelas from value
            $result = $service->calculateAuto(
                (float) $request->luas_bumi,
                (float) $request->nilai_bumi_per_m2,
                (float) $request->luas_bangunan,
                (float) $request->nilai_bangunan_per_m2,
                $njoptkp,
                $tariff
            );
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Harus menyertakan kelas_bumi & kelas_bangunan, atau nilai_bumi_per_m2 & nilai_bangunan_per_m2',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'data' => $result,
            'formatted' => 'Rp ' . number_format($result['pbb_terhutang'], 0, ',', '.'),
            'message' => 'Estimasi PBB terhutang: Rp ' . number_format($result['pbb_terhutang'], 0, ',', '.'),
        ]);
    }
}
