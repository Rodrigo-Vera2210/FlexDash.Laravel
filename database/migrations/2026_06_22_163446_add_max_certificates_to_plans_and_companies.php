<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('max_certificates')->default(1)->after('monthly_invoice_limit');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->integer('max_certificates')->nullable()->after('monthly_invoice_limit');
        });

        // Set default limits for existing plans
        DB::table('plans')->where('code', 'basic')->update(['max_certificates' => 1]);
        DB::table('plans')->where('code', 'standard')->update(['max_certificates' => 3]);
        DB::table('plans')->where('code', 'premium')->update(['max_certificates' => 9999]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('max_certificates');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('max_certificates');
        });
    }
};
