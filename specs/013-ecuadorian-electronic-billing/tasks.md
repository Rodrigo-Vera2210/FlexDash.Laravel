# Tasks: Ecuadorian Electronic Invoicing (SRI)

## Phase 1: Database Schema & Migration

- [x] T001 Create migration `2026_06_22_000002_create_billing_configs_table.php` to store `.p12` certificates (path, encrypted passwords, expiration, establishment, emission point, and sequential counters) for tenants and the SuperAdmin.
- [x] T002 Create migration `2026_06_22_000003_create_electronic_invoices_table.php` to track morphable invoicing link (`invoicable_type`, `invoicable_id`), sequence string, access keys, statuses, PDF/XML file paths, and SRI errors.
- [x] T003 Create migration `2026_06_22_000004_add_electronic_billing_fields_to_plans_and_companies.php` adding boolean `has_electronic_billing` column and integer invoice limits to plans and company overrides.
- [x] T004 Run `php artisan migrate` to create billing tables.
- [x] T005 Update the Partner database schema or validations to explicitly support CI (Cédula), RUC, and Pasaporte identification types, verifying constraint limits.

---

## Phase 2: Configuration Panels (UI & Logic)

- [x] T006 Create `app/Modules/Billing/Models/BillingConfig.php` and `app/Modules/Billing/Models/ElectronicInvoice.php`.
- [x] T007 Implement certificate encryption logic in `BillingConfig` using `Crypt` for the `.p12` password.
- [x] T008 Implement PKCS#12 certificate parsing logic in a helper class or service using `openssl_pkcs12_read()` to validate password correctness, validity dates, and expiration date checks.
- [x] T009 Create `app/Modules/Billing/Controllers/BillingSettingsController.php` implementing settings GET index and POST save methods (validating point, establishment, and uploading `.p12` files to a secure directory).
- [x] T010 Create settings blade view `app/Modules/Billing/Views/settings/config.blade.php` with file inputs, password fields, sequence inputs, and status badges.
- [x] T011 Create `app/Modules/Billing/Controllers/SuperAdminBillingController.php` and its blade view `app/Modules/Billing/Views/superadmin/billing.blade.php` to enable platform-level electronic invoicing configuration.

---

## Phase 3: Core Billing Services (XML, Signing, SOAP, RIDE)

- [x] T012 Create `app/Modules/Billing/Services/XmlGeneratorService.php` which generates the standard Ecuadorian invoice XML payload (incorporating access key computation, customer CI/RUC, company information, item lists, and taxes).
- [x] T013 Implement the check-digit calculation (Modulo 11 algorithm) for generating the 49-digit SRI Access Key.
- [x] T014 Create `app/Modules/Billing/Services/XmlSignerService.php` that implements the XAdES-BES signature structure, embedding signed properties and certificate key references into the XML document.
- [x] T015 Create `app/Modules/Billing/Services/SriSoapClientService.php` with support for SRI WSDL endpoints (test vs. production), utilizing SOAP `validarComprobante` and `autorizacionComprobante` calls.
- [x] T016 Create RIDE PDF blade layout `app/Modules/Billing/Views/invoices/ride.blade.php` containing standard elements (company name, RUC, access key barcode/number, details, total taxes, and internal notes).
- [x] T017 Create `app/Modules/Billing/Services/RideGeneratorService.php` utilizing a PDF library (e.g. Dompdf) to render the HTML view into a PDF document saved in storage.
- [x] T018 Write `app/Modules/Billing/Services/ElectronicInvoicingService.php` coordinating the steps (generate, sign, send reception, check auth state, render RIDE, save paths to database).

---

## Phase 4: Integration with POS & SaaS Subscription Billing

- [x] T019 Create queued job `ProcessElectronicInvoiceJob` to run invoicing steps asynchronously.
- [x] T020 Integrate invoicing option into Sales Detail view: add button "Emitir Factura Electrónica" shown after checkout is fully paid.
- [x] T021 Implement `app/Modules/Billing/Controllers/InvoiceController.php` with routes to trigger invoicing, list issued invoices, download XML files, and view PDFs.
- [x] T022 Integrate electronic invoicing limit middleware checks into routes protecting billing sections (`auth.module:settings`).
- [x] T023 Integrate invoicing option into SuperAdmin Subscription payments list. When validating and approving payments, enable invoicing of subscribers.
- [x] T024 Add automated email notification that attaches both the authorized XML and the generated RIDE PDF.

---

## Phase 5: Test Suite & Verification

- [x] T025 Create unit tests checking the access key check-digit generator (Modulo 11).
- [x] T026 Create unit tests verifying XML generator produces valid nodes and schema outputs.
- [x] T027 Create feature tests for certificate settings: upload valid `.p12` -> extracts metadata; upload invalid `.p12` or password -> returns validation exception.
- [x] T028 Create mock feature tests for POS invoicing: mock SOAP Reception response as `RECIBIDA` / `DEVUELTA` and mock SOAP Authorization response as `AUTORIZADO` / `NO AUTORIZADO`, asserting database status transitions.
- [x] T029 Create feature tests verifying plan subscription restrictions block electronic billing functions for Basic accounts.
- [x] T030 Create feature tests checking SuperAdmin platform invoicing triggers upon payment approval.
- [x] T031 Run targeted tests `php artisan test --filter=Billing` and assert all pass.
- [x] T032 Run full suite `php artisan test` and verify zero regressions.

---

## Phase 6: Sidebar Navigation & CSRF Fixes

- [x] T033 Add "Comprobantes SRI" link to Vendedor sidebar menu in `layouts/app.blade.php`.
- [x] T034 Update standard and premium plans to have `has_electronic_billing = true` by default in migration `2026_06_22_000004_add_electronic_billing_fields_to_plans_and_companies.php`.
- [x] T035 Update Company model accessors in `Company.php` to robustly cast and fall back to plan configuration values when database values are null.
- [x] T036 Fix CSRF Page Expired error in SuperAdmin payment approval modal in `payment-modal.blade.php` by using `x-show` instead of template tags to keep form fields parsed at page load.
- [x] T037 Run database updates to refresh plans and verify via full test suite execution.
