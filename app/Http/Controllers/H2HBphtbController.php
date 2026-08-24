<?php

namespace App\Http\Controllers;

use App\Models\BpnH2hMapping;
use App\Models\BphtbSubmission;
use App\Services\FormulaParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class H2HBphtbController extends Controller
{
    protected FormulaParserService $formulaService;

    public function __construct(FormulaParserService $formulaService)
    {
        $this->formulaService = $formulaService;
    }

    public function mappings(Request $request)
    {
        $query = BpnH2hMapping::query();
        if ($request->has('search')) {
            $query->where('nib', 'like', "%{$request->search}%")
                  ->orWhere('nop', 'like', "%{$request->search}%");
        }
        return response()->json($query->paginate(15));
    }

    public function simulate(Request $request)
    {
        $request->validate([
            'nib' => 'required|string',
            'npop' => 'required|numeric|min:0',
            'acquisition_type' => 'nullable|string'
        ]);

        $mapping = BpnH2hMapping::where('nib', $request->nib)->first();
        $zntValue = $mapping ? $mapping->znt_value : null;

        $type = $request->acquisition_type ?: 'umum';

        $calculation = $this->formulaService->calculateBPHTB($request->npop, $type, $zntValue);

        return response()->json([
            'mapping_found' => (bool)$mapping,
            'calculation' => $calculation
        ]);
    }

    public function submit(Request $request)
    {
        $request->validate([
            'nib' => 'required|string',
            'npop' => 'required|numeric|min:0',
            'acquisition_type' => 'nullable|string'
        ]);

        $mapping = BpnH2hMapping::where('nib', $request->nib)->first();
        $zntValue = $mapping ? $mapping->znt_value : null;
        $type = $request->acquisition_type ?: 'umum';

        $calculation = $this->formulaService->calculateBPHTB($request->npop, $type, $zntValue);

        $submission = BphtbSubmission::create([
            'nib' => $request->nib,
            'nop' => $mapping ? $mapping->nop : null,
            'ppat_user_id' => $request->user() ? $request->user()->id : null,
            'reported_npop' => $calculation['npop_reported'],
            'znt_applied' => $calculation['znt_applied'],
            'final_npop' => $calculation['final_npop'],
            'status_flag' => $calculation['status_flag'],
            'billing_code' => 'BPHTB-' . strtoupper(Str::random(10)),
        ]);

        return response()->json([
            'message' => 'e-BPHTB berhasil di-submit',
            'data' => $submission,
            'calculation' => $calculation
        ], 201);
    }
}
