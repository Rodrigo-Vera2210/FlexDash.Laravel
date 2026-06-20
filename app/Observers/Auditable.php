<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait reutilizable que implementa el comportamiento de auditoría
 * para cualquier Observer que lo use.
 */
trait Auditable
{
    public function created(Model $model): void
    {
        AuditLog::record('created', $model, null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        AuditLog::record('updated', $model, $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        AuditLog::record('deleted', $model, $model->getAttributes(), null);
    }
}
