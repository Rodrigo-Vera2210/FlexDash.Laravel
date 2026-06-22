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
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('has_electronic_billing')->default(false);
            $table->integer('monthly_invoice_limit')->default(0);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('has_electronic_billing')->nullable();
            $table->integer('monthly_invoice_limit')->nullable();
        });

        // Update standard and premium plans to have electronic billing enabled
        \Illuminate\Support\Facades\DB::table('plans')
            ->where('code', 'standard')
            ->update([
                'has_electronic_billing' => true,
                'monthly_invoice_limit' => 100,
            ]);

        \Illuminate\Support\Facades\DB::table('plans')
            ->where('code', 'premium')
            ->update([
                'has_electronic_billing' => true,
                'monthly_invoice_limit' => 1000,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['has_electronic_billing', 'monthly_invoice_limit']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['has_electronic_billing', 'monthly_invoice_limit']);
        });
    }
};
