<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AutoAssignService;
use App\Support\ServiceAccessMatrix;
use App\Support\TaxDomainHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * PenugasanPetugasController — master "Kabid menetapkan petugas x layanan".
 *
 * Ini BUKAN menu baru: endpoint ini melengkapi rangka RBAC berbasis layanan
 * di BidangPajakController. Bedanya:
 *   - BidangPajakController : menugaskan SATU pekerjaan (per objek)
 *   - controller ini         : menetapkan SEKALI siapa memegang layanan apa,
 *                              lalu AutoAssignService memakainya berulang
 *
 * Kewenangan: KABID saja (route digate `service_action:assign`).
 * Domain wilayah dijaga TaxDomainHelper — Kabid Wilayah I tidak bisa
 * menetapkan petugas untuk layanan Wilayah II.
 *
 * Aturan pengelola 24 Sep 2026:
 *  - Semua layanan domain SELALU ditampilkan, termasuk yang belum ada petugas.
 *  - Petugas dengan frekuensi 0 tetap tampil dengan nilai 0 (jadi prioritas).
 *  - Nonaktifkan = soft (is_active=false + revoked_at). Tidak pernah menghapus.
 */
class PenugasanPetugasController extends Controller
{
    public function __construct(private AutoAssignService $autoAssign) {}

    /** Konteks akun: role, layanan, domain. */
    private function ctx(Request $request): array
    {
        $user = $request->user();
        $role = $user->role ?? null;

        return [
            'user'     => $user,
            'role'     => $role,
            'profile'  => ServiceAccessMatrix::profileFor($role),
            'services' => ServiceAccessMatrix::servicesForRole($role),
            'domain'   => TaxDomainHelper::getDomain($user),
        ];
    }

    /**
     * GET /api/bidang-pajak/petugas-layanan
     *
     * Daftar SEMUA layanan dalam domain Kabid + petugas yang memegangnya.
     * Layanan tanpa petugas TETAP muncul (perlu_penetapan=true).
     */
    public function daftar(Request $request)
    {
        $c = $this->ctx($request);
        $domain = $c['domain'];

        $layanan = $this->autoAssign->daftarLayananDenganPetugas($domain);

        // Pembatasan tambahan: bila Kabid hanya berwenang atas sebagian layanan
        // (ServiceAccessMatrix), sembunyikan yang di luar tupoksinya.
        if (! empty($c['services'])) {
            $kodeDiizinkan = $c['services'];
            $layanan = array_values(array_filter(
                $layanan,
                fn ($l) => in_array($l['kode'], $kodeDiizinkan, true)
            ));
        }

        $totalPetugas = 0;
        foreach ($layanan as $l) {
            $totalPetugas += $l['jumlah_petugas'];
        }

        return response()->json([
            'peran'   => $c['profile']['group'] ?? null,
            'domain'  => $domain,
            'layanan_anda' => $c['services'],
            'ringkasan' => [
                'jumlah_layanan'        => count($layanan),
                'layanan_ada_petugas'   => count(array_filter($layanan, fn ($l) => $l['ada_petugas'])),
                'layanan_perlu_penetapan' => count(array_filter($layanan, fn ($l) => $l['perlu_penetapan'])),
                'total_penugasan_petugas' => $totalPetugas,
            ],
            'data' => $layanan,
        ]);
    }

    /**
     * GET /api/bidang-pajak/petugas-layanan/petugas
     *
     * Daftar petugas + layanan yang dipegang + frekuensi bulan ini + beban aktif.
     * Petugas dengan frekuensi 0 tetap tampil (prioritas=true).
     */
    public function daftarPetugas(Request $request)
    {
        $c = $this->ctx($request);

        $daftar = $this->autoAssign->daftarPetugasDenganBeban($c['domain']);

        // Batasi ke layanan dalam tupoksi Kabid ini.
        if (! empty($c['services'])) {
            $kode = $c['services'];
            $daftar = array_map(function ($p) use ($kode) {
                $p['layanan'] = array_values(array_filter(
                    $p['layanan'],
                    fn ($l) => in_array($l['kode'], $kode, true)
                ));
                $p['jumlah_layanan'] = count($p['layanan']);

                return $p;
            }, $daftar);
            $daftar = array_values(array_filter($daftar, fn ($p) => $p['jumlah_layanan'] > 0));
        }

        return response()->json([
            'domain'  => $c['domain'],
            'jumlah'  => count($daftar),
            'catatan' => 'Frekuensi 0 tetap ditampilkan: menandakan petugas paling '
                .'jarang ditugaskan bulan ini sehingga menjadi prioritas.',
            'data'    => $daftar,
        ]);
    }

