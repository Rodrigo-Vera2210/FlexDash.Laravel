<?php

namespace App\Modules\Billing\Models;

use App\Modules\Registration\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ElectronicInvoice extends Model
{
    protected $table = 'electronic_invoices';

    protected $fillable = [
        'company_id',
        'certificate_id',
        'invoicable_type',
        'invoicable_id',
        'access_key',
        'sequence',
        'status',
        'xml_path',
        'pdf_path',
        'sri_error_details',
        'authorized_at',
    ];

    protected $casts = [
        'authorized_at' => 'datetime',
    ];

    /**
     * Get the parent invoicable model (Sale or SubscriptionPayment).
     */
    public function invoicable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the company associated with the electronic invoice.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the digital certificate used to sign this electronic invoice.
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(CompanyCertificate::class, 'certificate_id');
    }
}
