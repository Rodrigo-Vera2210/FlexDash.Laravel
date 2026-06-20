<?php

namespace App\Modules\Registration\Repositories;

use App\Modules\Registration\Models\EmailVerification;
use Illuminate\Support\Facades\DB;

class EmailVerificationRepository
{
    public function __construct(
        protected EmailVerification $model
    ) {}

    /**
     * Create a new email verification record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): EmailVerification
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * Return the latest non-expired verification record for a given user.
     */
    public function findActiveByUserId(int $userId): ?EmailVerification
    {
        /** @var EmailVerification|null */
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Delete all verification records belonging to a user.
     */
    public function deleteByUserId(int $userId): void
    {
        $this->model->newQuery()
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Increment the attempts counter for a verification record.
     */
    public function incrementAttempts(int $verificationId): void
    {
        $this->model->newQuery()
            ->where('id', $verificationId)
            ->increment('attempts');
    }
}
