<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace global unique constraints with company-scoped composite unique constraints.
     *
     * Multi-tenant architecture requires uniqueness to be scoped per company_id,
     * not globally. This migration drops old single-column unique indexes and
     * replaces them with composite (company_id, column) unique indexes.
     */
    public function up(): void
    {
        // SQLite does not support DROP INDEX inside Schema::table() well,
        // so we use raw SQL for dropping indexes on SQLite.

        $this->replaceUnique('products', 'code', 'products_code_unique', 'products_company_code_unique');
        $this->replaceUnique('taxes', 'code', 'taxes_code_unique', 'taxes_company_code_unique');
        $this->replaceUnique('categories', 'name', 'categories_name_unique', 'categories_company_name_unique');
        $this->replaceUnique('payment_methods', 'name', 'payment_methods_name_unique', 'payment_methods_company_name_unique');
        $this->replaceUnique('partners', 'document_number', 'partners_document_number_unique', 'partners_company_document_unique');
        $this->replaceUnique('sales', 'number', 'sales_number_unique', 'sales_company_number_unique');
        $this->replaceUnique('purchases', 'number', 'purchases_number_unique', 'purchases_company_number_unique');
    }

    /**
     * Reverse: restore global unique constraints.
     */
    public function down(): void
    {
        $this->restoreUnique('products', 'code', 'products_company_code_unique', 'products_code_unique');
        $this->restoreUnique('taxes', 'code', 'taxes_company_code_unique', 'taxes_code_unique');
        $this->restoreUnique('categories', 'name', 'categories_company_name_unique', 'categories_name_unique');
        $this->restoreUnique('payment_methods', 'name', 'payment_methods_company_name_unique', 'payment_methods_name_unique');
        $this->restoreUnique('partners', 'document_number', 'partners_company_document_unique', 'partners_document_number_unique');
        $this->restoreUnique('sales', 'number', 'sales_company_number_unique', 'sales_number_unique');
        $this->restoreUnique('purchases', 'number', 'purchases_company_number_unique', 'purchases_number_unique');
    }

    /**
     * Drop a single-column unique index and create a composite (company_id, column) unique index.
     */
    private function replaceUnique(string $table, string $column, string $oldIndex, string $newIndex): void
    {
        // Drop the old global unique index
        if ($this->indexExists($table, $oldIndex)) {
            Schema::table($table, function (Blueprint $t) use ($oldIndex) {
                $t->dropUnique($oldIndex);
            });
        }

        // Create composite unique index (company_id, column)
        Schema::table($table, function (Blueprint $t) use ($column, $newIndex) {
            $t->unique(['company_id', $column], $newIndex);
        });
    }

    /**
     * Reverse: drop composite unique index and restore single-column unique index.
     */
    private function restoreUnique(string $table, string $column, string $compositeIndex, string $singleIndex): void
    {
        Schema::table($table, function (Blueprint $t) use ($compositeIndex) {
            $t->dropUnique($compositeIndex);
        });

        Schema::table($table, function (Blueprint $t) use ($column, $singleIndex) {
            $t->unique($column, $singleIndex);
        });
    }

    /**
     * Check if an index exists on a table (SQLite compatible).
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        // MySQL / PostgreSQL
        $indexes = Schema::getIndexListing($table);
        return in_array($indexName, $indexes);
    }
};
