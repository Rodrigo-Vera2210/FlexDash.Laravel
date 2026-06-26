<?php

namespace App\Modules\Purchase\Models;

use App\Exceptions\ImmutableDocumentException;
use App\Modules\Partner\Models\Partner;
use App\Models\Tax;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\BelongsToCompany;

class Purchase extends Model
{
    use SoftDeletes, BelongsToCompany;

    const STATUS_DRAFT    = 'BORRADOR';
    const STATUS_APPROVED = 'APROBADO';
    const STATUS_PAID     = 'PAGADO';
    const STATUS_CANCELLED = 'ANULADO';

    protected $fillable = [
        'partner_id', 'user_id', 'tax_id', 'branch_id', 'series', 'number',
        'supplier_invoice', 'issue_date', 'due_date', 'status',
        'currency', 'subtotal', 'tax_amount', 'discount', 'total',
        'paid_amount', 'pending_balance', 'notes',
        'approved_at', 'cancelled_at', 'company_id',
    ];

    protected $casts = [
        'issue_date'      => 'date',
        'due_date'        => 'date',
        'approved_at'     => 'datetime',
        'cancelled_at'    => 'datetime',
        'subtotal'        => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'discount'        => 'decimal:2',
        'total'           => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'pending_balance' => 'decimal:2',
    ];

    public function assertEditable(): void
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new ImmutableDocumentException('Compra', $this->number, $this->status);
        }
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function scopeApproved($query)  { return $query->where('status', self::STATUS_APPROVED); }
    public function scopePending($query)   { return $query->where('pending_balance', '>', 0); }
    public function scopeThisMonth($query) { return $query->whereMonth('issue_date', now()->month)->whereYear('issue_date', now()->year); }

    public function partner(): BelongsTo  { return $this->belongsTo(Partner::class); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function tax(): BelongsTo      { return $this->belongsTo(Tax::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(\App\Modules\Branch\Models\Branch::class); }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
