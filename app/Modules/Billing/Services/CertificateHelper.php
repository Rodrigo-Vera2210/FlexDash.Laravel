<?php

namespace App\Modules\Billing\Services;

use Carbon\Carbon;
use Exception;

class CertificateHelper
{
    /**
     * Parse a .p12 certificate file content and extract metadata.
     *
     * @param string $p12Content Binary content of the .p12 file.
     * @param string $password The password to open the certificate.
     * @return array
     * @throws Exception
     */
    public function extractMetadata(string $p12Content, string $password): array
    {
        $certs = $this->parsePkcs12($p12Content, $password);

        if (!isset($certs['cert'])) {
            throw new Exception("El archivo .p12 no contiene un certificado válido.");
        }

        // Parse x509 certificate parameters
        $certData = openssl_x509_parse($certs['cert']);
        if (!$certData) {
            throw new Exception("No se pudo analizar la estructura del certificado x509.");
        }

        $expiresAtTimestamp = $certData['validTo_time_t'] ?? null;
        if (!$expiresAtTimestamp) {
            throw new Exception("No se pudo determinar el periodo de vigencia del certificado.");
        }

        $expiresAt = Carbon::createFromTimestamp($expiresAtTimestamp);

        if ($expiresAt->isPast()) {
            throw new Exception("El certificado cargado ha expirado el " . $expiresAt->format('d/m/Y H:i:s') . ".");
        }

        // Extract Common Name (CN) or Organization Name (O)
        $ownerName = $certData['subject']['CN'] ?? $certData['subject']['O'] ?? 'Titular desconocido';

        // Extract OIDs for RUC and Cédula (Ecuadorian standard)
        $extensions = $certData['extensions'] ?? [];
        $ruc = null;
        if (isset($extensions['1.3.6.1.4.1.37947.3.11'])) {
            $ruc = preg_replace('/[^0-9]/', '', $extensions['1.3.6.1.4.1.37947.3.11']);
        }
        $cedula = null;
        if (isset($extensions['1.3.6.1.4.1.37947.3.1'])) {
            $cedula = preg_replace('/[^0-9]/', '', $extensions['1.3.6.1.4.1.37947.3.1']);
        }

        return [
            'owner_name' => $ownerName,
            'expires_at' => $expiresAt,
            'subject'    => $certData['subject'] ?? [],
            'issuer'     => $certData['issuer'] ?? [],
            'ruc'        => $ruc,
            'cedula'     => $cedula,
        ];
    }

