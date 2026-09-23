<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureOpdScope — middleware scope dashboard agregat M-PAD.
 *
 * - Memblokir akun warga/taxpayer (endpoint dashboard adalah internal).
 * - Menentukan cakupan data:
 *     * Admin/super_admin/eksekutif/audit/viewer → global (boleh filter ?opd_id=).
 *     * Role lain (opd, petugas, admin_ptsp, dll) → dibatasi ke opd_id sendiri.
 * - Menyuntikkan atribut request: scope_opd_id, is_global_scope.
 */
class EnsureOpdScope
{
    /** Role yang boleh melihat data global (seluruh OPD). */
    private const GLOBAL_ROLES = [
        'super_admin',
        'admin',
        'walikota',
        'kepala_bapenda',
        'kasubid_pendataan',
        'viewer',
        'pengawas',
        'kabid_pengawas',
        'kasubid_pengawas',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Internal staff only — akun warga (taxpayer) tidak punya akses agregat.
        if (! $user || $user instanceof \App\Models\Taxpayer) {
            return response()->json([
                'message' => 'Forbidden: endpoint dashboard internal (non-warga).',
            ], 403);
        }

        $scopeOpdId = null;
        $isGlobal = true;

        if (in_array($user->role, self::GLOBAL_ROLES, true)) {
            $requested = $request->query('opd_id');
            if ($requested !== null && $requested !== '' && is_numeric($requested)) {
                $scopeOpdId = (int) $requested;
                $isGlobal = false;
            }
        } elseif ($user->opd_id) {
            $scopeOpdId = (int) $user->opd_id;
            $isGlobal = false;
        }

        $request->attributes->set('scope_opd_id', $scopeOpdId);
        $request->attributes->set('is_global_scope', $isGlobal);
        $request->attributes->set('scope_tax_domain', \App\Support\TaxDomainHelper::getDomain($user));

        return $next($request);
    }
}
