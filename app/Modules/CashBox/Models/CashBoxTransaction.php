<?php

namespace App\Modules\CashBox\Models;

use App\Models\User;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashBoxTransaction extends Model
{
    protected $table = 'cash_box_transactions';

    protected $fillable = [
        'cash_box_id', 'user_id', 'payment_id',
        'type', 'amount', 'concept',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function cashBox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
