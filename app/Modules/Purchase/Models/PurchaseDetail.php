<?php

namespace App\Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Product\Models\Product;

class PurchaseDetail extends Model
{
    protected $fillable = [
        'purchase_id', 'product_id', 'quantity',
        'unit_cost', 'discount', 'subtotal', 'notes',
    ];

    protected $casts = [
        'quantity'  => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'discount'  => 'decimal:2',
        'subtotal'  => 'decimal:2',
    ];

    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
    public function product(): BelongsTo  { return $this->belongsTo(Product::class); }
}
