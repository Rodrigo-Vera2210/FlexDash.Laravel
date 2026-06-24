<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Product\Models\Product;
use App\Modules\Sale\Models\Sale;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Service\Models\Service;

use App\Traits\BelongsToCompany;

class Tax extends Model
{
    use BelongsToCompany;

    protected $fillable = ['name', 'code', 'rate', 'is_active', 'company_id'];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
