<?php

namespace App\Modules\Product\Models;

use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Purchase\Models\PurchaseDetail;
use App\Modules\Sale\Models\SaleDetail;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'tax_id', 'code', 'name', 'description',
        'unit', 'cost', 'price', 'stock', 'minimum_stock',
        'image_path', 'is_active',
    ];

    protected $casts = [
        'cost'          => 'decimal:4',
        'price'         => 'decimal:4',
        'stock'         => 'decimal:4',
        'minimum_stock' => 'decimal:4',
        'is_active'     => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'minimum_stock')->where('is_active', true);
    }

    // ── Relaciones ────────────────────────────────────────────────────
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function purchaseDetails(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────
    public function getImageUrlAttribute(): string
    {
        if ($this->image_path && str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }
        return $this->image_path
            ? asset('storage/' . $this->image_path)
            : asset('images/no-image.png');
    }

    public function getMarginAttribute(): float
    {
        if ($this->cost <= 0) return 0;
        return round((($this->price - $this->cost) / $this->cost) * 100, 2);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->minimum_stock;
    }
}
