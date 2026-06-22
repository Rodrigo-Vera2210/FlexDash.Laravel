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
        Schema::create('company_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('certificate_path');
            $table->text('certificate_password'); // encrypted
            $table->dateTime('certificate_expires_at');
            $table->string('owner_name');
            $table->string('ruc', 13)->nullable()->index();
            $table->string('cedula', 10)->nullable()->index();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();

            // Foreign key to companies
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_certificates');
    }
};
