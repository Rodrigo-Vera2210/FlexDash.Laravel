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
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_admins' => 'integer',
        'max_sellers' => 'integer',
        'max_monthly_transactions' => 'integer',
        'modules' => 'array',
        'is_active' => 'boolean',
    ];
}
