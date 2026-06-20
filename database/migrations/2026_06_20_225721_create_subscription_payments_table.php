<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('plan'); // basic, standard
            $table->string('bank_origin');
            $table->string('account_destination');
            $table->string('receipt_path');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('type')->default('signup'); // signup, upgrade, renewal
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
