<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Models\Taxpayer;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\TaxObject;
use App\Models\PetugasTask;
use App\Models\User;
use App\Models\Verification;
use App\Models\RetributionClassification;
use App\Models\Zone;

class RetributionTypeScope implements Scope
{
    /**
     * The authenticated user, set AFTER authentication middleware completes.
     * This avoids calling Auth::user() or Auth::hasUser() which both trigger
     * Sanctum Guard -> model loading -> this scope -> infinite recursion.
     */
    protected static $resolvedUser = null;
    protected static $userResolved = false;

    /**
     * Called by middleware after authentication is complete to safely provide
     * the user to this scope without triggering recursion.
     */
    public static function setAuthenticatedUser($user): void
    {
        static::$resolvedUser = $user;
        static::$userResolved = true;
    }

    /**
     * Reset the resolved user (for testing or between requests).
     */
    public static function resetUser(): void
    {
        static::$resolvedUser = null;
        static::$userResolved = false;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     * 
     * CRITICAL: This method must NEVER call Auth::user(), Auth::hasUser(),
     * or any method that triggers Sanctum guard resolution. Doing so causes
     * infinite recursion: Guard -> model load -> this scope -> Guard -> ...
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply if middleware has explicitly set the user after auth
        if (!static::$userResolved) {
            return;
        }

        $user = static::$resolvedUser;

        // =====================================================================
        // Layer A: OPD Isolation (staff-only).
        // Non-super-admin staff (petugas/opd/pengawas) may only see rows owned
        // by their own OPD. SuperAdmin & Admin (isSuperAdmin) bypass; citizens
        // (Taxpayer) are never scoped because they are not instances of User.
        // =====================================================================
        if ($user instanceof User && !$user->isSuperAdmin()) {
            $opdId = $user->opd_id;

            // Skip when the user has no OPD bound (avoid `WHERE opd_id =` null).
            if ($opdId !== null && $opdId !== '') {
                if ($model instanceof Taxpayer
                    || $model instanceof Bill
                    || $model instanceof TaxObject
                    || $model instanceof Verification
                ) {
                    $builder->where('opd_id', (int) $opdId);
                } elseif ($model instanceof Payment) {
                    $builder->whereHas('bill', function ($q) use ($opdId) {
                        $q->where('opd_id', (int) $opdId);
                    });
                } elseif ($model instanceof PetugasTask) {
                    $builder->whereHas('user', function ($q) use ($opdId) {
                        $q->where('opd_id', (int) $opdId);
                    });
                } elseif ($model instanceof User) {
                    $builder->where('opd_id', (int) $opdId);
                }
            }
        }

        // =====================================================================
        // Layer B: Retribution-type scope (existing behavior, unchanged).
        // This scope only applies to admin/pengawas with a specific retribution_type_id
        // =====================================================================
        if (!$user || !isset($user->role) || !in_array($user->role, ['admin', 'pengawas']) || !$user->retribution_type_id) {
            return;
        }

        $typeId = $user->retribution_type_id;

        if ($model instanceof Taxpayer) {
            $builder->whereHas('taxObjects', function($q) use ($typeId) {
                $q->where('retribution_type_id', $typeId);
            });
        } 
        elseif ($model instanceof Bill || $model instanceof TaxObject || $model instanceof RetributionClassification || $model instanceof Zone) {
            $builder->where('retribution_type_id', $typeId);
        }
        elseif ($model instanceof Payment) {
            $builder->whereHas('bill', function($q) use ($typeId) {
                $q->where('retribution_type_id', $typeId);
            });
        }
        elseif ($model instanceof User) {
            $builder->where('role', 'petugas')->where('retribution_type_id', $typeId);
        }
    }
}
