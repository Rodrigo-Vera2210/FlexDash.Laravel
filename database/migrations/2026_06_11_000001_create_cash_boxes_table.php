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
        Schema::create('cash_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 10)->default('OPEN'); // OPEN, CLOSED
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('expected_closing_balance', 14, 2)->default(0);
            $table->decimal('actual_closing_balance', 14, 2)->nullable();
            $table->decimal('difference', 14, 2)->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });

        Schema::create('cash_box_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_box_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['ingreso', 'egreso']);
            $table->decimal('amount', 14, 2);
            $table->string('concept', 255);
            $table->timestamps();

            $table->index('cash_box_id');
            $table->index('user_id');
            $table->index('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_box_transactions');
        Schema::dropIfExists('cash_boxes');
    }
};
