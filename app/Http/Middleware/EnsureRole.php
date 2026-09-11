<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureRole — gate otorisasi berbasis role per-route.
 *
 * Dipakai untuk memisahkan wewenang PELAKSANA (admin) dari PENYETUJU
 * (kepala_bapenda) pada aksi approval/otorisasi bernilai uang atau data.
 *
 * Pemakaian:
 *   Route::post('/.../{id}/approve', [Ctrl::class, 'approve'])
 *        ->middleware('role:kepala_bapenda');
 *
 *   // beberapa role sekaligus
 *   ->middleware('role:admin,kepala_bapenda');
 *
 * Aturan:
 * - `super_admin` (platform/teknis) SELALU lolos, tidak perlu didaftarkan.
 * - Akun warga (Taxpayer) tidak pernah lolos.
 * - Tanpa parameter role → ditolak (fail-closed), supaya salah tulis
 *   tidak berubah jadi "semua boleh".
 *
 * CATATAN: menyembunyikan menu di frontend BUKAN pengamanan. Gate ini adalah
 * penegak sebenarnya; pastikan daftar role di sini sinkron dengan menu FE.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Warga / tamu tidak punya akses agregat maupun approval.
        if (! $user || $user instanceof \App\Models\Taxpayer) {
            return response()->json([
                'message' => 'Forbidden: aksi ini hanya untuk pegawai internal.',
            ], 403);
        }

        // Fail-closed: salah tulis parameter jangan sampai membuka akses.
        if (empty($roles)) {
            return response()->json([
                'message' => 'Forbidden: route ini belum dikonfigurasi role-nya.',
            ], 403);
        }

        // ⚠️ JANGAN pakai $user->isSuperAdmin() di sini.
        // User::isSuperAdmin() mengembalikan true untuk role `admin` JUGA
        // (super_admin || admin), sehingga gate maker-checker jadi bocor:
        // admin pelaksana ikut lolos ke aksi penyetuju. Di middleware ini
        // yang dianggap platform/teknis HANYA role `super_admin` murni.
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Forbidden: aksi ini hanya untuk ' . implode(' / ', $roles)
                . '. Role Anda sekarang: ' . ($user->role ?: 'tidak diketahui') . '.',
            'required_roles' => array_values($roles),
        ], 403);
    }
}
