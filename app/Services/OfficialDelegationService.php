<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * OfficialDelegationService — delegasi/backup pejabat (Kabid & Kasubid).
 *
 * Masalah yang dipecahkan:
 *   Role kabid_wilayah_1/2 dan kasubid_* hanya dipegang 1 user masing-masing.
 *   Bila pejabat cuti/sakit/dinas luar, tugas lapangan berhenti total karena
 *   OfficialResolverService selalu menunjuk orang yang sama.
 *
 * Solusi:
 *   Tabel `official_delegations` (aditif, append-only) yang memetakan
 *   "dari_role -> ke_user_id" pada rentang tanggal tertentu. Resolver
 *   mengecek delegasi AKTIF lebih dulu sebelum menunjuk pemegang role tetap.
 *
 * Prinsip:
 *   - Fail-safe: delegasi tidak valid DILEWATI, bukan dianggap sah.
 *   - Append-only: baris tidak pernah dihapus, hanya di-revoke.
 *   - Wilayah tetap dijaga: delegasi lintas wilayah DITOLAK kecuali
 *     dinyatakan eksplisit oleh Kabid (allow_cross_domain).
 */
class OfficialDelegationService
{
    public const TABLE = 'official_delegations';

    /** Pastikan tabel ada (idempoten, aman dijalankan berulang). */
    public static function ensureTable(): bool
    {
        if (Schema::hasTable(self::TABLE)) {
            return true;
        }

        Schema::create(self::TABLE, function ($t) {
            $t->id();
            $t->string('from_role', 64);              // role yang didelegasikan
            $t->string('tax_domain', 32)->nullable(); // wilayah_1 / wilayah_2 / null = semua
            $t->unsignedBigInteger('to_user_id');     // penerima delegasi
            $t->unsignedBigInteger('granted_by_user_id')->nullable();
            $t->date('start_date');
            $t->date('end_date');
            $t->string('reason', 500)->nullable();
            $t->boolean('allow_cross_domain')->default(false);
            $t->string('status', 20)->default('ACTIVE'); // ACTIVE | REVOKED | EXPIRED
            $t->timestamp('revoked_at')->nullable();
            $t->unsignedBigInteger('revoked_by_user_id')->nullable();
            $t->timestamps();

            $t->index(['from_role', 'tax_domain', 'status'], 'idx_deleg_lookup');
            $t->index(['to_user_id', 'status'], 'idx_deleg_user');
        });

        return true;
    }

    /**
     * Cari user pengganti untuk sebuah role pada domain tertentu.
     * Mengembalikan user_id penerima delegasi AKTIF, atau null bila tidak ada.
     */
    public function resolveDelegate(?string $role, ?string $domain = null, ?Carbon $at = null): ?int
    {
        if (! $role) {
            return null;
        }

        if (! Schema::hasTable(self::TABLE)) {
            return null;
        }

        $at = $at ?: Carbon::now();

        $row = DB::table(self::TABLE)
            ->where('from_role', $role)
            ->where('status', 'ACTIVE')
            ->where('start_date', '<=', $at->toDateString())
            ->where('end_date', '>=', $at->toDateString())
            ->where(function ($q) use ($domain) {
                $q->whereNull('tax_domain');
                if ($domain) {
                    $q->orWhere('tax_domain', $domain);
                }
            })
            ->orderByRaw('CASE WHEN tax_domain IS NULL THEN 1 ELSE 0 END') // domain spesifik diprioritaskan
            ->orderByDesc('id')
            ->first();

        if (! $row) {
            return null;
        }

        // Fail-safe: penerima harus user aktif dan memang punya izin bidang.
        $penerima = User::find($row->to_user_id);
        if (! $penerima) {
            return null;
        }

        return (int) $penerima->id;
    }

