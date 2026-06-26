<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->integer('duration_months')->default(1)->after('type');
            $table->decimal('discount_percentage', 5, 2)->default(0.00)->after('duration_months');
            $table->decimal('amount', 10, 2)->default(0.00)->after('discount_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropColumn(['duration_months', 'discount_percentage', 'amount']);
        });
    }
};
