<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add certificate_id to electronic_invoices
        Schema::table('electronic_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('certificate_id')->nullable()->after('company_id');
            $table->foreign('certificate_id')->references('id')->on('company_certificates')->onDelete('set null');
        });

        // 2. Data Migration: Move certificates from billing_configs to company_certificates
        $configs = DB::table('billing_configs')->whereNotNull('certificate_path')->get();

        foreach ($configs as $config) {
            $ownerName = 'Migrated Certificate';
            $ruc = null;
            $cedula = null;

            try {
                // Read certificate file if it exists and parse it using CertificateHelper
                if ($config->certificate_path && Storage::exists($config->certificate_path)) {
                    $content = Storage::get($config->certificate_path);
                    $decryptedPass = Crypt::decryptString($config->certificate_password);
                    
                    $helper = new \App\Modules\Billing\Services\CertificateHelper();
                    $meta = $helper->extractMetadata($content, $decryptedPass);
                    
                    $ownerName = $meta['owner_name'] ?? $ownerName;
                    $ruc = $meta['ruc'] ?? null;
                    $cedula = $meta['cedula'] ?? null;
                } else {
                    // Fallback to company RUC if files do not exist (e.g. tests database or local seeders)
                    if ($config->company_id) {
                        $company = DB::table('companies')->where('id', $config->company_id)->first();
                        if ($company && $company->tax_id) {
                            $ruc = $company->tax_id;
                            $cedula = substr($company->tax_id, 0, 10);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Migration: Failed to parse certificate for config ID {$config->id}: " . $e->getMessage());
                // Fallback to company RUC
                if ($config->company_id) {
                    $company = DB::table('companies')->where('id', $config->company_id)->first();
                    if ($company && $company->tax_id) {
                        $ruc = $company->tax_id;
                        $cedula = substr($company->tax_id, 0, 10);
                    }
                }
            }

            DB::table('company_certificates')->insert([
                'company_id' => $config->company_id,
                'certificate_path' => $config->certificate_path,
                'certificate_password' => $config->certificate_password,
                'certificate_expires_at' => $config->certificate_expires_at ?? now()->addYear(),
                'owner_name' => $ownerName,
                'ruc' => $ruc,
                'cedula' => $cedula,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Drop deprecated certificate columns from billing_configs
        Schema::table('billing_configs', function (Blueprint $table) {
            $table->dropColumn(['certificate_path', 'certificate_password', 'certificate_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add deprecated columns to billing_configs
        Schema::table('billing_configs', function (Blueprint $table) {
            $table->string('certificate_path')->nullable()->after('company_id');
            $table->text('certificate_password')->nullable()->after('certificate_path');
            $table->dateTime('certificate_expires_at')->nullable()->after('certificate_password');
        });

        // 2. Data Migration: Restore from company_certificates back to billing_configs
        $certs = DB::table('company_certificates')->where('is_default', true)->get();
        foreach ($certs as $cert) {
            DB::table('billing_configs')
                ->where('company_id', $cert->company_id)
                ->update([
                    'certificate_path' => $cert->certificate_path,
                    'certificate_password' => $cert->certificate_password,
                    'certificate_expires_at' => $cert->certificate_expires_at,
                ]);
        }

        // 3. Drop columns and foreign keys from electronic_invoices
        Schema::table('electronic_invoices', function (Blueprint $table) {
            $table->dropForeign(['certificate_id']);
            $table->dropColumn('certificate_id');
        });
    }
};
