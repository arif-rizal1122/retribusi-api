<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\TaxObject;
use App\Support\TaxDomainHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * WilayahController — endpoint terpisah per wilayah pajak daerah.
 *
 * Endpoint di sini dipasang middleware `tax_domain:wilayah_X`, jadi:
 * - Akun Wilayah I tidak bisa memanggil endpoint Wilayah II (403).
 * - Akun dengan metadata domain kosong DITOLAK (fail-closed).
 * - admin / super_admin boleh keduanya (lintas wilayah).
 *
 * Query di dalam juga memfilter ulang berdasarkan retribution_type_ids
 * milik domain, sebagai lapis kedua (defence in depth): kalau gate route
 * salah konfigurasi, data tetap tidak bocor.
 */
class WilayahController extends Controller
{
    /**
     * Ambil daftar ID tipe retribusi untuk domain tertentu.
     * Dipakai sebagai filter lapis kedua.
     */
    private function typeIds(string $domain): array
    {
        return TaxDomainHelper::getRetributionTypeIds($domain);
    }

    /**
     * Daftar objek pajak untuk domain tertentu.
     */
    private function objek(Request $request, string $domain): JsonResponse
    {
        $typeIds = $this->typeIds($domain);

        $query = TaxObject::query()
            ->whereIn('retribution_type_id', $typeIds)
            ->orderByDesc('id');

        if ($search = $request->query('q')) {
            $query->where(function ($w) use ($search) {
                $w->where('nop', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->query('per_page', 25), 100);

        return response()->json([
            'domain' => $domain,
            'retribution_type_ids' => $typeIds,
            'data' => $query->paginate($perPage),
        ]);
    }

    /**
     * Daftar tagihan (bills) untuk domain tertentu.
     */
    private function tagihan(Request $request, string $domain): JsonResponse
    {
        $typeIds = $this->typeIds($domain);

        $query = Bill::query()
            ->whereIn('retribution_type_id', $typeIds)
            ->orderByDesc('id');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 25), 100);

        return response()->json([
            'domain' => $domain,
            'retribution_type_ids' => $typeIds,
            'data' => $query->paginate($perPage),
        ]);
    }

    public function objekWilayah1(Request $request): JsonResponse
    {
        return $this->objek($request, TaxDomainHelper::DOMAIN_WILAYAH_1);
    }

    public function tagihanWilayah1(Request $request): JsonResponse
    {
        return $this->tagihan($request, TaxDomainHelper::DOMAIN_WILAYAH_1);
    }

    public function objekWilayah2(Request $request): JsonResponse
    {
        return $this->objek($request, TaxDomainHelper::DOMAIN_WILAYAH_2);
    }

    public function tagihanWilayah2(Request $request): JsonResponse
    {
        return $this->tagihan($request, TaxDomainHelper::DOMAIN_WILAYAH_2);
    }
}
