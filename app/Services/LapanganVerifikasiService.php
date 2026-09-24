<?php

namespace App\Services;

use App\Models\User;
use App\Support\TaxDomainHelper;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * LapanganVerifikasiService — alur SERAH-TERIMA pekerjaan lapangan.
 *
 * === Model Maker-Checker (keputusan pengelola 24 Sep 2026) ===
 *
 *   Petugas  : mengerjakan survei, lalu MENGAJUKAN hasil.
 *              Ia TIDAK PERNAH mengklaim "SELESAI" — yang ia nyatakan hanya
 *              "sudah saya kerjakan" (status MENUNGGU_VERIFIKASI).
 *   Kasubid  : memeriksa hasil. Ia yang MEMASTIKAN selesai:
 *                - setuju -> TERVERIFIKASI -> naik ke Kabid
 *                - tolak  -> REVISI + catatan wajib -> kembali ke petugas
 *   Kabid    : konfirmasi akhir / ganti petugas (jalur terpisah).
 *
 * Prinsip yang dijaga:
 *  - Satu objek = satu petugas.
 *  - Petugas tidak bisa memverifikasi pekerjaannya sendiri, dan tidak bisa
 *    menyentuh tugas milik petugas lain.
 *  - TIDAK ADA penghapusan data. Semua perpindahan status tercatat di
 *    service_task_assignment_logs (append-only).
 *  - Tanpa catatan, penolakan tidak sah (fail-closed).
 */
class LapanganVerifikasiService
{
    /** Status yang berarti "petugas sudah mengajukan, menunggu Kasubid". */
    public const AWAITING = SlaTrackerService::STATUS_MENUNGGU_VERIFIKASI;

    /** Fokus Kasubid menentukan jalur SLA yang dipakai. */
    private const STAGE_BY_FOCUS = [
        'penagihan' => 'VERIFIKASI_HASIL_PENAGIHAN',
        'pendataan' => 'VERIFIKASI_HASIL_SURVEI',
    ];

    public function __construct(private AutoAssignService $audit)
    {
    }

    // ==================================================================
    // SISI PETUGAS
    // ==================================================================

