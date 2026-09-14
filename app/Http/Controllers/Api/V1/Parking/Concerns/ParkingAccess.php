<?php

namespace App\Http\Controllers\Api\V1\Parking\Concerns;

use App\Models\ParkingLocation;
use App\Models\ParkingShift;
use App\Models\User;

/**
 * RBAC + OPD-scoping khusus modul Parkir Dishub.
 *
 * Matriks akses (source of truth):
 * - petugas (jukir)          : kasir — shift, sesi, prepaid-cash, profil, top-up.
 * - opd/pengawas*             : inspektur — dashboard & sidak/sanksi.
 * - super_admin / admin       : global (semua akses parkir).
 * Semua role dipaksa OPD-scoped ke Dishub kecuali super admin.
 */
trait ParkingAccess
{
    public const PARKING_JUKIR_ROLES = ['petugas'];

    public const PARKING_INSPECTOR_ROLES = ['opd', 'pengawas', 'kabid_pengawas', 'kasubid_pengawas'];

    protected function isDishubScoped(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return strtoupper((string) $user->opd?->code) === 'DISHUB';
    }

    protected function isParkingInspector(User $user): bool
    {
        return in_array($user->role, array_merge(self::PARKING_INSPECTOR_ROLES, ['super_admin', 'admin']), true);
    }

    protected function abortParkingForbidden(): void
    {
        abort(response()->json([
            'success' => false,
            'message' => 'Forbidden: akses modul Parkir Dishub ditolak.',
        ], 403));
    }

    protected function ensureParkingRole(User $user, array $roles): void
    {
        $roles = array_merge($roles, ['super_admin', 'admin']);

        if (!$this->isDishubScoped($user) || !in_array($user->role, $roles, true)) {
            $this->abortParkingForbidden();
        }
    }

    /**
     * Query lokasi parkir dengan scoping OPD + penugasan petugas.
     */
    protected function parkingLocationQuery(?User $user = null)
    {
        $query = ParkingLocation::query()
            ->with(['opd:id,name,code', 'classification:id,name,code']);

        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        $query->where('opd_id', $user->opd_id);

        if ($user->role === 'petugas') {
            $assignedTypeIds = $user->assignments->pluck('retribution_type_id')->filter()->unique()->toArray();
            if (!empty($assignedTypeIds)) {
                $query->whereIn('retribution_type_id', $assignedTypeIds);
            }
        }

        return $query;
    }

    protected function resolveParkingLocation(?User $user, int $id): ParkingLocation
    {
        $location = $this->parkingLocationQuery($user)->find($id);

        if (!$location) {
            $this->abortParkingForbidden();
        }

        return $location;
    }

    /**
     * Wajibkan shift terbuka di lokasi yang sama sebelum mencatat sesi.
     * Mencegah "cross-location stuffing" (transaksi di lokasi lain dari shift agregat).
     */
    protected function requireActiveShiftAtLocation(User $user, ParkingLocation $location): ParkingShift
    {
        $shift = ParkingShift::where('user_id', $user->id)
            ->where('parking_location_id', $location->id)
            ->where('shift_date', now()->toDateString())
            ->where('shift_status', 'open')
            ->first();

        if (!$shift) {
            abort(response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka di lokasi ini. Buka shift terlebih dahulu.',
            ], 422));
        }

        return $shift;
    }
}