<?php

namespace App\Http\Controllers;

use App\Models\BusinessUnit;
use Illuminate\Http\Request;

/**
 * M36 — Deteksi Titik Adopsi Kalkulator Bisnis (9 Agu 2026)
 * Dashboard cakupan: list titik/lapak yang sudah ter-M-PAD-kan + ringkasan status.
 */
class BusinessUnitController extends Controller
{
    public function index(Request $request)
    {
        $query = BusinessUnit::with('taxpayer:id,name,nik');

        if ($request->has('status') && in_array($request->status, ['aktif', 'belum', 'nonaktif'])) {
            $query->where('status', $request->status);
        }

        if ($request->has('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('business_name', 'like', "%{$q}%")
                    ->orWhere('location_ref', 'like', "%{$q}%");
            });
        }

        $items = $query->latest('first_transaction_at')->paginate($request->get('per_page', 15));

        $summary = [
            'total' => BusinessUnit::count(),
            'aktif' => BusinessUnit::where('status', 'aktif')->count(),
            'belum' => BusinessUnit::where('status', 'belum')->count(),
            'nonaktif' => BusinessUnit::where('status', 'nonaktif')->count(),
        ];

        return response()->json([
            'data' => $items,
            'summary' => $summary,
        ]);
    }
}