    /**
     * Petugas mengajukan hasil pekerjaannya. Status menjadi
     * MENUNGGU_VERIFIKASI — bukan SELESAI.
     *
     * @return array{assignment_id:int,status:string,stage:string}
     */
    public function ajukanHasil(int $assignmentId, int $petugasId, ?string $catatan = null): array
    {
        $row = $this->ambilAssignment($assignmentId);

        // Hanya PIC yang boleh mengajukan. Ini mencegah petugas menyentuh
        // tugas orang lain — bukan sekadar kenyamanan UI.
        if ((int) $row->assigned_to_user_id !== $petugasId) {
            throw new InvalidArgumentException(
                'Anda bukan petugas PIC untuk tugas ini.'
            );
        }

        if ($row->status === self::AWAITING) {
            throw new InvalidArgumentException(
                'Tugas ini sudah diajukan dan sedang menunggu verifikasi Kasubid.'
            );
        }

        if ($row->status === SlaTrackerService::STATUS_TERVERIFIKASI) {
            throw new InvalidArgumentException(
                'Tugas ini sudah diverifikasi Kasubid.'
            );
        }

        if (in_array($row->status, [SlaTrackerService::STATUS_APPROVED, 'CANCELLED'], true)) {
            throw new InvalidArgumentException('Tugas ini sudah ditutup.');
        }

        $stageVerif = $this->stageVerifikasiUntuk($row);

        // Penjaga ganda: hanya pengajuan yang MASIH HIDUP yang menghalangi.
        // Tahap verifikasi milik siklus sebelumnya (mis. setelah pengajuan
        // ditolak dan tugas dikembalikan untuk revisi) sudah tidak relevan —
        // kalau tidak dikecualikan, petugas tidak akan pernah bisa mengajukan
        // ulang setelah direvisi. Status tahap lama ditutup di bawah.
        $sudahAda = DB::table('service_task_assignments')
            ->where('reference_type', $row->reference_type)
            ->where('reference_id', $row->reference_id)
            ->where('stage', $stageVerif)
            ->whereIn('status', [
                self::AWAITING,
                SlaTrackerService::STATUS_IN_PROGRESS,
                SlaTrackerService::STATUS_PENDING,
            ])
            ->exists();

        if ($sudahAda) {
            throw new InvalidArgumentException(
                'Sudah ada pengajuan verifikasi yang menunggu untuk objek ini.'
            );
        }

        $statusLama = $row->status;

        // Tahap verifikasi lama yang menggantung (PENDING/IN_PROGRESS) dari
        // siklus sebelumnya ditutup sebagai REJECTED — bukan dihapus. Jejaknya
        // tetap ada, dan ini yang membuat pengajuan ulang bisa jalan.
        $stageLamaDitutup = DB::table('service_task_assignments')
            ->where('reference_type', $row->reference_type)
            ->where('reference_id', $row->reference_id)
            ->where('stage', $stageVerif)
            ->whereIn('status', [SlaTrackerService::STATUS_PENDING, SlaTrackerService::STATUS_IN_PROGRESS])
            ->get();

        DB::transaction(function () use ($row, $petugasId, $catatan, $stageVerif, $statusLama, $stageLamaDitutup) {
            // Tutup tahap verifikasi siklus sebelumnya (soft, tidak dihapus).
            foreach ($stageLamaDitutup as $lama) {
                SlaTrackerService::complete(
                    (int) $lama->id,
                    SlaTrackerService::STATUS_REJECTED,
                    'Siklus verifikasi lama — digantikan pengajuan ulang oleh petugas.'
                );

                $this->audit->catatLog([
                    'assignment_id' => (int) $lama->id,
                    'event'         => 'SUPERSEDED',
                    'actor_user_id' => $petugasId,
                    'stage_from'    => $lama->stage,
                    'stage_to'      => $lama->stage,
                    'status_from'   => $lama->status,
                    'status_to'     => SlaTrackerService::STATUS_REJECTED,
                    'reason'        => 'Digantikan oleh pengajuan ulang setelah revisi.',
                    'meta'          => ['mode' => 'superseded'],
                ]);
            }

            // Pekerjaan lapangan dinyatakan TUNTAS DIKERJAKAN (bukan selesai
            // secara administratif) — waktunya dicatat, keputusannya belum.
            DB::table('service_task_assignments')->where('id', $row->id)->update([
                'status'     => self::AWAITING,
                'notes'      => trim((string) $row->notes
                    . "\n[Petugas mengajukan hasil: " . now()->format('d/m/Y H:i') . ']'
                    . ($catatan ? "\nCatatan petugas: " . $catatan : '')),
                'updated_at' => now(),
            ]);

            $this->audit->catatLog([
                'assignment_id' => $row->id,
                'event'         => 'SUBMITTED',
                'actor_user_id' => $petugasId,
                'from_user_id'  => $petugasId,
                'to_user_id'    => $petugasId,
                'stage_from'    => $row->stage,
                'stage_to'      => $stageVerif,
                'status_from'   => $statusLama,
                'status_to'     => self::AWAITING,
                'reason'        => $catatan,
                'meta'          => ['mode' => 'petugas_ajukan'],
            ]);
        });

        // Buka tahapan SLA verifikasi agar Kasubid punya tenggat yang terlihat.
        $slaId = SlaTrackerService::assign($stageVerif, [
            'reference_type'      => $row->reference_type,
            'reference_id'        => $row->reference_id,
            'tax_domain'          => $row->tax_domain,
            'tax_sector_code'     => $row->tax_sector_code,
            'assigned_to_user_id' => null,
            'notes'               => 'Menunggu verifikasi Kasubid atas hasil pekerjaan lapangan.',
        ]);

        return [
            'assignment_id' => (int) $row->id,
            'status'        => self::AWAITING,
            'stage'         => $stageVerif,
            'sla_id'        => $slaId,
        ];
    }

    // ==================================================================
    // SISI KASUBID
    // ==================================================================

