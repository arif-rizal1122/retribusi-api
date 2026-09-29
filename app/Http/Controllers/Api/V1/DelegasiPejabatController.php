<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\OfficialDelegationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * DelegasiPejabatController — kelola delegasi/backup pejabat Kabid & Kasubid.
 *
 * Konteks: role kabid_wilayah_1/2 dan kasubid_* hanya dipegang satu user.
 * Bila pejabat tidak masuk (cuti/sakit/dinas luar), tugas lapangan mandek.
 * Endpoint ini memberi Kabid (dan Kepala Bapenda) cara menunjuk pengganti
 * secara tercatat — bukan lewat ubah role manual di database.
 *
 * Otorisasi: gate 'approve' (Kabid / Kepala Bapenda) untuk tulis;
 *            gate 'verify' (Kasubid ke atas) untuk baca daftar.
 */
class DelegasiPejabatController extends Controller
{
    public function __construct(private OfficialDelegationService $delegasi)
    {
    }

    /** GET /api/bidang-pajak/delegasi/daftar */
    public function daftar(Request $request)
    {
        $user = $request->user();

        $filter = [
            'role'   => $request->query('role'),
            'status' => $request->query('status'),
            'domain' => \App\Support\TaxDomainHelper::getDomain($user) ?: $request->query('domain'),
        ];

        return response()->json([
            'domain_saya' => \App\Support\TaxDomainHelper::getDomain($user) ?: null,
            'jumlah'      => count($this->delegasi->daftar($filter)),
            'data'        => $this->delegasi->daftar($filter),
        ]);
    }

    /** GET /api/bidang-pajak/delegasi/kandidat */
    public function kandidat(Request $request)
    {
        $user = $request->user();
        $domain = \App\Support\TaxDomainHelper::getDomain($user) ?: null;

        if ($domain === null) {
            throw new \InvalidArgumentException('Domain pajak akun Anda tidak dikenali; hubungi admin sistem.');
        }

        return response()->json([
            'domain' => $domain,
            'data'   => $this->delegasi->kandidat($domain),
        ]);
    }

    /** POST /api/bidang-pajak/delegasi */
    public function buat(Request $request)
    {
        $v = $request->validate([
            'from_role'          => 'required|string|max:64',
            'to_user_id'         => 'required|integer|min:1',
            'start_date'         => 'required|date',
            'end_date'           => 'required|date|after_or_equal:start_date',
            'tax_domain'         => 'nullable|string|in:wilayah_1,wilayah_2',
            'reason'             => 'nullable|string|max:500',
            'allow_cross_domain' => 'nullable|boolean',
        ]);

        $user = $request->user();

        // Pejabat hanya boleh mendelegasikan peran di wilayahnya sendiri.
        $domainUser = \App\Support\TaxDomainHelper::getDomain($user) ?: null;
        $domainTarget = $v['tax_domain'] ?? $domainUser;

        if ($domainUser && $domainTarget && $domainUser !== $domainTarget
            && empty($v['allow_cross_domain'])) {
            return response()->json([
                'message' => "Anda berdomisili {$domainUser}; tidak boleh mendelegasikan peran wilayah {$domainTarget}.",
            ], 403);
        }

        try {
            $id = $this->delegasi->grant(
                fromRole: $v['from_role'],
                toUserId: (int) $v['to_user_id'],
                startDate: $v['start_date'],
                endDate: $v['end_date'],
                domain: $domainTarget,
                grantedBy: (int) $user->id,
                reason: $v['reason'] ?? null,
                allowCrossDomain: (bool) ($v['allow_cross_domain'] ?? false),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Delegasi pejabat berhasil dibuat dan langsung berlaku pada rentang tanggal tersebut.',
            'id'      => $id,
            'data'    => collect($this->delegasi->daftar([]))->firstWhere('id', $id),
        ], 201);
    }

    /** POST /api/bidang-pajak/delegasi/{id}/cabut */
    public function cabut(Request $request, int $id)
    {
        $v = $request->validate(['alasan' => 'nullable|string|max:500']);

        try {
            $this->delegasi->revoke($id, (int) $request->user()->id, $v['alasan'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Delegasi dicabut. Penugasan kembali ke pemegang jabatan tetap.',
            'id'      => $id,
        ]);
    }
}
