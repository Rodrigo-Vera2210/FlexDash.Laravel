<?php

namespace App\Traits;

use App\Modules\Registration\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToCompany
{
    /**
     * Boot the BelongsToCompany trait.
     */
    protected static function bootBelongsToCompany(): void
    {
        // Automatically scope model queries by authenticated user's company_id
        static::addGlobalScope('company_scope', function (Builder $builder) {
            $user = auth()->user();
            if ($user && $user->role !== 'superadmin' && $user->company_id) {
                $builder->where($builder->getQuery()->from . '.company_id', $user->company_id);
            }
        });

        // Automatically populate company_id column on creation
        static::creating(function (Model $model) {
            $user = auth()->user();
            if ($user && $user->company_id && !$model->company_id) {
                $model->company_id = $user->company_id;
            }
        });
    }

    /**
     * Get the company associated with the model.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