    /**
     * Kasubid MENYATAKAN SELESAI (setuju) — status menjadi TERVERIFIKASI.
     * Hanya dari sini kata "selesai" berasal.
     */
    public function verifikasi(int $assignmentId, int $kasubidId, ?string $catatan = null): array
    {
        $row = $this->ambilAssignment($assignmentId);

        if ($row->status !== self::AWAITING) {
            throw new InvalidArgumentException(
                'Tugas ini belum diajukan petugas atau sudah diproses (status: '
                . $row->status . ').'
            );
        }

        // Anti self-verification: pemeriksa tidak boleh PIC-nya sendiri.
        if ((int) $row->assigned_to_user_id === $kasubidId) {
            throw new InvalidArgumentException(
                'Anda adalah petugas PIC tugas ini. Verifikasi harus dilakukan pejabat lain.'
            );
        }

        DB::transaction(function () use ($row, $kasubidId, $catatan) {
            DB::table('service_task_assignments')->where('id', $row->id)->update([
                'status'       => SlaTrackerService::STATUS_TERVERIFIKASI,
                'notes'        => trim((string) $row->notes
                    . "\n[Kasubid: HASIL TERVERIFIKASI " . now()->format('d/m/Y H:i') . ']'
                    . ($catatan ? "\nCatatan: " . $catatan : '')),
                'updated_at'   => now(),
            ]);

            $this->audit->catatLog([
                'assignment_id' => $row->id,
                'event'         => 'VERIFIED',
                'actor_user_id' => $kasubidId,
                'from_user_id'  => $row->assigned_to_user_id,
                'to_user_id'    => $row->assigned_to_user_id,
                'stage_from'    => $row->stage,
                'stage_to'      => $row->stage,
                'status_from'   => self::AWAITING,
                'status_to'     => SlaTrackerService::STATUS_TERVERIFIKASI,
                'reason'        => $catatan,
                'meta'          => ['mode' => 'kasubid_verifikasi'],
            ]);
        });

        // Tutup tahap SLA verifikasi — inilah momen "dipastikan selesai".
        $this->tutupTahapVerifikasi($row, $kasubidId, $catatan, false);

        return [
            'assignment_id' => (int) $row->id,
            'status'        => SlaTrackerService::STATUS_TERVERIFIKASI,
            'oleh'          => $kasubidId,
        ];
    }

    /**
     * Kasubid MENGEMBALIKAN pekerjaan untuk diperbaiki.
     * Catatan WAJIB — tanpa catatan petugas tidak tahu apa yang salah.
     */
    public function tolak(int $assignmentId, int $kasubidId, string $catatan): array
    {
        if (trim($catatan) === '') {
            throw new InvalidArgumentException('Catatan wajib diisi saat mengembalikan pekerjaan.');
        }

        $row = $this->ambilAssignment($assignmentId);

        if ($row->status !== self::AWAITING) {
            throw new InvalidArgumentException(
                'Tugas ini belum diajukan petugas atau sudah diproses (status: '
                . $row->status . ').'
            );
        }

        if ((int) $row->assigned_to_user_id === $kasubidId) {
            throw new InvalidArgumentException(
                'Anda adalah petugas PIC tugas ini. Verifikasi harus dilakukan pejabat lain.'
            );
        }

        DB::transaction(function () use ($row, $kasubidId, $catatan) {
            DB::table('service_task_assignments')->where('id', $row->id)->update([
                'status'           => SlaTrackerService::STATUS_REVISI,
                'rejection_reason' => $catatan,
                'notes'            => trim((string) $row->notes
                    . "\n[Kasubid: PERLU PERBAIKAN " . now()->format('d/m/Y H:i') . ']'
                    . "\nAlasan: " . $catatan),
                'updated_at'       => now(),
            ]);

            $this->audit->catatLog([
                'assignment_id' => $row->id,
                'event'         => 'RETURNED',
                'actor_user_id' => $kasubidId,
                'from_user_id'  => $row->assigned_to_user_id,
                'to_user_id'    => $row->assigned_to_user_id,
                'stage_from'    => $row->stage,
                'stage_to'      => $row->stage,
                'status_from'   => self::AWAITING,
                'status_to'     => SlaTrackerService::STATUS_REVISI,
                'reason'        => $catatan,
                'meta'          => ['mode' => 'kasubid_tolak'],
            ]);
        });

        // Tahap verifikasi ditutup sebagai REJECTED: pekerjaan ini belum
        // dinyatakan selesai, dan petugas akan mengajukan ulang setelah revisi.
        $this->tutupTahapVerifikasi($row, $kasubidId, $catatan, true);

        return [
            'assignment_id' => (int) $row->id,
            'status'        => SlaTrackerService::STATUS_REVISI,
            'catatan'       => $catatan,
        ];
    }

    // ==================================================================
    // KUERI ANTRIAN
    // ==================================================================

