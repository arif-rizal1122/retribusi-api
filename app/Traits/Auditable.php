<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function (Model $model) {
            self::logAudit($model, 'create');
        });

        static::updated(function (Model $model) {
            self::logAudit($model, 'update');
        });

        static::deleted(function (Model $model) {
            self::logAudit($model, 'delete');
        });
    }

    protected static function logAudit(Model $model, string $action)
    {
        $oldValues = null;
        $newValues = null;

        if ($action === 'update') {
            $newValues = $model->getChanges();
            $oldValues = array_intersect_key($model->getOriginal(), $newValues);
        } elseif ($action === 'create') {
            $newValues = $model->getAttributes();
            // Don't log sensitive info
            unset($newValues['password']);
        } elseif ($action === 'delete') {
            $oldValues = $model->getAttributes();
            unset($oldValues['password']);
        }

        $actor = Auth::user();

        AuditLog::create([
            'user_id' => $actor instanceof User ? $actor->id : null,
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
