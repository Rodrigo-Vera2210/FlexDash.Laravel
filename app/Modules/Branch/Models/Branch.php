<?php

namespace App\Modules\Branch\Models;

use App\Models\User;
use App\Modules\CashBox\Models\CashBox;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Product\Models\Product;
use App\Modules\Registration\Models\Company;
use App\Modules\Sale\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\BelongsToCompany;

class Branch extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'address',
        'phone',
        'establishment_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Relations ─────────────────────────────────────────────────────
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'branch_product')
                    ->withPivot('stock');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function cashBoxes(): HasMany
    {
        return $this->hasMany(CashBox::class);
    }
}
