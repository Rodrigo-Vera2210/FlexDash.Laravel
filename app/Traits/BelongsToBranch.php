<?php

namespace App\Traits;

use App\Modules\Branch\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToBranch
{
    /**
     * Boot the BelongsToBranch trait.
     */
    protected static function bootBelongsToBranch(): void
    {
        // Automatically scope model queries by session's active_branch_id
        static::addGlobalScope('branch_scope', function (Builder $builder) {
            $user = auth()->user();
            if ($user && $user->role !== 'superadmin') {
                $activeBranchId = session('active_branch_id') ?? $user->branch_id;
                if ($activeBranchId) {
                    $builder->where($builder->getQuery()->from . '.branch_id', $activeBranchId);
                }
            }
        });

        // Automatically populate branch_id column on creation
        static::creating(function (Model $model) {
            $user = auth()->user();
            if ($user && !$model->branch_id) {
                $model->branch_id = session('active_branch_id') ?? $user->branch_id;
            }
        });
    }

    /**
     * Get the branch associated with the model.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
