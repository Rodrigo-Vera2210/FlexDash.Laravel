<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'categories', 'taxes', 'payment_methods', 'partners', 'products',
            'inventory_movements', 'sales', 'sale_details', 'purchases',
            'purchase_details', 'payments', 'audit_logs', 'cash_boxes'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'categories', 'taxes', 'payment_methods', 'partners', 'products',
            'inventory_movements', 'sales', 'sale_details', 'purchases',
            'purchase_details', 'payments', 'audit_logs', 'cash_boxes'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('company_id');
                });
            }
        }
    }
};
