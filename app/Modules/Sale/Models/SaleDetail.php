<?php

namespace App\Modules\Sale\Models;

use App\Modules\Product\Models\Product;
use App\Modules\Service\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDetail extends Model
{
    protected $fillable = [
        'sale_id', 'product_id', 'service_id', 'quantity', 'unit_price',
        'cost_price', 'discount', 'subtotal', 'notes',
    ];

    protected $casts = [
        'quantity'   => 'decimal:4',
        'unit_price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'discount'   => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    // ── Relaciones ────────────────────────────────────────────────────
    public function sale(): BelongsTo    { return $this->belongsTo(Sale::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }

    // ── Helpers ───────────────────────────────────────────────────────
    public function isService(): bool
    {
        return $this->service_id !== null;
    }

    public function isProduct(): bool
    {
        return $this->product_id !== null;
    }

    public function getItemNameAttribute(): string
    {
        if ($this->isProduct() && $this->product) {
            return $this->product->name;
        }
        if ($this->isService() && $this->service) {
            return $this->service->name;
        }
        return 'Item desconocido';
    }

    public function getItemCodeAttribute(): string
    {
        if ($this->isProduct() && $this->product) {
            return $this->product->code;
        }
        if ($this->isService() && $this->service) {
            return $this->service->code;
        }
        return '—';
    }

    public function getMarginAttribute(): float
    {
        $cost = $this->cost_price * $this->quantity;
        return $this->subtotal - $cost;
    }
}
