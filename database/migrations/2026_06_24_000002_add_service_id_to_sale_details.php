<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            // Add service_id as nullable FK
            $table->unsignedBigInteger('service_id')->nullable()->after('product_id');
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
            $table->index('service_id');
        });

        // Make product_id nullable (was required before)
        // SQLite doesn't support ALTER COLUMN directly, so we handle via pragma
        if (config('database.default') === 'sqlite') {
            // For SQLite, we need to recreate the column constraint
            // Since Laravel 12 handles this with the change() method via doctrine
            // We'll use a raw approach that's SQLite compatible
            DB::statement('PRAGMA foreign_keys=off');
            
            // Create temp table with new schema
            Schema::create('sale_details_temp', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->unsignedBigInteger('service_id')->nullable();
                $table->decimal('quantity', 14, 4);
                $table->decimal('unit_price', 14, 4);
                $table->decimal('cost_price', 14, 4)->default(0);
                $table->decimal('discount', 14, 2)->default(0);
                $table->decimal('subtotal', 14, 2);
                $table->string('notes')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->timestamps();

                $table->index('sale_id');
                $table->index('product_id');
                $table->index('service_id');
            });

            // Copy data
            DB::statement('INSERT INTO sale_details_temp (id, sale_id, product_id, service_id, quantity, unit_price, cost_price, discount, subtotal, notes, company_id, created_at, updated_at) SELECT id, sale_id, product_id, NULL, quantity, unit_price, cost_price, discount, subtotal, notes, company_id, created_at, updated_at FROM sale_details');
            
            Schema::drop('sale_details');
            Schema::rename('sale_details_temp', 'sale_details');

            DB::statement('PRAGMA foreign_keys=on');
        } else {
            // For MySQL/PostgreSQL
            Schema::table('sale_details', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });
    }
};
