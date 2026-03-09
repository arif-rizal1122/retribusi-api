<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use App\Models\Taxpayer;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\TaxObject;
use App\Models\User;

class RetributionTypeScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Check if user is logged in, is an admin, and has a retribution_type_id restriction
        if (Auth::check() && Auth::user()->role === 'admin' && Auth::user()->retribution_type_id) {
            
            $typeId = Auth::user()->retribution_type_id;

            if ($model instanceof Taxpayer) {
                // Filter taxpayers that have operations/objects under this type
                $builder->whereHas('taxObjects', function($q) use ($typeId) {
                    $q->where('retribution_type_id', $typeId);
                });
            } 
            elseif ($model instanceof Bill || $model instanceof TaxObject) {
                // Direct relationship filtering
                $builder->where('retribution_type_id', $typeId);
            }
            elseif ($model instanceof Payment) {
                $builder->whereHas('bill', function($q) use ($typeId) {
                    $q->where('retribution_type_id', $typeId);
                });
            }
            elseif ($model instanceof User) {
                // Filter only petugas under this retribution_type_id
                $builder->where('role', 'petugas')->where('retribution_type_id', $typeId);
            }
        }
    }
}
