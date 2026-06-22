# Tasks: Ecuadorian Electronic Invoicing (SRI)

## Phase 1: Database Schema & Migration

- [ ] T001 Create migration `2026_06_22_000002_create_billing_configs_table.php` to store `.p12` certificates (path, encrypted passwords, expiration, establishment, emission point, and sequential counters) for tenants and the SuperAdmin.
- [ ] T002 Create migration `2026_06_22_000003_create_electronic_invoices_table.php` to track morphable invoicing link (`invoicable_type`, `invoicable_id`), sequence string, access keys, statuses, PDF/XML file paths, and SRI errors.
- [ ] T003 Create migration `2026_06_22_000004_add_electronic_billing_fields_to_plans_and_companies.php` adding boolean `has_electronic_billing` column and integer invoice limits to plans and company overrides.
- [ ] T004 Run `php artisan migrate` to create billing tables.
- [ ] T005 Update the Partner database schema or validations to explicitly support CI (Cédula), RUC, and Pasaporte identification types, verifying constraint limits.

---

## Phase 2: Configuration Panels (UI & Logic)

- [ ] T006 Create `app/Modules/Billing/Models/BillingConfig.php` and `app/Modules/Billing/Models/ElectronicInvoice.php`.
- [ ] T007 Implement certificate encryption logic in `BillingConfig` using `Crypt` for the `.p12` password.
- [ ] T008 Implement PKCS#12 certificate parsing logic in a helper class or service using `openssl_pkcs12_read()` to validate password correctness, validity dates, and expiration date checks.
- [ ] T009 Create `app/Modules/Billing/Controllers/BillingSettingsController.php` implementing settings GET index and POST save methods (validating point, establishment, and uploading `.p12` files to a secure directory).
- [ ] T010 Create settings blade view `app/Modules/Billing/Views/settings/config.blade.php` with file inputs, password fields, sequence inputs, and status badges.
- [ ] T011 Create `app/Modules/Billing/Controllers/SuperAdminBillingController.php` and its blade view `app/Modules/Billing/Views/superadmin/billing.blade.php` to enable platform-level electronic invoicing configuration.

---

## Phase 3: Core Billing Services (XML, Signing, SOAP, RIDE)

- [ ] T012 Create `app/Modules/Billing/Services/XmlGeneratorService.php` which generates the standard Ecuadorian invoice XML payload (incorporating access key computation, customer CI/RUC, company information, item lists, and taxes).
- [ ] T013 Implement the check-digit calculation (Modulo 11 algorithm) for generating the 49-digit SRI Access Key.
- [ ] T014 Create `app/Modules/Billing/Services/XmlSignerService.php` that implements the XAdES-BES signature structure, embedding signed properties and certificate key references into the XML document.
- [ ] T015 Create `app/Modules/Billing/Services/SriSoapClientService.php` with support for SRI WSDL endpoints (test vs. production), utilizing SOAP `validarComprobante` and `autorizacionComprobante` calls.
- [ ] T016 Create RIDE PDF blade layout `app/Modules/Billing/Views/invoices/ride.blade.php` containing standard elements (company name, RUC, access key barcode/number, details, total taxes, and internal notes).
- [ ] T017 Create `app/Modules/Billing/Services/RideGeneratorService.php` utilizing a PDF library (e.g. Dompdf) to render the HTML view into a PDF document saved in storage.
- [ ] T018 Write `app/Modules/Billing/Services/ElectronicInvoicingService.php` coordinating the steps (generate, sign, send reception, check auth state, render RIDE, save paths to database).

---

## Phase 4: Integration with POS & SaaS Subscription Billing

- [ ] T019 Create queued job `ProcessElectronicInvoiceJob` to run invoicing steps asynchronously.
- [ ] T020 Integrate invoicing option into Sales Detail view: add button "Emitir Factura Electrónica" shown after checkout is fully paid.
- [ ] T021 Implement `app/Modules/Billing/Controllers/InvoiceController.php` with routes to trigger invoicing, list issued invoices, download XML files, and view PDFs.
- [ ] T022 Integrate electronic invoicing limit middleware checks into routes protecting billing sections (`auth.module:settings`).
- [ ] T023 Integrate invoicing option into SuperAdmin Subscription payments list. When validating and approving payments, enable invoicing of subscribers.
- [ ] T024 Add automated email notification that attaches both the authorized XML and the generated RIDE PDF.

---

## Phase 5: Test Suite & Verification

- [ ] T025 Create unit tests checking the access key check-digit generator (Modulo 11).
- [ ] T026 Create unit tests verifying XML generator produces valid nodes and schema outputs.
- [ ] T027 Create feature tests for certificate settings: upload valid `.p12` -> extracts metadata; upload invalid `.p12` or password -> returns validation exception.
- [ ] T028 Create mock feature tests for POS invoicing: mock SOAP Reception response as `RECIBIDA` / `DEVUELTA` and mock SOAP Authorization response as `AUTORIZADO` / `NO AUTORIZADO`, asserting database status transitions.
- [ ] T029 Create feature tests verifying plan subscription restrictions block electronic billing functions for Basic accounts.
- [ ] T030 Create feature tests checking SuperAdmin platform invoicing triggers upon payment approval.
- [ ] T031 Run targeted tests `php artisan test --filter=Billing` and assert all pass.
- [ ] T032 Run full suite `php artisan test` and verify zero regressions.
