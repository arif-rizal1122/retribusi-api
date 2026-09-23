<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Allow only internal staff roles with admin-level access
        if (!auth()->check() || !($user instanceof \App\Models\User)) {
            return response()->json([
                'message' => 'Forbidden: Internal staff access required.'
            ], 403);
        }

        // Daftar role aparatur diambil dari RoleResolver (satu sumber kebenaran).
        // Sebelumnya daftar ini di-hardcode di sini, sehingga setiap role baru
        // (mis. kabid_wilayah_1/2, kasubid_pendataan_wil1/2) otomatis tertolak
        // 403 sampai middleware ini ikut diedit — sumber bug berulang.
        $allowedRoles = array_merge(
            array_keys(\App\Support\RoleResolver::CORE_ROLE_GROUPS),
            array_keys(\App\Support\RoleResolver::ROLE_MATRIX),
            array_keys(\App\Support\RoleResolver::ROLE_ALIASES),
            [\App\Models\User::ROLE_WALIKOTA]
        );

        // Role warga/citizen TIDAK boleh masuk — endpoint aparatur tertutup untuk mereka.
        $wargaRoles = ['warga', 'citizen', 'wajib_pajak'];
        $allowedRoles = array_values(array_diff(array_unique($allowedRoles), $wargaRoles));

        if (!in_array($user->role, $allowedRoles, true)) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required role to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}
