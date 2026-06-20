<?php

namespace App\Modules\Sale\Models;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDetail extends Model
{
    protected $fillable = [
        'sale_id', 'product_id', 'quantity', 'unit_price',
        'cost_price', 'discount', 'subtotal', 'notes',
    ];

    protected $casts = [
        'quantity'   => 'decimal:4',
        'unit_price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'discount'   => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function sale(): BelongsTo    { return $this->belongsTo(Sale::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function getMarginAttribute(): float
    {
        $cost = $this->cost_price * $this->quantity;
        return $this->subtotal - $cost;
    }
}
