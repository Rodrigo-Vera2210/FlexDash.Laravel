<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->enum('company_type', ['legal_entity', 'natural_person']);
            $table->string('name');
            $table->string('tax_id')->nullable();
            $table->string('legal_address')->nullable();
            $table->string('address')->nullable();
            $table->string('city');
            $table->string('state_province');
            $table->string('postal_code');
            $table->string('country', 64);
            $table->boolean('legal_entity_flag')->default(false);
            $table->boolean('natural_entity_flag')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