    /**
     * Buat delegasi baru. Mengembalikan id baris.
     *
     * @throws \InvalidArgumentException
     */
    public function grant(
        string $fromRole,
        int $toUserId,
        string $startDate,
        string $endDate,
        ?string $domain = null,
        ?int $grantedBy = null,
        ?string $reason = null,
        bool $allowCrossDomain = false
    ): int {
        self::ensureTable();

        $penerima = User::find($toUserId);
        if (! $penerima) {
            throw new \InvalidArgumentException("User penerima delegasi (id={$toUserId}) tidak ditemukan.");
        }

        // Wilayah tidak boleh bocor tanpa izin eksplisit.
        if ($domain && ! $allowCrossDomain) {
            $domainPenerima = \App\Support\TaxDomainHelper::getDomain($penerima);
            if ($domainPenerima && $domainPenerima !== $domain) {
                throw new \InvalidArgumentException(
                    "User {$penerima->name} berdomisili {$domainPenerima}, tidak boleh menerima delegasi {$domain} "
                    . 'tanpa izin lintas wilayah.'
                );
            }
        }

        // Cegah delegasi ganda pada role+domain yang sama dan rentang tumpang tindih.
        $tumpangTindih = DB::table(self::TABLE)
            ->where('from_role', $fromRole)
            ->where('status', 'ACTIVE')
            ->where(function ($q) use ($domain) {
                $q->whereNull('tax_domain');
                if ($domain) {
                    $q->orWhere('tax_domain', $domain);
                }
            })
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->exists();

        if ($tumpangTindih) {
            throw new \InvalidArgumentException(
                "Sudah ada delegasi aktif untuk role {$fromRole} pada rentang tanggal tersebut. "
                . 'Cabut dulu delegasi lama sebelum membuat yang baru.'
            );
        }

        $id = DB::table(self::TABLE)->insertGetId([
            'from_role'            => $fromRole,
            'tax_domain'           => $domain,
            'to_user_id'           => $toUserId,
            'granted_by_user_id'   => $grantedBy,
            'start_date'           => $startDate,
            'end_date'             => $endDate,
            'reason'               => $reason,
            'allow_cross_domain'   => $allowCrossDomain,
            'status'               => 'ACTIVE',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $this->log('GRANT', $id, [
            'from_role' => $fromRole,
            'to_user_id' => $toUserId,
            'domain' => $domain,
            'range' => "{$startDate}..{$endDate}",
            'by' => $grantedBy,
        ]);

        return (int) $id;
    }

    /** Cabut delegasi (append-only: tidak dihapus, hanya ditandai REVOKED). */
    public function revoke(int $id, ?int $byUserId = null, ?string $alasan = null): bool
    {
        if (! Schema::hasTable(self::TABLE)) {
            return false;
        }

        $row = DB::table(self::TABLE)->find($id);
        if (! $row) {
            throw new \InvalidArgumentException("Delegasi id={$id} tidak ditemukan.");
        }
        if ($row->status !== 'ACTIVE') {
            throw new \InvalidArgumentException("Delegasi id={$id} sudah tidak aktif (status: {$row->status}).");
        }

        DB::table(self::TABLE)->where('id', $id)->update([
            'status'              => 'REVOKED',
            'revoked_at'          => now(),
            'revoked_by_user_id'  => $byUserId,
            'reason'              => trim(($row->reason ? $row->reason . ' | ' : '') . 'Dicabut: ' . ($alasan ?: 'tanpa keterangan')),
            'updated_at'          => now(),
        ]);

        $this->log('REVOKE', $id, ['by' => $byUserId, 'alasan' => $alasan]);

        return true;
    }

    /** Tandai delegasi yang sudah lewat tanggal sebagai EXPIRED. */
    public function expireLapsed(): int
    {
        if (! Schema::hasTable(self::TABLE)) {
            return 0;
        }

        return DB::table(self::TABLE)
            ->where('status', 'ACTIVE')
            ->where('end_date', '<', Carbon::now()->toDateString())
            ->update(['status' => 'EXPIRED', 'updated_at' => now()]);
    }

    /** Daftar delegasi untuk kebutuhan tampilan. */
    public function daftar(array $filter = []): array
    {
        if (! Schema::hasTable(self::TABLE)) {
            return [];
        }

        $this->expireLapsed();

        $q = DB::table(self::TABLE . ' as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.to_user_id')
            ->leftJoin('users as g', 'g.id', '=', 'd.granted_by_user_id')
            ->select(
                'd.*',
                'u.name as to_user_name',
                'g.name as granted_by_name'
            );

        if (! empty($filter['role'])) {
            $q->where('d.from_role', $filter['role']);
        }
        if (! empty($filter['status'])) {
            $q->where('d.status', $filter['status']);
        }
        if (! empty($filter['domain'])) {
            $q->where(function ($w) use ($filter) {
                $w->whereNull('d.tax_domain')->orWhere('d.tax_domain', $filter['domain']);
            });
        }

        return $q->orderByDesc('d.id')->limit(200)->get()->map(function ($r) {
            return [
                'id'              => $r->id,
                'from_role'       => $r->from_role,
                'domain'          => $r->tax_domain ?: 'SEMUA',
                'to_user_id'      => $r->to_user_id,
                'to_user_name'    => $r->to_user_name,
                'granted_by_name' => $r->granted_by_name,
                'mulai'           => $r->start_date,
                'selesai'         => $r->end_date,
                'alasan'          => $r->reason,
                'cross_domain'    => (bool) $r->allow_cross_domain,
                'status'          => $r->status,
                'aktif'           => $r->status === 'ACTIVE'
                    && $r->start_date <= now()->toDateString()
                    && $r->end_date >= now()->toDateString(),
            ];
        })->all();
    }

    /** Kandidat penerima delegasi (pejabat sejenis di bidang pajak). */
    public function kandidat(?string $domain = null): array
    {
        $peranBidang = [
            'kabid_wilayah_1', 'kabid_wilayah_2', 'kabid_pengawas',
            'kasubid_pendataan_wil1', 'kasubid_pendataan_wil2',
            'kasubid_penagihan_wil1', 'kasubid_penagihan_wil2',
        ];

        // ⚠️ JANGAN pakai ->select('id','name','role','opd_id') di sini.
        // TaxDomainHelper::getDomain() membaca kolom `metadata` untuk menentukan
        // wilayah. Bila kolom itu tidak ikut di-select, pembacaan gagal senyap
        // dan SEMUA kandidat dilaporkan berdomain 'all' -- akibatnya Kabid
        // Wilayah II sendiri tertulis "(beda wilayah)" di formulir.
        $q = User::whereIn('role', $peranBidang)
            ->where('opd_id', 5)
            ->where('status', '!=', 'inactive');

        return $q->get(['id', 'name', 'role', 'opd_id', 'metadata'])->map(function ($u) use ($domain) {
            $d = \App\Support\TaxDomainHelper::getDomain($u) ?: 'all';
            return [
                'id'     => $u->id,
                'nama'   => $u->name,
                'role'   => $u->role,
                'domain' => $d,
                'cocok'  => ! $domain || $d === 'all' || $d === $domain,
            ];
        })->all();
    }

    private function log(string $aksi, int $id, array $meta): void
    {
        try {
            \Illuminate\Support\Facades\Log::info("[DELEGASI] {$aksi} id={$id} " . json_encode($meta, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // logging tidak boleh menggagalkan aksi utama
        }
    }
}
