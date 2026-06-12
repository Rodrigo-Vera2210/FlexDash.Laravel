<?php

namespace App\Modules\Registration\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $table = 'companies';

    protected $fillable = [
        'company_type',
        'name',
        'tax_id',
        'legal_address',
        'address',
        'city',
        'state_province',
        'postal_code',
        'country',
        'legal_entity_flag',
        'natural_entity_flag',
    ];

    protected $casts = [
        'company_type'       => 'string',
        'legal_entity_flag'  => 'boolean',
        'natural_entity_flag' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
