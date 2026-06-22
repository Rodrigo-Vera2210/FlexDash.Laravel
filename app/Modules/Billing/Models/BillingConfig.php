<?php

namespace App\Modules\Billing\Models;

use App\Modules\Registration\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class BillingConfig extends Model
{
    protected $table = 'billing_configs';

    protected $fillable = [
        'company_id',
        'establishment',
        'emission_point',
        'last_sequence',
        'environment',
        'is_active',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'last_sequence' => 'integer',
    ];

    /**
     * Get the company associated with the billing configuration.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
