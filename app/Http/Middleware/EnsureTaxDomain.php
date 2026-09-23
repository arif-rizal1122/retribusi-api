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
 * - Role dalam `TaxDomainHelper::CROSS_WILAYAH_ROLES` (admin, super_admin,
 *   kepala_bapenda, kasubid_pengembangan, kasubid_pengawas, dll) → SELALU lolos.
 *   Daftarnya ada di satu tempat agar tidak tercecer seperti daftar hardcoded.
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

        $domain = TaxDomainHelper::getDomain($user);

        // Domain ALL sah HANYA untuk role yang memang bertugas lintas wilayah
        // (lihat TaxDomainHelper::CROSS_WILAYAH_ROLES — rujukan tunggal).
        //
        // Dua kondisi berbeda yang sama-sama menghasilkan domain 'all':
        //   (a) role lintas wilayah yang memang berhak  -> lolos
        //   (b) role wilayah yang metadata-nya KOSONG   -> DITOLAK (fail-closed)
        // Kondisi (b) yang dulu jadi celah: akun Kabid tanpa metadata ikut
        // mendapat 'all' lalu bisa menembus kedua wilayah.
        if ($domain === TaxDomainHelper::DOMAIN_ALL) {
            if (TaxDomainHelper::isCrossWilayahRole($user->role)) {
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
