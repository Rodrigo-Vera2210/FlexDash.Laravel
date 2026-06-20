<?php

namespace App\Models;

use App\Exceptions\PaymentExceedsBalanceException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use App\Traits\BelongsToCompany;

class Payment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'payment_method_id', 'user_id', 'payable_type', 'payable_id',
        'amount', 'payment_date', 'reference', 'notes', 'company_id',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    // ── Validación crítica: el pago no puede superar el saldo ─────────
    public static function validateAmount(float $amount, float $pendingBalance): void
    {
        if ($amount > $pendingBalance) {
            throw new PaymentExceedsBalanceException($pendingBalance, $amount);
        }
        if ($amount <= 0) {
            throw new \InvalidArgumentException('El monto del pago debe ser mayor a cero.');
        }
    }

    // ── Relaciones ────────────────────────────────────────────────────
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
