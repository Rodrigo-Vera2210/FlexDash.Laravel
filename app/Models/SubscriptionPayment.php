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
        'type',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
