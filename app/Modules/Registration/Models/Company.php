<?php

namespace App\Modules\Registration\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $table = 'companies';

    protected $fillable = [
        'company_type',
        'name',
        'tax_id',
        'legal_address',
        'address',
        'city',
        'state_province',
        'postal_code',
        'country',
        'legal_entity_flag',
        'natural_entity_flag',
        'subscription_plan',
        'subscription_status',
        'subscription_expires_at',
        'suspension_reason',
        'active_modules',
        'max_monthly_transactions',
        'max_admins',
        'max_sellers',
        'has_electronic_billing',
        'monthly_invoice_limit',
    ];

    protected $casts = [
        'company_type'       => 'string',
        'legal_entity_flag'  => 'boolean',
        'natural_entity_flag' => 'boolean',
        'subscription_expires_at' => 'datetime',
        'active_modules'     => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(\App\Models\SubscriptionPayment::class);
    }

    // ── Accessors & Helpers ──────────────────────────────────────────

    public function getOwnerAttribute()
    {
        return $this->users()->where('role', 'owner')->first()
            ?? $this->users()->where('role', 'company_representative')->first()
            ?? $this->users()->first();
    }

    public function getPlanAttribute()
    {
        return \App\Models\Plan::where('code', $this->subscription_plan)->first();
    }

    public function getActiveModulesAttribute($value)
    {
        if (!is_null($value)) {
            return is_string($value) ? json_decode($value, true) : $value;
        }
        return $this->plan ? $this->plan->modules : [];
    }

    public function hasModuleAccess(string $module): bool
    {
        return in_array($module, $this->active_modules);
    }

    public function getMaxAdminsAttribute($value)
    {
        return $value ?? ($this->plan ? $this->plan->max_admins : 1);
    }

    public function getMaxSellersAttribute($value)
    {
        return $value ?? ($this->plan ? $this->plan->max_sellers : 2);
    }

    public function getMaxMonthlyTransactionsAttribute($value)
    {
        return $value ?? ($this->plan ? $this->plan->max_monthly_transactions : 100);
    }

    public function getHasElectronicBillingAttribute($value)
    {
        if (is_null($value)) {
            return $this->plan ? (bool)$this->plan->has_electronic_billing : false;
        }
        return (bool)$value;
    }

    public function getMonthlyInvoiceLimitAttribute($value)
    {
        if (is_null($value)) {
            return $this->plan ? (int)$this->plan->monthly_invoice_limit : 0;
        }
        return (int)$value;
    }

    public function companyCertificates(): HasMany
    {
        return $this->hasMany(\App\Modules\Billing\Models\CompanyCertificate::class, 'company_id');
    }

    public function getMaxCertificatesAttribute($value)
    {
        return $value ?? ($this->plan ? $this->plan->max_certificates : 1);
    }

    public function canUploadCertificate(): bool
    {
        return $this->companyCertificates()->count() < $this->max_certificates;
    }
}
