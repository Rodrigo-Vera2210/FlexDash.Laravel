# Implementation Plan: Multi-Certificate Invoicing

This plan details the step-by-step technical implementation to enable multi-certificate support, plan limits, and invoicing default selections.

---

## 1. Database Migrations

### Step 1.1: Add Limits & Foreign Keys
- Add `max_certificates` integer to `plans` (defaults: `basic` = 1, `standard` = 3, `premium` = 9999).
- Add `max_certificates` integer (nullable) to `companies`.
- Add `certificate_id` (unsigned big integer, nullable) to `electronic_invoices` with a foreign key constraint to `company_certificates`.

### Step 1.2: Create `company_certificates` table
- Fields: `id`, `company_id` (nullable, foreign key to `companies`), `certificate_path`, `certificate_password` (encrypted text), `certificate_expires_at` (datetime), `owner_name`, `ruc` (nullable), `cedula` (nullable), `is_default` (boolean).
- Add unique constraint: a company can only have one certificate with `is_default = true`. (Handled via model validation / database constraints or database unique index is not direct due to SQLite limitations on partial indices; we will enforce this at the application layer/transaction).

### Step 1.3: Data Migration (Backwards Compatibility)
- Run a data migration script that looks at existing records in `billing_configs` that have `certificate_path`, extracts their metadata, creates a new entry in `company_certificates` with `is_default = true`, and links them.
- Once migrated, drop the deprecated fields (`certificate_path`, `certificate_password`, `certificate_expires_at`) from the `billing_configs` table to ensure clean database schemas.

---

## 2. Model & Repository Updates

### Step 2.1: `CompanyCertificate` Model
- Create `App\Modules\Billing\Models\CompanyCertificate.php`.
- Define `certificate_password` mutator using `Crypt::encryptString()` and accessor using `Crypt::decryptString()`.
- Add scopes: `default()` (where `is_default = true`).
- Relation: `company()`.

### Step 2.2: `Company` & `Plan` Models
- Update `Company` relationship: `hasMany(CompanyCertificate::class)`.
- Add accessor `getMaxCertificatesAttribute()` returning the company override or plan default.
- Add helper method `canUploadCertificate()` checking if the active count is under the limit.
- Update `Plan` to include `max_certificates` cast.

### Step 2.3: `ElectronicInvoice` Model
- Add `belongsTo(CompanyCertificate::class)` relationship.

---

## 3. Core Service Refactoring

### Step 3.1: `ElectronicInvoicingService` Refactoring
- Update the signature resolution to look up the default certificate (`company->companyCertificates()->where('is_default', true)->first()`), unless a specific `certificate_id` is supplied.
- Throw an exception if no active or default certificate is configured when attempting to process an invoice.

### Step 3.2: `XmlSignerService` Refactoring
- Adjust parameters to accept the resolved `CompanyCertificate` model, extracting the file binary and password dynamically.

---

## 4. Presentation & Controller Updates

### Step 4.1: `BillingSettingsController` (Tenants)
- **Index View**:
  - Pass the collection of `CompanyCertificate` records to the view.
- **Store Action**:
  - If a certificate file is uploaded:
    - Validate that the company does not exceed `max_certificates` limit.
    - Validate certificate password and tax ID match.
    - Save new record in `company_certificates`.
    - If it's the first certificate or the "default" checkbox is selected, mark it as default and clear default flags on other certificates.
- **New Actions**:
  - `setDefault(CompanyCertificate $certificate)`: Toggle default status.
  - `delete(CompanyCertificate $certificate)`: Delete the certificate file from storage and remove record. Don't allow if default unless it's the last one.

### Step 4.2: `SuperAdminBillingController` (Platform)
- Update platform billing configurations to support multiple certificates if desired, following a similar list structure but without company-specific limits or RUC validations.

---

## 5. UI Views (Redesign)

### Step 5.1: Settings View `config.blade.php`
- Replace the single-signature card with a **Certificados Digitales** management panel:
  - Table showing: Owner, RUC, Expiration Date, Status, Default Flag, Actions (Set Default / Delete).
  - Elegant modal or sub-form to "Añadir Firma Electrónica" containing file upload, password, and "Establecer como predeterminada" check.

### Step 5.2: Invoice Trigger Selection
- In sales list/details, when triggering "Emitir Factura Electrónica", display a selection drop-down if the company has multiple active certificates, allowing the user to select which signature profile to sign this invoice with.

---

## 6. Verification Plan

### Automated Tests
- Test certificate limit enforcement (`Basic` plan blocks second certificate, `Standard` blocks fourth).
- Test default certificate assignment logic (first certificate is automatically default; checking default checkbox updates other records).
- Test RUC mismatch rejection on certificate upload.
- Test invoice generation uses the selected certificate.
