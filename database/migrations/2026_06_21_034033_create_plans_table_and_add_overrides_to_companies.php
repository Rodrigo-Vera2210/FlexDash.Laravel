<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('price', 8, 2);
            $table->integer('max_admins');
            $table->integer('max_sellers');
            $table->integer('max_monthly_transactions');
            $table->text('modules');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->text('active_modules')->nullable()->after('subscription_status');
            $table->integer('max_monthly_transactions')->nullable()->after('active_modules');
            $table->integer('max_admins')->nullable()->after('max_monthly_transactions');
            $table->integer('max_sellers')->nullable()->after('max_admins');
        });

        // Seed default plans
        \Illuminate\Support\Facades\DB::table('plans')->insert([
            [
                'name' => 'Plan Basic',
                'code' => 'basic',
                'price' => 29.00,
                'max_admins' => 1,
                'max_sellers' => 2,
                'max_monthly_transactions' => 100,
                'modules' => json_encode(['ventas', 'clientes', 'caja_chica', 'settings', 'kardex']),
                'is_active' => true,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
            [
                'name' => 'Plan Standard',
                'code' => 'standard',
                'price' => 59.00,
                'max_admins' => 2,
                'max_sellers' => 10,
                'max_monthly_transactions' => 500,
                'modules' => json_encode(['ventas', 'clientes', 'caja_chica', 'settings', 'kardex', 'compras', 'proveedores']),
                'is_active' => true,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
            [
                'name' => 'Plan Premium',
                'code' => 'premium',
                'price' => 99.00,
                'max_admins' => 9999,
                'max_sellers' => 9999,
                'max_monthly_transactions' => 999999,
                'modules' => json_encode(['ventas', 'clientes', 'caja_chica', 'settings', 'kardex', 'compras', 'proveedores']),
                'is_active' => true,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['active_modules', 'max_monthly_transactions', 'max_admins', 'max_sellers']);
        });

        Schema::dropIfExists('plans');
    }
};
