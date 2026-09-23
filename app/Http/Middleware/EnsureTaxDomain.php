<?php

namespace App\Http\Middleware;

use App\Models\Taxpayer;
use App\Support\TaxDomainHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureTaxDomain — gate pemisahan tupoksi Bidang Pajak Daerah.
 *
 * Menegakkan isolasi Wilayah I vs Wilayah II pada level ROUTE (bukan hanya
 * level data di TaxDomainHelper). Tanpa ini, domain lock hanya menyaring
 * hasil query; endpoint yang tidak memfilter tetap bisa diakses.
 *
 * Pemakaian:
 *   Route::prefix('wilayah-1')->middleware('tax_domain:wilayah_1')->group(...)
 *   // boleh beberapa domain:
 *   ->middleware('tax_domain:wilayah_1,wilayah_2')
 *
 * Aturan:
 * - `super_admin` dan `admin` berpeta ke DOMAIN_ALL → SELALU lolos
 *   (mereka memang lintas wilayah, lihat TaxDomainHelper::getDomain).
 * - Warga (Taxpayer) tidak pernah lolos gate internal ini.
 * - Parameter kosong → DITOLAK (fail-closed).
 * - User tanpa metadata domain (fallback DOMAIN_ALL) → DITOLAK saat route
 *   wilayah, supaya metadata kosong TIDAK jadi jalan pintas akses lintas
 *   wilayah. Ini menutup pola fail-open yang pernah terjadi pada akun Kabid.
 */
class EnsureTaxDomain
{
    public function handle(Request $request, Closure $next, string ...$domains): Response
    {
        $user = $request->user();

        // Warga / tamu bukan pegawai internal.
        if (! $user || $user instanceof Taxpayer) {
            return response()->json([
                'message' => 'Forbidden: aksi ini hanya untuk pegawai internal.',
            ], 403);
        }

        // Fail-closed: salah tulis parameter jangan membuka akses.
        if (empty($domains)) {
            return response()->json([
                'message' => 'Forbidden: route ini belum dikonfigurasi domain pajaknya.',
            ], 403);
        }

        // Platform/teknis: lintas wilayah.
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        $domain = TaxDomainHelper::getDomain($user);

        // Domain ALL hanya sah untuk role yang memang lintas wilayah
        // (admin / super_admin). Role wilayah yang metadata-nya kosong
        // TIDAK boleh lolos — kalau lolos, itu fail-open.
        if ($domain === TaxDomainHelper::DOMAIN_ALL) {
            if (in_array($user->role, ['admin', 'super_admin'], true)) {
                return $next($request);
            }

            return response()->json([
                'message' => 'Forbidden: akun Anda belum dipetakan ke wilayah pajak mana pun. '
                    .'Hubungi administrator untuk mengisi domain penugasan.',
            ], 403);
        }

        if (in_array($domain, $domains, true)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Forbidden: aksi ini di luar wilayah penugasan Anda ('
                .$domain.'), route ini untuk '.implode(' / ', $domains).'.',
        ], 403);
    }
}
