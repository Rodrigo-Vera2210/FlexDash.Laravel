<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Illuminate\Support\Facades\DB::table('users')->updateOrInsert(
            ['email' => 'superadmin@flexdash.com'],
            [
                'name' => 'Super Admin',
                'password' => Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'superadmin',
                'status' => 'active',
                'company_id' => null,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Illuminate\Support\Facades\DB::table('users')->where('email', 'superadmin@flexdash.com')->delete();
    }
};
