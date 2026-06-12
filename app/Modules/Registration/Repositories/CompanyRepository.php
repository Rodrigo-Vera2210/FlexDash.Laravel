<?php

namespace App\Modules\Registration\Repositories;

use App\Modules\Registration\Models\Company;

class CompanyRepository
{
    public function __construct(
        protected Company $model
    ) {}

    /**
     * Create a new company record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * Find a company by its primary key.
     */
    public function findById(int $id): ?Company
    {
        /** @var Company|null */
        return $this->model->newQuery()->find($id);
    }

    /**
     * Find a company by its name.
     */
    public function findByName(string $name): ?Company
    {
        /** @var Company|null */
        return $this->model->newQuery()
            ->where('name', $name)
            ->first();
    }
}
