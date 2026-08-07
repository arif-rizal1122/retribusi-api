<?php

namespace App\Http\Controllers;

use App\Models\NibNopMapping;
use App\Services\BhumiService;
use Illuminate\Http\Request;

class NibNopMappingController extends Controller
{
    protected BhumiService $bhumiService;

    public function __construct(BhumiService $bhumiService)
    {
        $this->bhumiService = $bhumiService;
    }

    /**
     * Daftar mapping NIB–NOP dengan filter status/nib/nop/source.
     */
    public function index(Request $request)
    {
        $query = NibNopMapping::query();

        if ($request->has('status')) {
            $query->where('match_status', $request->status);
        }
        if ($request->has('source')) {
            $query->where('source', $request->source);
        }
        if ($request->has('nib')) {
            $query->where('nib', 'like', "%{$request->nib}%");
        }
        if ($request->has('nop')) {
            $query->where('nop', 'like', "%{$request->nop}%");
        }

        return response()->json($query->orderByDesc('id')->paginate(20));
    }

    /**
     * Entry manual mapping NIB–NOP.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nib'              => 'required|string|size:16|unique:nib_nop_mappings,nib',
            'nop'              => 'nullable|string|size:18',
            'taxpayer_name'    => 'nullable|string|max:255',
            'land_area_m2'     => 'nullable|numeric|min:0',
            'building_area_m2' => 'nullable|numeric|min:0',
            'znt'              => 'nullable|string|max:50',
            'match_status'     => 'nullable|in:pending,matched,conflict,not_found',
            'source'           => 'nullable|in:bhumi,manual',
        ]);

        $mapping = NibNopMapping::create([
            'nib'              => $request->nib,
            'nop'              => $request->nop,
            'taxpayer_name'    => $request->taxpayer_name,
            'land_area_m2'     => $request->land_area_m2,
            'building_area_m2' => $request->building_area_m2,
            'znt'              => $request->znt,
            'match_status'     => $request->match_status ?? 'pending',
            'source'           => $request->source ?? 'manual',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Mapping NIB-NOP berhasil disimpan.',
            'data'    => $mapping,
        ], 201);
    }

    /**
     * Sinkronisasi satu mapping ke BHUMI.
     * Kalau BHUMI not_configured → mapping tetap pending (menunggu PKS), response 200.
     */
    public function syncOne($id)
    {
        $mapping = NibNopMapping::findOrFail($id);

        if (! $this->bhumiService->available()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'BHUMI not configured, mapping menunggu sinkronisasi.',
                'data'    => $mapping->fresh(),
            ], 200);
        }

        $result = $this->bhumiService->matchNibNop($mapping->nib, $mapping->nop);

        if (($result['match'] ?? null) !== null) {
            $match = $result['match'];

            $mapping->nop              = $match['nop'] ?? $mapping->nop;
            $mapping->taxpayer_name    = $match['taxpayer_name'] ?? $mapping->taxpayer_name;
            $mapping->land_area_m2     = $match['land_area_m2'] ?? $mapping->land_area_m2;
            $mapping->building_area_m2 = $match['building_area_m2'] ?? $mapping->building_area_m2;
            $mapping->znt              = $match['znt'] ?? $mapping->znt;
            $mapping->match_status     = 'matched';
        } else {
            $mapping->match_status = 'not_found';
        }

        $mapping->source = 'bhumi';
        $mapping->last_synced_at = now();
        $mapping->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Sinkronisasi mapping selesai.',
            'data'    => $mapping->fresh(),
        ]);
    }

    /**
     * Statistik mapping NIB–NOP.
     */
    public function stats()
    {
        return response()->json([
            'status' => 'success',
            'data'   => [
                'total'         => NibNopMapping::count(),
                'pending'       => NibNopMapping::where('match_status', 'pending')->count(),
                'matched'       => NibNopMapping::where('match_status', 'matched')->count(),
                'conflict'      => NibNopMapping::where('match_status', 'conflict')->count(),
                'not_found'     => NibNopMapping::where('match_status', 'not_found')->count(),
                'source_bhumi'  => NibNopMapping::where('source', 'bhumi')->count(),
                'source_manual' => NibNopMapping::where('source', 'manual')->count(),
            ],
        ]);
    }
}
