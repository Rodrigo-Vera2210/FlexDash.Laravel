<?php

namespace App\Modules\Billing\Models;

use App\Modules\Registration\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class CompanyCertificate extends Model
{
    protected $table = 'company_certificates';

    protected $fillable = [
        'company_id',
        'certificate_path',
        'certificate_password',
        'certificate_expires_at',
        'owner_name',
        'ruc',
        'cedula',
        'is_default',
    ];

    protected $casts = [
        'certificate_expires_at' => 'datetime',
        'is_default'              => 'boolean',
    ];

    /**
     * Booted model events.
     */
    protected static function booted()
    {
        static::creating(function ($certificate) {
            // If this is the first certificate for the company, automatically make it the default
            $count = static::where('company_id', $certificate->company_id)->count();
            if ($count === 0) {
                $certificate->is_default = true;
            }
        });

        static::saving(function ($certificate) {
            // If this certificate is set as default, clear the default status on other certificates for the same company
            if ($certificate->is_default) {
                static::where('company_id', $certificate->company_id)
                    ->where('id', '!=', $certificate->id)
                    ->update(['is_default' => false]);
            }
        });

        static::deleted(function ($certificate) {
            // If the deleted certificate was the default, set another one as the default (if any exist)
            if ($certificate->is_default) {
                $next = static::where('company_id', $certificate->company_id)->first();
                if ($next) {
                    $next->update(['is_default' => true]);
                }
            }
        });
    }

    /**
     * Get decrypted certificate password.
     */
    public function getDecryptedPasswordAttribute(): string
    {
        return Crypt::decryptString($this->certificate_password);
    }

    /**
     * Set encrypted certificate password.
     */
    public function setCertificatePasswordAttribute(string $value): void
    {
        $this->attributes['certificate_password'] = Crypt::encryptString($value);
    }

    /**
     * Get the company associated with this certificate.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the electronic invoices signed by this certificate.
     */
    public function electronicInvoices(): HasMany
    {
        return $this->hasMany(ElectronicInvoice::class, 'certificate_id');
    }
}
