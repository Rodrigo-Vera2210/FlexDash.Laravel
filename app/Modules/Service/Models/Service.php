<?php

namespace App\Modules\Service\Models;

use App\Modules\Sale\Models\SaleDetail;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\BelongsToCompany;

class Service extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'service_category_id', 'tax_id', 'code', 'name', 'description',
        'price', 'cost', 'is_active', 'company_id',
    ];

    protected $casts = [
        'price'     => 'decimal:4',
        'cost'      => 'decimal:4',
        'is_active' => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Relaciones ────────────────────────────────────────────────────
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────
    public function getMarginAttribute(): float
    {
        if ($this->cost <= 0) return 0;
        return round((($this->price - $this->cost) / $this->cost) * 100, 2);
    }
}
