<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Billing\Services\CertificateHelper;

$p12Path = __DIR__.'/storage/app/public/certificado/firma_0803592435 (2).p12';
$password = 'RODRIgo#2210';
$p12Content = file_get_contents($p12Path);

// Let's run the openssl command directly to see the full output
$tempP12 = tempnam(sys_get_temp_dir(), 'p12_');
file_put_contents($tempP12, $p12Content);

$escapedPassword = PHP_OS_FAMILY === 'Windows' 
    ? '"' . str_replace('"', '""', $password) . '"'
    : escapeshellarg($password);

$escapedPath = PHP_OS_FAMILY === 'Windows'
    ? '"' . str_replace('"', '""', $tempP12) . '"'
    : escapeshellarg($tempP12);

// Resolve OpenSSL binary path dynamically
$opensslBin = 'openssl';
if (PHP_OS_FAMILY === 'Windows') {
    $whereOut = [];
    $whereCode = -1;
    @exec('where.exe openssl 2>NUL', $whereOut, $whereCode);
    if ($whereCode === 0 && !empty($whereOut)) {
        $opensslBin = '"' . trim($whereOut[0]) . '"';
    } else {
        $commonPaths = [
            'C:\Program Files\Git\mingw64\bin\openssl.exe',
            'C:\Program Files\Git\usr\bin\openssl.exe',
            'C:\xampp\apache\bin\openssl.exe',
            'C:\xampp\php\extras\openssl\openssl.exe',
        ];
        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                $opensslBin = '"' . $path . '"';
                break;
            }
        }
    }
}

$cmd = "{$opensslBin} pkcs12 -in {$escapedPath} -nodes -passin pass:{$escapedPassword} -legacy 2>NUL";
$output = [];
$resultCode = -1;
exec($cmd, $output, $resultCode);
@unlink($tempP12);

if ($resultCode !== 0 || empty($output)) {
    echo "CLI command failed with code $resultCode. Command used: $cmd\n";
    exit;
}

$pemContent = implode("\n", $output);

// Find all certificates
preg_match_all('/(-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----)/s', $pemContent, $matches);

echo "Found " . count($matches[0]) . " certificates in .p12 output:\n\n";

foreach ($matches[0] as $index => $certPem) {
    $data = openssl_x509_parse($certPem);
    echo "=== CERTIFICATE #$index ===\n";
    echo "Subject: " . ($data['name'] ?? 'Unknown') . "\n";
    echo "CN: " . ($data['subject']['CN'] ?? 'None') . "\n";
    echo "RUC: " . ($data['extensions']['1.3.6.1.4.1.37947.3.11'] ?? 'None') . "\n";
    echo "Key Usage: " . ($data['extensions']['keyUsage'] ?? 'None') . "\n";
    echo "Basic Constraints: " . ($data['extensions']['basicConstraints'] ?? 'None') . "\n";
    echo "\n";
}
