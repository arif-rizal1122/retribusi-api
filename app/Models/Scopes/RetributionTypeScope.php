<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Models\Taxpayer;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\TaxObject;
use App\Models\User;

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

        // This scope only applies to admin/pengawas with a specific retribution_type_id
        if (!$user || !isset($user->role) || !in_array($user->role, ['admin', 'pengawas']) || !$user->retribution_type_id) {
            return;
        }

        $typeId = $user->retribution_type_id;

        if ($model instanceof Taxpayer) {
            $builder->whereHas('taxObjects', function($q) use ($typeId) {
                $q->where('retribution_type_id', $typeId);
            });
        } 
        elseif ($model instanceof Bill || $model instanceof TaxObject) {
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