    /**
     * Antrian tugas yang MENUNGGU VERIFIKASI KASUBID, tersaring domain wilayah.
     *
     * Domain diambil dari akun Kasubid; domain 'all' (admin/super) melihat
     * semua. Domain kosong/null diperlakukan sebagai TIDAK BOLEH melihat
     * apa pun (fail-closed) — bukan sebagai "semua".
     *
     * @return array{domain:string,jumlah:int,data:\Illuminate\Support\Collection<int,object>}
     */
    public function antrian(User $kasubid, array $filter = []): array
    {
        $domain = TaxDomainHelper::getDomain($kasubid);
        $typeIds = TaxDomainHelper::getRetributionTypeIds($domain);

        $q = DB::table('service_task_assignments as sta')
            ->leftJoin('users as p', 'p.id', '=', 'sta.assigned_to_user_id')
            ->leftJoin('users as k', 'k.id', '=', 'sta.assigned_by_user_id')
            ->select(
                'sta.id', 'sta.reference_type', 'sta.reference_id', 'sta.stage',
                'sta.tax_domain', 'sta.tax_sector_code', 'sta.status',
                'sta.sla_deadline', 'sta.escalation_alert_at', 'sta.escalated_at',
                'sta.started_at', 'sta.updated_at', 'sta.notes',
                'p.name as petugas_nama', 'p.id as petugas_id',
                'k.name as penugasan_oleh'
            )
            ->where('sta.status', self::AWAITING)
            ->orderBy('sta.updated_at');

        $this->batasiDomain($q, $domain, $typeIds);

        if (! empty($filter['layanan'])) {
            $q->where('sta.tax_sector_code', $filter['layanan']);
        }

        if (! empty($filter['stage'])) {
            $q->where('sta.stage', $filter['stage']);
        }

        $rows = $q->limit(200)->get();

        // Tandai mana yang sudah lewat tenggat — Kasubid perlu tahu prioritas.
        $now = now();
        $rows = $rows->map(function ($r) use ($now) {
            $r->lewat_tenggat = $r->sla_deadline !== null
                && ! $r->escalated_at
                && \Illuminate\Support\Carbon::parse($r->sla_deadline)->lessThan($now);
            return $r;
        });

        return [
            'domain'  => $domain,
            'jumlah'  => $rows->count(),
            'data'    => $rows,
        ];
    }

    /**
     * Detail satu tugas + riwayat audit lengkap.
     */
    public function detail(User $kasubid, int $assignmentId): ?array
    {
        $domain = TaxDomainHelper::getDomain($kasubid);
        $typeIds = TaxDomainHelper::getRetributionTypeIds($domain);

        $q = DB::table('service_task_assignments as sta')
            ->leftJoin('users as p', 'p.id', '=', 'sta.assigned_to_user_id')
            ->leftJoin('users as k', 'k.id', '=', 'sta.assigned_by_user_id')
            ->select(
                'sta.*',
                'p.name as petugas_nama', 'p.email as petugas_email',
                'k.name as penugasan_oleh'
            )
            ->where('sta.id', $assignmentId);

        $this->batasiDomain($q, $domain, $typeIds);
        $row = $q->first();

        if (! $row) {
            return null;
        }

        // Riwayat audit tugas ini, DITAMBAH riwayat tahap verifikasi yang
        // berbagi objek referensi yang sama — supaya Kasubid melihat siklus
        // lengkap (diajukan, dikembalikan, diajukan ulang), bukan potongan.
        $idTerkait = DB::table('service_task_assignments')
            ->where('reference_type', $row->reference_type)
            ->where('reference_id', $row->reference_id)
            ->pluck('id')
            ->all();

        $log = DB::table('service_task_assignment_logs as l')
            ->leftJoin('users as a', 'a.id', '=', 'l.actor_user_id')
            ->leftJoin('users as f', 'f.id', '=', 'l.from_user_id')
            ->leftJoin('users as t', 't.id', '=', 'l.to_user_id')
            ->select(
                'l.id', 'l.assignment_id', 'l.event', 'l.stage_from', 'l.stage_to',
                'l.status_from', 'l.status_to', 'l.reason', 'l.meta', 'l.created_at',
                'a.name as aktor_nama', 'f.name as dari_nama', 't.name as ke_nama'
            )
            ->whereIn('l.assignment_id', $idTerkait)
            ->orderBy('l.created_at')
            ->get();

        return [
            'tugas'   => $row,
            'riwayat' => $log,
            'aksi_tersedia' => $row->status === self::AWAITING
                ? ['verifikasi', 'tolak']
                : [],
        ];
    }

