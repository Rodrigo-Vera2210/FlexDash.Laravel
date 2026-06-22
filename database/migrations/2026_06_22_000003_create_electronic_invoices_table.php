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
        Schema::create('electronic_invoices', function (Blueprint $table) {
            $table->id();
            // company_id is nullable (null represents invoices issued by SuperAdmin/the SaaS platform itself)
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->morphs('invoicable'); // invoicable_type and invoicable_id
            $table->string('access_key', 49)->unique();
            $table->string('sequence', 15); // e.g. 001-001-000000001
            $table->enum('status', ['draft', 'signed', 'received', 'authorized', 'failed'])->default('draft');
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->text('sri_error_details')->nullable();
            $table->dateTime('authorized_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electronic_invoices');
    }
};
