<?php

namespace App\Http\Controllers\Api\V1\Asset\Concerns;

use App\Models\AssetRental;
use App\Models\User;

/**
 * RBAC + OPD-scoping khusus modul Aset/Sewa Alat Berat PUPR.
 *
 * Matriks akses (source of truth):
 * - petugas (opd PUPR)   : inspeksi pra/pasca, survey, daftar sewa aktif.
 * - opd / pengawas*      : membaca & memvalidasi kegiatan lapangan.
 * - super_admin / admin  : global (semua OPD).
 * Semua role dipaksa OPD-scoped ke kode OPD 'PUPR' kecuali super admin.
 */
trait AssetAccess
{
    public const ASSET_READ_ROLES = ['petugas', 'opd', 'pengawas', 'kabid_pengawas', 'kasubid_pengawas'];

    protected function isPuprScoped(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return strtoupper((string) $user->opd?->code) === 'PUPR';
    }

    protected function abortAssetForbidden(string $detail = 'akses modul Aset PUPR ditolak.'): void
    {
        abort(response()->json([
            'message' => 'Forbidden: ' . $detail,
        ], 403));
    }

    protected function assertAssetRole(User $user): void
    {
        if (!in_array($user->role, array_merge(self::ASSET_READ_ROLES, ['super_admin', 'admin']), true)) {
            $this->abortAssetForbidden('role tidak memiliki akses modul Aset/PUPR.');
        }

        if (!$this->isPuprScoped($user)) {
            $this->abortAssetForbidden('hanya OPD PUPR yang dapat mengakses modul ini.');
        }
    }

    /**
     * Query sewa aset dengan scoping OPD PUPR.
     * Petugas hanya melihat kontrak yang sudah approved/active.
     */
    protected function assetRentalQuery(?User $user = null)
    {
        $query = AssetRental::with([
            'taxpayer:id,name,nik,phone',
            'assetItem:id,name,code',
            'opd:id,name,code',
        ]);

        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        $this->assertAssetRole($user);
        $query->where('opd_id', $user->opd_id);

        if ($user->role === 'petugas') {
            $query->whereIn('status', ['approved', 'active']);
        }

        return $query;
    }

    protected function resolveAssetRental(?User $user, int $id): AssetRental
    {
        $rental = $this->assetRentalQuery($user)->find($id);

        if (!$rental) {
            $this->abortAssetForbidden('data sewa tidak ditemukan atau bukan milik OPD Anda.');
        }

        return $rental;
    }

    /**
     * Resolusi sewa untuk alur inspeksi pra/pasca operasi.
     * Petugas diizinkan menginspeksi kontrak "pending_verification" agar
     * inspeksi pra-operasi berperan sebagai aktivasi unit.
     */
    protected function resolveInspectableRental(?User $user, int $id): AssetRental
    {
        $rental = AssetRental::with([
            'taxpayer:id,name,nik,phone',
            'assetItem:id,name,code',
            'opd:id,name,code',
        ])->find($id);

        if (!$rental) {
            $this->abortAssetForbidden('data sewa tidak ditemukan.');
        }

        if ($user && !$user->isSuperAdmin()) {
            $this->assertAssetRole($user);
            if ($rental->opd_id !== $user->opd_id) {
                $this->abortAssetForbidden('data sewa bukan milik OPD Anda.');
            }
        }

        return $rental;
    }
}