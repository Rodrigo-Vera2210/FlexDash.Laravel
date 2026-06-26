<?php

namespace App\Modules\CashBox\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\BelongsToCompany;

class CashBox extends Model
{
    use BelongsToCompany;

    protected $table = 'cash_boxes';

    protected $fillable = [
        'user_id', 'branch_id', 'status', 'opening_balance',
        'expected_closing_balance', 'actual_closing_balance',
        'difference', 'opened_at', 'closed_at', 'notes', 'company_id',
    ];

    protected $casts = [
        'opening_balance'          => 'decimal:2',
        'expected_closing_balance' => 'decimal:2',
        'actual_closing_balance'   => 'decimal:2',
        'difference'               => 'decimal:2',
        'opened_at'                => 'datetime',
        'closed_at'                => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Branch\Models\Branch::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CashBoxTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'OPEN');
    }

    public function isOpen(): bool
    {
        return $this->status === 'OPEN';
    }
}