    /**
     * Parse a .p12 certificate file content robustly, supporting legacy OpenSSL 3.x algorithms.
     *
     * @param string $p12Content Binary content of the .p12 file.
     * @param string $password The password to open the certificate.
     * @return array Contains 'cert' and 'pkey'.
     * @throws Exception
     */
    public function parsePkcs12(string $p12Content, string $password): array
    {
        $certs = [];

        // 1. Try standard PHP function first
        if (openssl_pkcs12_read($p12Content, $certs, $password)) {
            $allCerts = array_merge([$certs['cert']], $certs['extracerts'] ?? []);
            $selectedCert = null;
            foreach ($allCerts as $certPem) {
                if (!$certPem) continue;
                $data = openssl_x509_parse($certPem);
                if ($data) {
                    $extensions = $data['extensions'] ?? [];
                    $keyUsage = $extensions['keyUsage'] ?? '';
                    $basicConstraints = $extensions['basicConstraints'] ?? '';
                    $isCA = str_contains($basicConstraints, 'CA:TRUE');
                    $isSignature = str_contains(strtolower($keyUsage), 'digital signature') || str_contains(strtolower($keyUsage), 'non repudiation') || str_contains(strtolower($keyUsage), 'non-repudiation');
                    
                    if (!$isCA && $isSignature) {
                        $selectedCert = $certPem;
                        break;
                    }
                }
            }
            if ($selectedCert) {
                // Verify the private key matches the selected signature certificate
                if (isset($certs['pkey']) && openssl_x509_check_private_key($selectedCert, $certs['pkey'])) {
                    $certs['cert'] = $selectedCert;
                    return $certs;
                }
            }
            // If they don't match, or if we didn't find the signature certificate,
            // we proceed to CLI fallback to find the matching key.
        }

        // Retrieve OpenSSL error message if available
        $errors = [];
        while ($error = openssl_error_string()) {
            $errors[] = $error;
        }
        $errorMsg = !empty($errors) ? implode('; ', $errors) : 'Clave de firma incorrecta o archivo dañado.';

        // 2. If it fails, fall back to CLI openssl with -legacy flag
        $tempP12 = tempnam(sys_get_temp_dir(), 'p12_');
        file_put_contents($tempP12, $p12Content);

        // Escape arguments safely for CLI execution
        $escapedPassword = PHP_OS_FAMILY === 'Windows' 
            ? '"' . str_replace('"', '""', $password) . '"'
            : escapeshellarg($password);

        $escapedPath = PHP_OS_FAMILY === 'Windows'
            ? '"' . str_replace('"', '""', $tempP12) . '"'
            : escapeshellarg($tempP12);

        // Resolve OpenSSL binary path dynamically
        $opensslBin = $this->getOpenSslBinary();

        // Run openssl pkcs12 with -legacy flag
        $cmd = "{$opensslBin} pkcs12 -in {$escapedPath} -nodes -passin pass:{$escapedPassword} -legacy 2>" . (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null');
        $output = [];
        $resultCode = -1;

        exec($cmd, $output, $resultCode);
        @unlink($tempP12);

        if ($resultCode === 0 && !empty($output)) {
            $pemContent = implode("\n", $output);

            // Extract all private keys
            $pkeys = [];
            if (preg_match_all('/(-----BEGIN PRIVATE KEY-----.*?-----END PRIVATE KEY-----)/s', $pemContent, $matches)) {
                $pkeys = $matches[1];
            }
            if (empty($pkeys) && preg_match_all('/(-----BEGIN RSA PRIVATE KEY-----.*?-----END RSA PRIVATE KEY-----)/s', $pemContent, $matches)) {
                $pkeys = $matches[1];
            }

            // Extract all certs
            $allCerts = [];
            if (preg_match_all('/(-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----)/s', $pemContent, $matches)) {
                $allCerts = $matches[1];
            }

            // Find the digital signature one
            $selectedCert = null;
            foreach ($allCerts as $certPem) {
                $data = openssl_x509_parse($certPem);
                if ($data) {
                    $extensions = $data['extensions'] ?? [];
                    $keyUsage = $extensions['keyUsage'] ?? '';
                    $basicConstraints = $extensions['basicConstraints'] ?? '';
                    
                    $isCA = str_contains($basicConstraints, 'CA:TRUE');
                    $isSignature = str_contains(strtolower($keyUsage), 'digital signature') || str_contains(strtolower($keyUsage), 'non repudiation') || str_contains(strtolower($keyUsage), 'non-repudiation');
                    
                    if (!$isCA && $isSignature) {
                        $selectedCert = $certPem;
                        break;
                    }
                }
            }

            // Fallback to first cert if none matches the signature criteria
            if (!$selectedCert && !empty($allCerts)) {
                $selectedCert = $allCerts[0];
            }

            // Find the private key matching the selected certificate
            $selectedKey = null;
            if ($selectedCert) {
                foreach ($pkeys as $pkeyPem) {
                    if (openssl_x509_check_private_key($selectedCert, $pkeyPem)) {
                        $selectedKey = $pkeyPem;
                        break;
                    }
                }
            }

            // Fallback to first private key if no match found
            if (!$selectedKey && !empty($pkeys)) {
                $selectedKey = $pkeys[0];
            }

            if ($selectedKey && $selectedCert) {
                return [
                    'pkey' => $selectedKey,
                    'cert' => $selectedCert
                ];
            }
        }

        $cliError = !empty($output) ? implode('; ', array_slice($output, 0, 3)) : 'No output';
        \Illuminate\Support\Facades\Log::warning("Certificate PKCS12 CLI fallback failed: Code {$resultCode}, Cmd: {$cmd}, Out: {$cliError}");

        throw new Exception("Error al leer el certificado digital: " . $errorMsg);
    }

    /**
     * Get the path to the openssl binary.
     *
     * @return string
     */
    private function getOpenSslBinary(): string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return 'openssl';
        }

        // Try where.exe first (redirecting stderr to NUL to avoid noise)
        $whereOut = [];
        $whereCode = -1;
        @exec('where.exe openssl 2>NUL', $whereOut, $whereCode);
        if ($whereCode === 0 && !empty($whereOut)) {
            return '"' . trim($whereOut[0]) . '"';
        }

        // Common paths on Windows
        $commonPaths = [
            'C:\Program Files\Git\mingw64\bin\openssl.exe',
            'C:\Program Files\Git\usr\bin\openssl.exe',
            'C:\xampp\apache\bin\openssl.exe',
            'C:\xampp\php\extras\openssl\openssl.exe',
        ];

        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                return '"' . $path . '"';
            }
        }

        return 'openssl';
    }
}
