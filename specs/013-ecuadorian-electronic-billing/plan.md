# Technical Implementation Plan: Ecuadorian Electronic Invoicing (SRI)

**Branch**: `013-ecuadorian-electronic-billing` | **Date**: 2026-06-22

**Spec**: `/specs/013-ecuadorian-electronic-billing/spec.md`

---

## 1. Summary of Technical Approach

1. **New Module Structure**: Create `app/Modules/Billing` to contain all logic, endpoints, models, views, and tests associated with electronic invoicing.
2. **Database Migrations**:
   - Create tables `billing_configs` and `electronic_invoices` for sequence tracking, signature keys, and status logs.
   - Add column `has_electronic_billing` (boolean) to the `plans` table and add `subscription_plan_overrides` mapping or direct column in `companies` table to govern tenant plan restrictions.
   - Modify partner document types constraints to explicitly support Ecuadorian CI (Cédula de Identidad), RUC, and Pasaporte.
3. **Digital Signature Processing**:
   - Parse uploaded `.p12` certificates using PHP's native `openssl_pkcs12_read()`.
   - Validate certificate parameters, compute expiration date, and store the password using Laravel's native encryption (`Crypt::encryptString`).
   - Implement XAdES-BES XML digital signature using a service wrapping PHP standard cryptographic signatures (SHA256, DigestMethod, and PKCS#12 key extraction).
4. **SOAP Client Wrapper**:
   - Write an SRI Client Service using PHP's native `SoapClient` to invoke SRI's offline SOAP methods:
     - `validarComprobante` (Reception)
     - `autorizacionComprobante` (Authorization)
5. **RIDE Generator Service**:
   - Build a Blade template containing invoice details, barcodes, and SRI access key styling rules.
   - Wrap a PDF package (e.g. `dompdf/dompdf` or `barryvdh/laravel-dompdf`) to render the RIDE format cleanly.
6. **Queue Job Flow**:
   - Trigger electronic invoicing via `EmitElectronicInvoiceJob` to avoid blocking request/response cycles.
   - The job will sequentially build, sign, receive, authorize, render, and email the invoice documents to customers.

---

## 2. Project File Structure

```text
app/Modules/Billing/
├── Controllers/
│   ├── BillingSettingsController.php        [NEW] - For certificate and sequence settings
│   ├── InvoiceController.php                [NEW] - For POS invoice viewing and lists
│   └── SuperAdminBillingController.php      [NEW] - For platform billing settings
├── Models/
│   ├── BillingConfig.php                    [NEW] - Config record (tenant / superadmin)
│   └── ElectronicInvoice.php                [NEW] - Tracks issued invoices
├── Services/
│   ├── XmlGeneratorService.php              [NEW] - Generates SRI Factura XML (XSD compliance)
│   ├── XmlSignerService.php                 [NEW] - Signs XML via XAdES-BES
│   ├── SriSoapClientService.php             [NEW] - Handles WSDL communication with SRI
│   ├── RideGeneratorService.php             [NEW] - Renders PDF RIDE format
│   └── ElectronicInvoicingService.php       [NEW] - Coordinates the 6-step flow
├── Jobs/
│   └── ProcessElectronicInvoiceJob.php      [NEW] - Queued background invoice processor
├── Views/
│   ├── settings/
│   │   └── config.blade.php                 [NEW] - Configuration UI for signature/points
│   ├── invoices/
│   │   ├── index.blade.php                  [NEW] - List of issued invoices
│   │   └── ride.blade.php                   [NEW] - HTML template for PDF RIDE
│   └── superadmin/
│       └── billing.blade.php                [NEW] - Superadmin settings screen
└── Tests/
    ├── Feature/
    │   ├── ElectronicBillingSettingsTest.php [NEW] - Tests for configuration/p12 uploads
    │   ├── InvoiceGenerationFlowTest.php    [NEW] - Mocked flows for Reception/Authorization
    │   └── SubscriptionPlanInvoicingTest.php [NEW] - Tests plan restriction and SuperAdmin billing
    └── Unit/
        └── AccessKeyGeneratorTest.php       [NEW] - Verifies 49-digit access keys and check-digits

database/migrations/
├── 2026_06_22_000002_create_billing_configs_table.php                    [NEW]
├── 2026_06_22_000003_create_electronic_invoices_table.php                 [NEW]
└── 2026_06_22_000004_add_electronic_billing_fields_to_plans_and_companies.php [NEW]
```

---

## 3. Implementation Details

### A. Database Migrations

```php
// database/migrations/2026_06_22_000002_create_billing_configs_table.php
Schema::create('billing_configs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade'); // Null represents SuperAdmin
    $table->string('certificate_path');
    $table->text('certificate_password'); // encrypted
    $table->dateTime('certificate_expires_at');
    $table->string('establishment', 3);
    $table->string('emission_point', 3);
    $table->unsignedInteger('last_sequence')->default(0);
    $table->enum('environment', ['pruebas', 'produccion'])->default('pruebas');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// database/migrations/2026_06_22_000003_create_electronic_invoices_table.php
Schema::create('electronic_invoices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade'); // Null for platform invoices
    $table->morphs('invoicable'); // morph link to Sale or SubscriptionPayment
    $table->string('access_key', 49)->unique();
    $table->string('sequence', 15); // e.g., '001-001-000000001'
    $table->enum('status', ['draft', 'signed', 'received', 'authorized', 'failed'])->default('draft');
    $table->string('xml_path')->nullable();
    $table->string('pdf_path')->nullable();
    $table->text('sri_error_details')->nullable();
    $table->dateTime('authorized_at')->nullable();
    $table->timestamps();
});
```

### B. Security and Certificate Validation

To read the `.p12` file parameters (expiration date, owner, validity):
```php
public function validateAndExtractCertDetails(string $p12Path, string $password): array
{
    $p12Content = file_get_contents($p12Path);
    $certs = [];
    if (!openssl_pkcs12_read($p12Content, $certs, $password)) {
        throw new \Exception("Contraseña o formato del certificado .p12 incorrecto.");
    }
    
    $data = openssl_x509_parse($certs['cert']);
    $expiresAt = \Carbon\Carbon::createFromTimestampUTC($data['validTo_time_t']);
    
    if ($expiresAt->isPast()) {
         throw new \Exception("El certificado ha expirado el: " . $expiresAt->toDateString());
    }

    return [
        'subject' => $data['subject']['CN'] ?? '',
        'expires_at' => $expiresAt,
    ];
}
```

### C. XML Generation & Signing (XAdES-BES)
- `XmlGeneratorService` translates Sale / Payment attributes into the legal XML format. Columns are mapped to Ecuadorian standards (e.g. document types CI: `05`, RUC: `04`, Pasaporte: `06`).
- `XmlSignerService` implements the XAdES-BES signature structure. Signature requires inserting nodes: `ds:Signature`, `ds:SignedInfo`, `ds:SignatureValue`, `ds:KeyInfo`, and `ds:Object` (containing `xades:QualifyingProperties`). The signer will run locally in PHP using OpenSSL or execute a small command wrapper/native package to sign cleanly without external API dependencies.

### D. SOAP Client integration
A SOAP client wrapper will target test/production endpoints:
```php
// WSDL URLs for SRI Recepcion & Autorizacion
$repcionUrl = $env === 'produccion'
    ? 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl'
    : 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';

$authUrl = $env === 'produccion'
    ? 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl'
    : 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';
```

### E. Triggering Electronic Invoicing in POS Sales
- The transaction flow remains: Sale recorded -> payment settled -> invoice triggered.
- A new database column `invoiced_status` (defaults to `not_invoiced`) is added to sales.
- In `sales.show` blade, if `invoiced_status === 'not_invoiced'` and payment status is `completed`, cashier sees a button: **"Facturar electrónicamente"**.
- Clicking this triggers a POST request to `/billing/invoices/generate`, launching `ProcessElectronicInvoiceJob` which scopes the query, generates, signs, reception checks, queries authorization, writes details, generates PDF RIDE, and fires the email.

---

## 4. Verification Plan

### Automated Tests
1. **Settings configuration validation**:
   - Assert upload certificate works, correctly encrypts certificate password, extracts expiration date, and fails if certificate format or password is invalid.
2. **XML Construction and Signing unit tests**:
   - Assert XML structures have correct SRI tags and valid 49-digit access key check-digit calculation (Modulo 11 algorithm).
   - Assert signed XML includes correct XAdES signature blocks.
3. **Moked SRI SOAP Requests feature tests**:
   - Mock Reception SOAP calls returning successful status (`RECIBIDA`) and failed status (`DEVUELTA` with validation error messages).
   - Mock Authorization SOAP calls returning `AUTORIZADO` and `NO AUTORIZADO`.
4. **Subscription limits integration**:
   - Assert companies with plans without electronic billing cannot load the page or POST billing actions.
   - Assert SuperAdmin can set billing configs, approve payments, and trigger client invoicing.

### Manual Verification
1. Log in as tenant -> settings -> Facturación Electrónica.
2. Upload test `.p12` certificate file, specify password, set sequence inputs -> verify successful validation and extraction of certificate fields.
3. Complete a sale -> go to sale details -> click "Emitir Factura" -> check state badge changes, files are generated in storage, and mock log demonstrates receipt.
4. Log in as SuperAdmin -> approve subscription payment -> click "Emitir Factura al Suscriptor" -> check platform invoicing flow executes.
