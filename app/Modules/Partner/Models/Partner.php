<?php

namespace App\Modules\Partner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Sale\Models\Sale;
use App\Modules\Purchase\Models\Purchase;

class Partner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type', 'business_name', 'trade_name', 'document_type',
        'document_number', 'email', 'phone', 'address', 'city',
        'country', 'credit_limit', 'is_active', 'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'is_active'    => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeClientes($query)
    {
        return $query->whereIn('type', ['cliente', 'ambos']);
    }

    public function scopeProveedores($query)
    {
        return $query->whereIn('type', ['proveedor', 'ambos']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Relaciones ────────────────────────────────────────────────────
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────
    public function getDisplayNameAttribute(): string
    {
        return $this->trade_name ?? $this->business_name;
    }

    public function getTotalReceivableAttribute(): float
    {
        return $this->sales()
            ->whereIn('status', ['APROBADO'])
            ->sum('pending_balance');
    }

    public function getTotalPayableAttribute(): float
    {
        return $this->purchases()
            ->whereIn('status', ['APROBADO'])
            ->sum('pending_balance');
    }
}
