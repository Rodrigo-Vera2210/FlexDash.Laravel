<?php

namespace App\Models;

use App\Modules\Registration\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $table = 'subscription_payments';

    protected $fillable = [
        'company_id',
        'plan',
        'bank_origin',
        'account_destination',
        'receipt_path',
        'status',
        'rejection_reason',
        'type',
        'duration_months',
        'discount_percentage',
        'amount',
    ];

    protected $casts = [
        'duration_months'     => 'integer',
        'discount_percentage' => 'decimal:2',
        'amount'              => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function electronicInvoice(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(\App\Modules\Billing\Models\ElectronicInvoice::class, 'invoicable');
    }
}
