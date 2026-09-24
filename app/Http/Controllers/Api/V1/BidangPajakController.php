<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SlaTrackerService;
use App\Support\ServiceAccessMatrix;
use App\Support\TaxDomainHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * BidangPajakController — endpoint RBAC berbasis layanan.
 *
 * Memisahkan dua tupoksi inti (lihat ServiceAccessMatrix):
 *   - KASUBID : memeriksa data untuk VERIFIKASI (maker/checker L1-L2)
 *   - KABID   : melakukan PENUGASAN & otorisasi akhir (L3)
 *
 * Gate akses dipasang di level route (`service_action:verify` /
 * `service_action:assign`); controller ini menyaring data sesuai domain.
 */
class BidangPajakController extends Controller
{
    /** Profil akses + layanan yang menjadi tanggung jawab akun ini. */
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
            'typeIds'  => TaxDomainHelper::getRetributionTypeIds(TaxDomainHelper::getDomain($user)),
        ];
    }

    /**
     * GET /api/bidang-pajak/verifikasi/daftar
     *
     * Daftar data yang menunggu PEMERIKSAAN (tupoksi Kasubid).
     * Hanya layanan dalam tanggung jawab akun; sudah tersaring domain wilayah.
     */
    public function daftarVerifikasi(Request $request)
    {
        $c = $this->ctx($request);

        // Permohonan perubahan/pembetulan data objek yang masih pending.
        $q = DB::table('tax_object_change_requests')
            ->select('id', 'tax_object_id', 'taxpayer_id', 'status', 'created_at')
            ->where('status', 'pending');

        // Batasi ke objek pajak dalam domain wilayah akun.
        if ($c['domain'] !== TaxDomainHelper::DOMAIN_ALL && ! empty($c['typeIds'])) {
            $q->whereIn('tax_object_id', function ($sub) use ($c) {
                $sub->select('id')
                    ->from('tax_objects')
                    ->whereIn('retribution_classification_id', $c['typeIds']);
            });
        }

        $rows = $q->orderBy('created_at')->limit(100)->get();

        return response()->json([
            'peran'     => $c['profile']['group'] ?? null,
            'domain'    => $c['domain'],
            'layanan'   => $c['services'],
            'jumlah'    => $rows->count(),
            'data'      => $rows,
        ]);
    }

    /**
     * GET /api/bidang-pajak/verifikasi/{id}
     * Detail satu permohonan untuk diperiksa (Kasubid).
     */
    public function detailVerifikasi(Request $request, int $id)
    {
        $c = $this->ctx($request);

        $row = DB::table('tax_object_change_requests')->where('id', $id)->first();
        if (! $row) {
            return response()->json(['message' => 'Permohonan tidak ditemukan.'], 404);
        }

        // Jaga domain: objek harus berada dalam wilayah akun.
        $obj = DB::table('tax_objects')->where('id', $row->tax_object_id)->first();

        return response()->json([
            'peran'       => $c['profile']['group'] ?? null,
            'permohonan'  => $row,
            'objek'       => $obj,
            'layanan'     => $c['services'],
        ]);
    }

    /**
     * POST /api/bidang-pajak/verifikasi/{id}/periksa
     *
     * Kasubid mencatat hasil pemeriksaan. Keputusan akhir (approve/reject)
     * TIDAK di sini — itu kewenangan Kabid. Kasubid mengubah status ke
     * 'diperiksa' (PRE_APPROVED_L2) lalu dilanjutkan ke Kabid.
     */
    public function periksaVerifikasi(Request $request, int $id)
    {
        $c = $this->ctx($request);
        $user = $c['user'];

        $validated = $request->validate([
            'temuan' => 'nullable|string|max:2000',
            'sesuai' => 'required|boolean',
        ]);

        $row = DB::table('tax_object_change_requests')->where('id', $id)->first();
        if (! $row) {
            return response()->json(['message' => 'Permohonan tidak ditemukan.'], 404);
        }

        if ($row->status !== 'pending') {
            return response()->json([
                'message' => 'Permohonan sudah diperiksa sebelumnya (status: '.$row->status.').',
            ], 409);
        }

        // Status antara L2 — agar Kabid dapat membedakan mana yang sudah
        // diperiksa Kasubid dan mana yang belum. Sebelumnya status tetap
        // 'pending' sehingga antrean Kabid tidak pernah terisi.
        $statusL2 = $validated['sesuai']
            ? 'PRE_APPROVED_L2'
            : 'PERLU_PERBAIKAN_L2';

        DB::table('tax_object_change_requests')->where('id', $id)->update([
            'reviewer_id'  => $user->id,
            'review_notes' => ($validated['temuan'] ?? '')
                ."\n[Diperiksa Kasubid: ".($validated['sesuai'] ? 'DATA SESUAI' : 'PERLU PERBAIKAN')."]",
            'status'       => $statusL2,
            'reviewed_at'  => now(),
            'updated_at'   => now(),
        ]);

        // Pilih tahapan SLA sesuai fokus Kasubid yang memeriksa:
        // Kasubid Penagihan punya jalur tersendiri (tupoksi Penagihan,
        // Verifikasi & Pelaporan), bukan menumpang jalur Pendataan.
        $focus = $c['profile']['focus'] ?? 'pendataan';
        $stage = ($focus === 'penagihan') ? 'VERIFIKASI_PENAGIHAN' : 'VERIFIKASI_BERKAS';

        // Catat jejak SLA tahap verifikasi.
        $slaId = SlaTrackerService::assign($stage, [
            'reference_type'      => 'tax_object_change_request',
            'reference_id'        => $id,
            'tax_domain'          => $c['domain'],
            'assigned_to_user_id' => $user->id,
            'notes'               => 'Pemeriksaan Kasubid selesai ('.$statusL2.'); menunggu penugasan/otorisasi Kepala Bidang.',
        ]);

        return response()->json([
            'message'  => 'Hasil pemeriksaan tersimpan. Menunggu penugasan/otorisasi Kepala Bidang.',
            'status'   => $statusL2,
            'tahapan'  => $stage,
            'sla_id'   => $slaId,
        ]);
    }

    /**
     * GET /api/bidang-pajak/penugasan/daftar
     * Daftar penugasan aktif (Kabid). Termasuk status SLA.
     */
    public function daftarPenugasan(Request $request)
    {
        $c = $this->ctx($request);

        $q = DB::table('service_task_assignments')
            ->whereNull('completed_at')
            ->orderBy('sla_deadline');

        if ($c['domain'] !== TaxDomainHelper::DOMAIN_ALL) {
            $q->where(function ($w) use ($c) {
                $w->where('tax_domain', $c['domain'])->orWhereNull('tax_domain');
            });
        }

        $rows = $q->limit(100)->get();

        return response()->json([
            'peran'   => $c['profile']['group'] ?? null,
            'domain'  => $c['domain'],
            'layanan' => $c['services'],
            'ringkasan_sla' => SlaTrackerService::summary(
                $c['domain'] === TaxDomainHelper::DOMAIN_ALL ? null : $c['domain']
            ),
            'jumlah'  => $rows->count(),
            'data'    => $rows,
        ]);
    }

    /**
     * POST /api/bidang-pajak/penugasan
     *
     * Kabid menugaskan pekerjaan. Inilah aksi inti tupoksi Kabid.
     */
    public function buatPenugasan(Request $request)
    {
        $c = $this->ctx($request);

        $validated = $request->validate([
            'tahapan'             => 'required|string',
            'layanan'             => 'nullable|string',
            // OPSIONAL. Bila kosong -> sistem auto-assign memilih petugas paling
            // adil (frekuensi bulan ini terkecil, seri diacak) dari master
            // "petugas x layanan" yang ditetapkan Kabid.
            // Bila diisi -> penugasan manual (kewenangan Kabid).
            'ditugaskan_ke'       => 'nullable|integer|exists:users,id',
            'classification_id'    => 'nullable|integer',
            'reference_type'      => 'nullable|string|max:100',
            'reference_id'        => 'nullable|integer',
            'catatan'             => 'nullable|string|max:2000',
        ]);

        if (! SlaTrackerService::isKnownStage($validated['tahapan'])) {
            return response()->json([
                'message'  => 'Tahapan tidak dikenal.',
                'pilihan'  => array_keys(SlaTrackerService::SLA_MATRIX),
            ], 422);
        }

        // Bila layanan disebut, pastikan masih dalam tupoksi Kabid ini.
        if (! empty($validated['layanan'])) {
            if (! in_array($validated['layanan'], $c['services'], true)) {
                return response()->json([
                    'message' => 'Layanan "'.$validated['layanan'].'" di luar wewenang Anda.',
                    'layanan_anda' => $c['services'],
                ], 403);
            }
        }

        // ── TENTUKAN PENERIMA TUGAS ────────────────────────────────────
        // Dua jalur:
        //   (a) ditugaskan_ke diisi  -> manual, kekuasaan Kabid
        //   (b) ditugaskan_ke kosong -> AUTO-ASSIGN, sistem pilih paling adil
        $autoAssign = app(\App\Services\AutoAssignService::class);
        $target = null;
        $modeAuto = empty($validated['ditugaskan_ke']);
        $alasanAuto = null;

        if (! $modeAuto) {
            // Penerima tugas harus berada di OPD yang sama.
            $target = \App\Models\User::find($validated['ditugaskan_ke']);
            if (! $target || (int) $target->opd_id !== (int) $c['user']->opd_id) {
                return response()->json([
                    'message' => 'Penerima tugas harus pegawai pada OPD yang sama.',
                ], 422);
            }

            // Penerima tugas harus benar-benar berwenang untuk pekerjaan yang
            // ditugaskan. Ada DUA jenis penerima yang sah, dengan bukti
            // kewenangan berbeda:
            //
            //   (1) PEJABAT bidang pajak (Kasubid/Kabid) — buktinya profil
            //       ServiceAccessMatrix + izin ACT_VERIFY atas layanan itu.
            //       Ini jalur verifikasi berjenjang (L2).
            //   (2) PETUGAS lapangan — TIDAK punya profil bidang pajak (role
            //       `petugas` sengaja berada di luar matriks layanan). Bukti
            //       kewenangannya adalah penetapan master petugas x layanan
            //       oleh Kabid (user_retribution_assignments).
            //
            // Tanpa percabangan ini, penugasan manual ke petugas lapangan
            // selalu ditolak — padahal itulah alur kerja yang diminta.
            if (! empty($validated['layanan'])) {
                if ($target->role === 'petugas') {
                    // Wajib sudah ditetapkan Kabid untuk layanan ini.
                    $classId = $validated['classification_id'] ?? null;
                    if ($classId) {
                        $kompeten = DB::table('user_retribution_assignments')
                            ->where('user_id', $target->id)
                            ->where('retribution_classification_id', (int) $classId)
                            ->where('is_active', true)
                            ->exists();

                        if (! $kompeten) {
                            return response()->json([
                                'message' => 'Petugas ini belum ditetapkan untuk layanan tersebut. '
                                    .'Tetapkan lebih dulu lewat POST /api/bidang-pajak/petugas-layanan/tetapkan.',
                                'petugas_id'        => $target->id,
                                'classification_id' => (int) $classId,
                            ], 422);
                        }
                    }
                } else {
                    $peranPenerima = ServiceAccessMatrix::profileFor($target->role);
                    if ($peranPenerima === null) {
                        return response()->json([
                            'message' => 'Penerima tugas bukan pejabat bidang pajak yang berwenang memeriksa layanan.',
                            'role_penerima' => $target->role,
                        ], 422);
                    }

                    if (! ServiceAccessMatrix::can($target->role, $validated['layanan'], ServiceAccessMatrix::ACT_VERIFY)) {
                        return response()->json([
                            'message' => 'Penerima tugas tidak berwenang memeriksa layanan "'.$validated['layanan'].'".',
                            'role_penerima'  => $target->role,
                            'layanan_penerima' => ServiceAccessMatrix::servicesForRole($target->role),
                        ], 422);
                    }
                }
            }

            // Domain penerima harus cocok dengan domain penugasan.
            // Petugas lapangan TIDAK punya tax_domain (metadata kosong di
            // produksi) — kewenangan wilayahnya sudah dijamin lewat penetapan
            // master petugas x layanan, jadi jangan ditolak di sini.
            if ($target->role !== 'petugas') {
                $domainPenerima = TaxDomainHelper::getDomain($target);
                if ($c['domain'] !== TaxDomainHelper::DOMAIN_ALL
                    && $domainPenerima !== TaxDomainHelper::DOMAIN_ALL
                    && $domainPenerima !== $c['domain']) {
                    return response()->json([
                        'message'         => 'Penerima tugas berada di wilayah penugasan yang berbeda.',
                        'wilayah_anda'    => $c['domain'],
                        'wilayah_penerima' => $domainPenerima,
                    ], 422);
                }
            }
        } else {
            // ── Jalur auto-assign ──
            // Butuh klasifikasi objek untuk tahu pool petugas yang kompeten.
            $classId = $validated['classification_id'] ?? null;

            if (! $classId && ! empty($validated['reference_type']) && ! empty($validated['reference_id'])) {
                // Turunkan klasifikasi dari objek referensi bila tidak dikirim.
                if ($validated['reference_type'] === 'tax_object') {
                    $classId = DB::table('tax_objects')
                        ->where('id', $validated['reference_id'])
                        ->value('retribution_classification_id');
                }
            }

            if (! $classId) {
                return response()->json([
                    'message' => 'Auto-assign butuh `classification_id` (atau reference_type=tax_object '
                        .'beserta reference_id) untuk menentukan petugas yang kompeten. '
                        .'Alternatif: isi `ditugaskan_ke` untuk penugasan manual.',
                ], 422);
            }

            $pilih = $autoAssign->pilihPetugas((int) $classId);

            if (! $pilih['petugas']) {
                // Keputusan pengelola #3: tidak ada petugas kompeten -> eskalasi,
                // bukan gagal senyap. Kabid harus menetapkan petugas untuk layanan
                // ini lewat POST /bidang-pajak/petugas-layanan/tetapkan.
                return response()->json([
                    'message' => $pilih['alasan'],
                    'butuh_penetapan' => true,
                    'classification_id' => (int) $classId,
                    'tindakan' => 'Tetapkan petugas untuk layanan ini lewat '
                        .'POST /api/bidang-pajak/petugas-layanan/tetapkan, lalu ulangi.',
                ], 409);
            }

            $target = $pilih['petugas'];
            $alasanAuto = $pilih['alasan'];
        }

        $id = SlaTrackerService::assign($validated['tahapan'], [
            'reference_type'      => $validated['reference_type'] ?? null,
            'reference_id'        => $validated['reference_id'] ?? null,
            'tax_domain'          => $c['domain'],
            'tax_sector_code'     => $validated['layanan'] ?? null,
            'assigned_to_user_id' => $target->id,
            'assigned_by_user_id' => $c['user']->id,
            'notes'               => $validated['catatan'] ?? null,
        ]);

        // Jejak ke tabel audit (keputusan pengelola #7).
        $autoAssign->catatLog([
            'assignment_id' => $id,
            'event'         => \App\Services\AutoAssignService::EVENT_ASSIGNED,
            'actor_user_id' => $c['user']->id,
            'to_user_id'    => $target->id,
            'status_to'     => 'PENDING',
            'reason'        => $alasanAuto,
            'meta'          => [
                'mode'              => $modeAuto ? 'auto' : 'manual',
                'classification_id' => $validated['classification_id'] ?? null,
                'layanan'           => $validated['layanan'] ?? null,
                'tax_domain'        => $c['domain'],
            ],
        ]);

        return response()->json([
            'message' => $modeAuto
                ? 'Penugasan dibuat otomatis (sistem memilih petugas paling adil).'
                : 'Penugasan dibuat manual.',
            'id'      => $id,
            'mode'    => $modeAuto ? 'auto' : 'manual',
            'petugas' => [
                'user_id' => $target->id,
                'nama'    => $target->name,
                'role'    => $target->role,
            ],
            'alasan_pemilihan' => $alasanAuto,
        ], 201);
    }

    /**
     * GET /api/bidang-pajak/otorisasi/daftar
     *
     * Antrean permohonan yang sudah DIPERIKSA Kasubid (status PRE_APPROVED_L2)
     * dan menunggu otorisasi akhir Kepala Bidang. Inilah jalur Kasubid -> Kabid
     * yang sebelumnya tidak ada, sehingga Kabid tidak pernah melihat antrean.
     */
    public function daftarOtorisasi(Request $request)
    {
        $c = $this->ctx($request);

        $q = DB::table('tax_object_change_requests')
            ->select('id', 'tax_object_id', 'taxpayer_id', 'status', 'reviewer_id',
                     'review_notes', 'reviewed_at', 'created_at')
            ->where('status', 'PRE_APPROVED_L2')
            ->orderBy('reviewed_at');

        // Batasi ke objek pajak dalam domain wilayah Kabid.
        if ($c['domain'] !== TaxDomainHelper::DOMAIN_ALL && ! empty($c['typeIds'])) {
            $q->whereIn('tax_object_id', function ($sub) use ($c) {
                $sub->select('id')
                    ->from('tax_objects')
                    ->whereIn('retribution_classification_id', $c['typeIds']);
            });
        }

        $rows = $q->limit(100)->get();

        // Nama pemeriksa L2, agar Kabid tahu siapa yang sudah memeriksa.
        $reviewerIds = $rows->pluck('reviewer_id')->filter()->unique()->values();
        $reviewers = $reviewerIds->isEmpty()
            ? collect()
            : DB::table('users')->whereIn('id', $reviewerIds)->pluck('name', 'id');

        $rows = $rows->map(function ($row) use ($reviewers) {
            $row->reviewer_name = $reviewers[$row->reviewer_id] ?? null;
            return $row;
        });

        return response()->json([
            'peran'   => $c['profile']['group'] ?? null,
            'domain'  => $c['domain'],
            'layanan' => $c['services'],
            'jumlah'  => $rows->count(),
            'data'    => $rows,
        ]);
    }

    /**
     * POST /api/bidang-pajak/otorisasi/{id}
     *
     * Kabid memberi keputusan akhir atas permohonan yang sudah diperiksa
     * Kasubid (status PRE_APPROVED_L2).
     */
    public function putuskanOtorisasi(Request $request, int $id)
    {
        $c = $this->ctx($request);

        $validated = $request->validate([
            'keputusan' => 'required|in:setujui,tolak',
            'alasan'    => 'nullable|string|max:2000',
        ]);

        $row = DB::table('tax_object_change_requests')->where('id', $id)->first();
        if (! $row) {
            return response()->json(['message' => 'Permohonan tidak ditemukan.'], 404);
        }

        if ($row->status !== 'PRE_APPROVED_L2') {
            return response()->json([
                'message' => 'Permohonan belum diperiksa Kasubid atau sudah diputuskan (status: '.$row->status.').',
            ], 409);
        }

        // Jaga domain: Kabid hanya memutus dalam wilayahnya.
        if ($c['domain'] !== TaxDomainHelper::DOMAIN_ALL && ! empty($c['typeIds'])) {
            $obj = DB::table('tax_objects')->where('id', $row->tax_object_id)->first();
            $inDomain = $obj && in_array($obj->retribution_classification_id, $c['typeIds'], true);
            if (! $inDomain) {
                return response()->json([
                    'message' => 'Permohonan ini berada di luar wilayah penugasan Anda.',
                ], 403);
            }
        }

        $setuju = $validated['keputusan'] === 'setujui';

        DB::table('tax_object_change_requests')->where('id', $id)->update([
            'status'     => $setuju ? 'approved' : 'rejected',
            'review_notes' => trim((string) $row->review_notes
                ."\n[Otorisasi Kabid: ".($setuju ? 'DISETUJUI' : 'DITOLAK').' - '.$c['user']->name.']'
                .($validated['alasan'] ?? '' ? "\nAlasan: ".$validated['alasan'] : '')),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message'   => $setuju ? 'Permohonan diotorisasi (L3).' : 'Permohonan ditolak (L3).',
            'id'        => $id,
            'status'    => $setuju ? 'approved' : 'rejected',
            'keputusan' => $validated['keputusan'],
            'oleh'      => $c['user']->name,
        ]);
    }

    /**
     * POST /api/bidang-pajak/penugasan/{id}/otorisasi
     *
     * Kabid memberi otorisasi akhir (L3 signer) atas penugasan/permohonan.
     */
    public function otorisasiPenugasan(Request $request, int $id)
    {
        $c = $this->ctx($request);

        $validated = $request->validate([
            'keputusan' => 'required|in:setujui,tolak',
            'alasan'    => 'nullable|string|max:2000',
        ]);

        $row = DB::table('service_task_assignments')->where('id', $id)->first();
        if (! $row) {
            return response()->json(['message' => 'Penugasan tidak ditemukan.'], 404);
        }

        // Jaga domain: Kabid tidak boleh mengotorisasi di luar wilayahnya.
        if ($c['domain'] !== TaxDomainHelper::DOMAIN_ALL
            && $row->tax_domain !== null
            && $row->tax_domain !== $c['domain']) {
            return response()->json([
                'message' => 'Penugasan ini berada di luar wilayah penugasan Anda.',
            ], 403);
        }

        $setuju = $validated['keputusan'] === 'setujui';

        SlaTrackerService::complete(
            $id,
            $setuju ? SlaTrackerService::STATUS_APPROVED : SlaTrackerService::STATUS_REJECTED,
            $setuju ? null : ($validated['alasan'] ?? 'Ditolak oleh Kepala Bidang.')
        );

        return response()->json([
            'message'   => $setuju ? 'Penugasan diotorisasi.' : 'Penugasan ditolak.',
            'id'        => $id,
            'keputusan' => $validated['keputusan'],
            'oleh'      => $c['user']->name,
        ]);
    }
}
