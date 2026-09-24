<?php

namespace App\Http\Middleware;

use App\Support\ServiceAccessMatrix;
use App\Support\TaxDomainHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureServiceAction — gate RBAC berbasis LAYANAN.
 *
 * Memisahkan tupoksi Kabid vs Kasubid berdasarkan aksi:
 *   - Kasubid: memeriksa data untuk VERIFIKASI (verify)
 *   - Kabid  : melakukan PENUGASAN & otorisasi (assign / approve)
 *
 * Pemakaian:
 *   Route::middleware('service_action:verify')->group(...)   // Kasubid & Kabid
 *   Route::middleware('service_action:assign')->group(...)   // Kabid saja
 *
 * Layanan yang diperiksa ditentukan dari parameter route bila ada
 * (`service` query/route), atau diperiksa lintas semua layanan role.
 *
 * Role di luar matriks layanan (petugas, opd, super_admin) tidak dibatasi
 * middleware ini — gate mereka tetap EnsureAdmin.
 */
class EnsureServiceAction
{
    public function handle(Request $request, Closure $next, string ...$actions): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (empty($actions)) {
            return response()->json([
                'message' => 'Forbidden: route ini belum dikonfigurasi aksi layanannya.',
            ], 403);
        }

        $role = $user->role ?? null;

        // FAIL-CLOSED (dikoreksi 24 Sep 2026).
        // Sebelumnya: role di luar matriks -> return $next($request) == LOLOS.
        // Terbukti bocor: akun `petugas` dapat 200 di seluruh endpoint Kabid.
        // Sekarang hanya pengecualian eksplisit yang lolos; sisanya 403.
        $exempt = ['super_admin', 'admin'];

        if (in_array($role, $exempt, true)) {
            return $next($request);
        }

        if (! ServiceAccessMatrix::isServiceScopedRole($role)) {
            return response()->json([
                'message' => 'Forbidden: akun Anda (role "'.($role ?? 'null').'") tidak terdaftar dalam matriks tupoksi bidang ini.',
                'tindakan' => 'Hubungi administrator untuk pemetaan role yang benar.',
                'your_role' => $role,
            ], 403);
        }

        $services = ServiceAccessMatrix::servicesForRole($role);

        // Bila route menyebut layanan spesifik, periksa hanya layanan itu.
        $requested = $request->route('service') ?? $request->query('service');

        if ($requested !== null && $requested !== '') {
            $services = in_array($requested, $services, true) ? [$requested] : [];
        }

        if (empty($services)) {
            return response()->json([
                'message' => 'Forbidden: akun Anda belum dipetakan ke layanan mana pun '
                    .'pada bidang ini. Hubungi administrator.',
            ], 403);
        }

        foreach ($actions as $action) {
            foreach ($services as $code) {
                if (ServiceAccessMatrix::can($role, $code, $action)) {
                    return $next($request);
                }
            }
        }

        return response()->json([
            'message' => 'Forbidden: aksi "'.implode(' / ', $actions).'" di luar tupoksi '
                .'jabatan Anda. Kasubid memeriksa data untuk verifikasi; '
                .'penugasan dan otorisasi dilakukan Kepala Bidang.',
            'your_role' => $role,
            'your_services' => ServiceAccessMatrix::servicesForRole($role),
        ], 403);
    }
}
