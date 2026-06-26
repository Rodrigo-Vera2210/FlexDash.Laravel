<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('max_branches')->default(1)->after('max_certificates');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->integer('max_branches')->nullable()->after('monthly_invoice_limit');
        });

        // Seed default max_branches values for existing plans
        DB::table('plans')->where('code', 'basic')->update(['max_branches' => 1]);
        DB::table('plans')->where('code', 'standard')->update(['max_branches' => 3]);
        DB::table('plans')->where('code', 'premium')->update(['max_branches' => 9999]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('max_branches');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('max_branches');
        });
    }
};
