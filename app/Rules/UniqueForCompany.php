<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Closure;

/**
 * Validates that a value is unique within the authenticated user's company.
 *
 * Usage:
 *   new UniqueForCompany('products', 'code')              // for store
 *   new UniqueForCompany('products', 'code', $product->id) // for update (ignore self)
 */
class UniqueForCompany implements ValidationRule
{
    public function __construct(
        protected string $table,
        protected string $column,
        protected ?int $ignoreId = null,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $companyId = auth()->user()?->company_id;

        $query = DB::table($this->table)
            ->where($this->column, $value)
            ->where('company_id', $companyId);

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        // For tables with soft deletes, exclude soft-deleted records
        if ($this->tableHasSoftDeletes()) {
            $query->whereNull('deleted_at');
        }

        if ($query->exists()) {
            $fail('El valor de :attribute ya está en uso.');
        }
    }

    /**
     * Check if the table has a deleted_at column (soft deletes).
     */
    private function tableHasSoftDeletes(): bool
    {
        static $cache = [];

        if (!isset($cache[$this->table])) {
            $cache[$this->table] = DB::getSchemaBuilder()->hasColumn($this->table, 'deleted_at');
        }

        return $cache[$this->table];
    }
}
