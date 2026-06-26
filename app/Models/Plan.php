<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'code',
        'price',
        'max_admins',
        'max_sellers',
        'max_monthly_transactions',
        'modules',
        'is_active',
        'has_electronic_billing',
        'monthly_invoice_limit',
        'max_certificates',
        'max_branches',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_admins' => 'integer',
        'max_sellers' => 'integer',
        'max_monthly_transactions' => 'integer',
        'modules' => 'array',
        'is_active' => 'boolean',
        'has_electronic_billing' => 'boolean',
        'monthly_invoice_limit' => 'integer',
        'max_certificates' => 'integer',
        'max_branches' => 'integer',
    ];
}