    /**
     * POST /api/bidang-pajak/petugas-layanan/tetapkan
     *
     * Kabid menetapkan satu petugas memegang satu/lebih layanan (bulk).
     * Idempotent: kirim daftar yang sama tidak menggandakan baris; baris yang
     * pernah dinonaktifkan akan diaktifkan kembali (bukan insert baru).
     */
    public function tetapkan(Request $request)
    {
        $c = $this->ctx($request);

        $validated = $request->validate([
            'user_id'            => 'required|integer|exists:users,id',
            'classification_ids' => 'required|array|min:1',
            'classification_ids.*' => 'integer',
        ]);

        // 1) Penerima harus pegawai OPD yang sama.
        $petugas = \App\Models\User::find($validated['user_id']);
        if (! $petugas || (int) $petugas->opd_id !== (int) $c['user']->opd_id) {
            return response()->json([
                'message' => 'Petugas harus pegawai pada OPD yang sama.',
            ], 422);
        }

        if ($petugas->role !== 'petugas') {
            return response()->json([
                'message' => 'Akun yang dipilih bukan role petugas.',
                'role_petugas_terpilih' => $petugas->role,
            ], 422);
        }

        // 2) Semua layanan yang dikirim HARUS ada di domain Kabid ini.
        //    Ini penjaga utama: Kabid Wilayah II tidak boleh menetapkan
        //    petugas untuk layanan Wilayah I.
        $idDomain = $this->autoAssign->klasifikasiDomain($c['domain']);
        if ($idDomain === null) {
            // domain 'all' -> pakai gabungan domain pajak daerah yang dikenal
            $idDomain = [];
            foreach (TaxDomainHelper::DOMAIN_CONFIG as $cfg) {
                foreach ($cfg['classification_ids'] ?? [] as $cid) {
                    $idDomain[] = $cid;
                }
            }
            $idDomain = array_values(array_unique($idDomain));
        }

        $diminta = array_map('intval', $validated['classification_ids']);
        $diLuarWewenang = array_values(array_diff($diminta, $idDomain));

        if (! empty($diLuarWewenang)) {
            return response()->json([
                'message' => 'Ada layanan di luar wewenang wilayah Anda.',
                'wilayah_anda'        => $c['domain'],
                'layanan_ditolak'     => $diLuarWewenang,
                'layanan_diizinkan'   => $idDomain,
            ], 403);
        }

        // 3) Tulis: aktifkan ulang baris lama bila ada, kalau tidak insert baru.
        $hasil = ['diaktifkan_ulang' => [], 'baru' => [], 'sudah_ada' => []];

        DB::transaction(function () use ($diminta, $petugas, $c, &$hasil) {
            foreach ($diminta as $cid) {
                $lama = DB::table('user_retribution_assignments')
                    ->where('user_id', $petugas->id)
                    ->where('retribution_classification_id', $cid)
                    ->first();

                if ($lama) {
                    if ($lama->is_active) {
                        $hasil['sudah_ada'][] = $cid;
                        continue;
                    }

                    // Aktifkan kembali — BUKAN insert baru (hindari duplikat).
                    DB::table('user_retribution_assignments')
                        ->where('id', $lama->id)
                        ->update([
                            'is_active'           => true,
                            'revoked_at'          => null,
                            'assigned_by_user_id' => $c['user']->id,
                            'updated_at'          => now(),
                        ]);
                    $hasil['diaktifkan_ulang'][] = $cid;
                    continue;
                }

                DB::table('user_retribution_assignments')->insert([
                    'user_id'                     => $petugas->id,
                    'retribution_type_id'         => $this->typeIdUntukKlasifikasi($cid),
                    'retribution_classification_id' => $cid,
                    'assigned_by_user_id'         => $c['user']->id,
                    'is_active'                   => true,
                    'created_at'                  => now(),
                    'updated_at'                  => now(),
                ]);
                $hasil['baru'][] = $cid;
            }
        });

        return response()->json([
            'message' => sprintf(
                'Penetapan disimpan: %d baru, %d diaktifkan ulang, %d sudah ada.',
                count($hasil['baru']),
                count($hasil['diaktifkan_ulang']),
                count($hasil['sudah_ada'])
            ),
            'petugas' => ['user_id' => $petugas->id, 'nama' => $petugas->name],
            'hasil'   => $hasil,
        ], 200);
    }

    /**
     * POST /api/bidang-pajak/petugas-layanan/cabut
     *
     * Lepas seorang petugas dari satu/lebih layanan. SOFT: is_active=false +
     * revoked_at. Baris TIDAK dihapus supaya riwayat tetap utuh.
     */
    public function cabut(Request $request)
    {
        $c = $this->ctx($request);

        $validated = $request->validate([
            'user_id'              => 'required|integer|exists:users,id',
            'classification_ids'   => 'required|array|min:1',
            'classification_ids.*' => 'integer',
            'alasan'               => 'nullable|string|max:500',
        ]);

        $idDomain = $this->autoAssign->klasifikasiDomain($c['domain']) ?? [];
        if ($c['domain'] === TaxDomainHelper::DOMAIN_ALL) {
            $idDomain = [];
            foreach (TaxDomainHelper::DOMAIN_CONFIG as $cfg) {
                foreach ($cfg['classification_ids'] ?? [] as $cid) {
                    $idDomain[] = $cid;
                }
            }
        }

        $diminta = array_map('intval', $validated['classification_ids']);
        $diLuarWewenang = array_values(array_diff($diminta, $idDomain));
        if (! empty($diLuarWewenang)) {
            return response()->json([
                'message' => 'Ada layanan di luar wewenang wilayah Anda.',
                'wilayah_anda'      => $c['domain'],
                'layanan_ditolak'   => $diLuarWewenang,
            ], 403);
        }

        $jumlah = DB::table('user_retribution_assignments')
            ->where('user_id', $validated['user_id'])
            ->whereIn('retribution_classification_id', $diminta)
            ->where('is_active', true)
            ->update([
                'is_active'           => false,
                'revoked_at'          => now(),
                'assigned_by_user_id' => $c['user']->id,
                'updated_at'          => now(),
            ]);

        return response()->json([
            'message' => "Sebanyak {$jumlah} penetapan dicabut (baris tetap tersimpan).",
            'jumlah'  => $jumlah,
            'alasan'  => $validated['alasan'] ?? null,
        ]);
    }

    /** retribution_type_id dari sebuah klasifikasi (kolom wajib NOT NULL). */
    private function typeIdUntukKlasifikasi(int $classificationId): int
    {
        $t = DB::table('retribution_classifications')
            ->where('id', $classificationId)
            ->value('retribution_type_id');

        return (int) ($t ?? 16);
    }
}
