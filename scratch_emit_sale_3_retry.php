<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Sale\Models\Sale;
use App\Modules\Billing\Services\CertificateHelper;
use App\Modules\Billing\Services\ElectronicInvoicingService;
use App\Modules\Billing\Models\CompanyCertificate;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

$user = User::where('email', 'donra2210@gmail.com')->first();
auth()->login($user);

$sale = Sale::find(3);
$certificate = CompanyCertificate::where('company_id', 1)->where('is_default', true)->first();

// Increment sequence in the database to generate a fresh access key
\App\Modules\Billing\Models\BillingConfig::where('company_id', 1)->update(['last_sequence' => 70]);

$p12Content = Storage::get($certificate->certificate_path);
$password = $certificate->decrypted_password;

try {
    $helper = app(CertificateHelper::class);
    $certs = $helper->parsePkcs12($p12Content, $password);
    
    $publicKeyCert = $certs['cert'];
    $certData = openssl_x509_parse($publicKeyCert);
    
    echo "Using Certificate CN: " . ($certData['subject']['CN'] ?? 'None') . "\n";
    echo "Key Usage: " . ($certData['extensions']['keyUsage'] ?? 'None') . "\n";
    
    echo "Processing invoicing for Sale ID: 3...\n";
    $service = app(ElectronicInvoicingService::class);
    $invoice = $service->process($sale);
    
    echo "Invoiced successfully!\n";
    echo "Invoice ID: " . $invoice->id . "\n";
    echo "Status: " . $invoice->status . "\n";
    echo "Sequence: " . $invoice->sequence . "\n";
    echo "Access Key: " . $invoice->access_key . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
