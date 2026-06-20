<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\BelongsToCompany;

class PaymentMethod extends Model
{
    use BelongsToCompany;

    protected $fillable = ['name', 'description', 'is_active', 'company_id'];

    protected $casts = ['is_active' => 'boolean'];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
