<?php

namespace App\Modules\Registration\Repositories;

use App\Models\User;

class UserRepository
{
    public function __construct(
        protected User $model
    ) {}

    /**
     * Create a new user record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * Find a user by its primary key.
     */
    public function findById(int $id): ?User
    {
        /** @var User|null */
        return $this->model->newQuery()->find($id);
    }

    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?User
    {
        /** @var User|null */
        return $this->model->newQuery()
            ->where('email', $email)
            ->first();
    }

    /**
     * Update the status of a user.
     */
    public function updateStatus(int $userId, string $status): bool
    {
        return (bool) $this->model->newQuery()
            ->where('id', $userId)
            ->update(['status' => $status]);
    }
}
