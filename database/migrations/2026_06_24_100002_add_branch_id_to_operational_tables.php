<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
        });

        Schema::table('cash_boxes', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $tables = ['users', 'sales', 'purchases', 'cash_boxes', 'inventory_movements'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
