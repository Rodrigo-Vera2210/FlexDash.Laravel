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
        Schema::table('users', function (Blueprint $table) {
            // User preferences (optional fields with sensible defaults)
            $table->enum('theme_preference', ['light', 'dark', 'system'])->default('system')->after('status');
            $table->enum('language', ['es', 'en'])->default('es')->after('theme_preference');
            $table->string('timezone')->default('America/Guayaquil')->after('language');
            $table->boolean('notifications_enabled')->default(true)->after('timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'theme_preference',
                'language',
                'timezone',
                'notifications_enabled',
            ]);
        });
    }
};
