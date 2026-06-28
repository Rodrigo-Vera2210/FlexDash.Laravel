<?php

namespace App\Modules\Registration\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailVerification extends Model
{
    protected $table = 'email_verifications';

    protected $fillable = [
        'user_id',
        'verification_code',
        'expires_at',
        'attempts',
        'purpose',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'attempts'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