    // ==================================================================
    // INTERNAL
    // ==================================================================

    private function ambilAssignment(int $id): object
    {
        $row = DB::table('service_task_assignments')->where('id', $id)->first();

        if (! $row) {
            throw new InvalidArgumentException('Tugas #' . $id . ' tidak ditemukan.');
        }

        return $row;
    }

    /**
     * Tutup tahap SLA verifikasi milik tugas ini.
     *
     * Tanpa ini, tahap verifikasi menggantung sebagai PENDING selamanya —
     * dan itu pula yang dulu memblokir pengajuan ulang setelah revisi.
     */
    private function tutupTahapVerifikasi(object $row, int $kasubidId, ?string $catatan, bool $ditolak): void
    {
        $stageVerif = $this->stageVerifikasiUntuk($row);

        $tahap = DB::table('service_task_assignments')
            ->where('reference_type', $row->reference_type)
            ->where('reference_id', $row->reference_id)
            ->where('stage', $stageVerif)
            ->whereIn('status', [
                SlaTrackerService::STATUS_PENDING,
                SlaTrackerService::STATUS_IN_PROGRESS,
                SlaTrackerService::STATUS_ESCALATED,
            ])
            ->get();

        foreach ($tahap as $t) {
            SlaTrackerService::complete(
                (int) $t->id,
                $ditolak ? SlaTrackerService::STATUS_REJECTED : SlaTrackerService::STATUS_APPROVED,
                $ditolak ? $catatan : null
            );

            $this->audit->catatLog([
                'assignment_id' => (int) $t->id,
                'event'         => 'VERIFICATION_CLOSED',
                'actor_user_id' => $kasubidId,
                'stage_from'    => $t->stage,
                'stage_to'      => $t->stage,
                'status_from'   => $t->status,
                'status_to'     => $ditolak
                    ? SlaTrackerService::STATUS_REJECTED
                    : SlaTrackerService::STATUS_APPROVED,
                'reason'        => $catatan,
                'meta'          => ['mode' => $ditolak ? 'kasubid_tolak' : 'kasubid_verifikasi'],
            ]);
        }
    }

    /**
     * Batasi kueri ke domain wilayah akun.
     *
     * Fail-closed: akun dengan domain null (metadata kosong) TIDAK melihat
     * apa pun. Sebelumnya kelalaian semacam ini membuat data bocor lintas
     * wilayah — sekarang sengaja ditutup.
     */
    private function batasiDomain($query, ?string $domain, array $typeIds): void
    {
        if ($domain === TaxDomainHelper::DOMAIN_ALL) {
            return;
        }

        if (empty($domain) || empty($typeIds)) {
            // Tidak punya domain -> tidak melihat apa pun.
            $query->whereRaw('1 = 0');
            return;
        }

        // Penugasan punya kolom tax_domain sendiri; bila kosong (data lama),
        // turunkan dari objek pajak referensinya.
        $query->where(function ($w) use ($domain, $typeIds) {
            $w->where('sta.tax_domain', $domain)
              ->orWhere(function ($w2) use ($typeIds) {
                  $w2->whereNull('sta.tax_domain')
                     ->where('sta.reference_type', 'tax_object')
                     ->whereIn('sta.reference_id', function ($sub) use ($typeIds) {
                         $sub->select('id')->from('tax_objects')
                             ->whereIn('retribution_classification_id', $typeIds);
                     });
              });
        });
    }

    /** Tahap SLA verifikasi sesuai fokus Kasubid yang menangani. */
    private function stageVerifikasiUntuk(object $row): string
    {
        // Penugasan penagihan (STPD/denda/penagihan lapangan) memakai jalur
        // Penagihan; sisanya jalur Pendataan.
        $kodePenagihan = ['STPD', 'PENAGIHAN', 'DENDA'];
        $sector = strtoupper((string) $row->tax_sector_code);

        foreach ($kodePenagihan as $k) {
            if ($sector !== '' && str_contains($sector, $k)) {
                return 'VERIFIKASI_HASIL_PENAGIHAN';
            }
        }

        return self::STAGE_BY_FOCUS['pendataan'];
    }
}
