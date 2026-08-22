<?php

namespace App\Http\Controllers;

use App\Models\BusinessUnit;
use App\Models\Opd;
use App\Models\User;
use App\Support\SectorOpdMappingRegistry;
use Illuminate\Http\Request;

/**
 * M37-2 — Integrasi akun OPD pengelola per sektor usaha (Dok 143).
 *
 * Endpoint GET /api/opd/sector-data (middleware: auth:sanctum + admin + opd_scope).
 * - Role global (super_admin/admin/eksekutif/pengawas/viewer) → seluruh mapping sektor.
 * - Role OPD/petugas → hanya sektor yang diampu oleh opd_id akunnya
 *   (konsisten dengan pola authorizeAssetScope di AdminAssetController).
 */
class OpdSectorDataController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! ($user instanceof User)) {
            return response()->json([
                'message' => 'Forbidden: Internal staff access required.',
            ], 403);
        }

        $scopeOpdId = $request->attributes->get('scope_opd_id');
        $isGlobal   = (bool) $request->attributes->get('is_global_scope', false);

        // Resolve OPD konteks: global → OPD yang diminta via ?opd_id= (atau null);
        // non-global → opd_id akun (dijamin ada oleh EnsureOpdScope, tapi dijaga).
        $contextOpdId = $isGlobal ? $scopeOpdId : (int) ($user->opd_id ?: $scopeOpdId);

        if (! $isGlobal && ! $contextOpdId) {
            return response()->json([
                'message' => 'Forbidden: Akun Anda belum terhubung ke OPD.',
            ], 403);
        }

        $mappings = SectorOpdMappingRegistry::mappings($contextOpdId);

        $sectors = $mappings->map(function ($rows, $sectorId) {
            $opds = $rows->map(fn ($m) => [
                'opd_id'      => (int) $m->opd_id,
                'name'        => $m->opd?->name,
                'code'        => $m->opd?->code,
                'is_primary'  => (bool) $m->is_primary,
                'source_note' => $m->source_note,
            ])->values();

            return [
                'sector_id'       => $sectorId,
                'name'            => SectorOpdMappingRegistry::sectorName($sectorId),
                'default_tax_rate'=> SectorOpdMappingRegistry::defaultTaxRate($sectorId),
                'opd_pengampu'    => $opds,
                'summary'         => [
                    // Fondasi validasi: jumlah unit usaha terdaftar per sektor.
                    // Keterbatasan: alur isi sector_id dari aplikasi belum tersedia (lokal-first).
                    'business_units' => BusinessUnit::where('sector_id', $sectorId)->count(),
                ],
            ];
        })->values();

        $contextOpd = null;
        if ($contextOpdId) {
            $contextOpd = Opd::find($contextOpdId, ['id', 'name', 'code']);
        }

        return response()->json([
            'data' => [
                'scope' => [
                    'is_global' => $isGlobal,
                    'opd'       => $contextOpd,
                ],
                'sectors' => $sectors,
            ],
        ]);
    }
}
