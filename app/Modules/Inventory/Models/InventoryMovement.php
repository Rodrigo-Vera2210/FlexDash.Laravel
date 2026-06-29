<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Product\Models\Product;
use App\Models\User;
use App\Traits\BelongsToCompany;
use App\Traits\BelongsToBranch;

class InventoryMovement extends Model
{
    use BelongsToCompany, BelongsToBranch;

    protected $fillable = [
        'product_id', 'user_id', 'branch_id', 'type', 'quantity',
        'stock_before', 'stock_after', 'unit_cost',
        'reference_type', 'reference_id', 'notes', 'company_id',
    ];

    protected $casts = [
        'quantity'     => 'decimal:4',
        'stock_before' => 'decimal:4',
        'stock_after'  => 'decimal:4',
        'unit_cost'    => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Branch\Models\Branch::class);
    }
}
