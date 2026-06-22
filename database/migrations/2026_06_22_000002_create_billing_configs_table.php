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
        Schema::create('billing_configs', function (Blueprint $table) {
            $table->id();
            // company_id is nullable: a null company_id represents the SaaS platform/SuperAdmin configuration
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('certificate_path');
            $table->text('certificate_password'); // Stored encrypted
            $table->dateTime('certificate_expires_at');
            $table->string('establishment', 3);
            $table->string('emission_point', 3);
            $table->unsignedInteger('last_sequence')->default(0);
            $table->enum('environment', ['pruebas', 'produccion'])->default('pruebas');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_configs');
    }
};
