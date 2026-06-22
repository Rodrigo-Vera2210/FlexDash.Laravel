# Tasks: 014 Multi-Certificate Invoicing

## Phase 1: Database Schema & Migration
- [x] T001 Create migration to add `max_certificates` to `plans` and `companies` tables
- [x] T002 Create migration for `company_certificates` table
- [x] T003 Create data migration to migrate existing certificate details from `billing_configs` to `company_certificates`
- [x] T004 Create migration to remove deprecated certificate fields from `billing_configs` and add `certificate_id` to `electronic_invoices`
- [x] T005 Run database migrations and verify schema integrity

## Phase 2: Eloquent Models & Accessors
- [x] T006 Create `CompanyCertificate` model with password encryption/decryption mutators
- [x] T007 Add relations and `max_certificates` limit check logic to `Company` and `Plan` models
- [x] T008 Update `ElectronicInvoice` and `BillingConfig` relationships

## Phase 3: Core Billing Service Refactoring
- [x] T009 Refactor `ElectronicInvoicingService` to load and use default or specified certificate
- [x] T010 Refactor `XmlSignerService` to accept certificate models and sign using dynamically retrieved paths/passwords

## Phase 4: UI & Controller Updates
- [x] T011 Update `BillingSettingsController` with list, store, delete, and setDefault actions
- [x] T012 Enforce subscription plan limit check validation on upload action
- [x] T013 Update settings view (`config.blade.php`) to show certificate list, active statuses, and upload form
- [x] T014 Update billing trigger views to allow dynamic signature selection when emitting invoices

## Phase 5: Verification & Tests
- [x] T015 Write feature tests for plan limit enforcement (Basic vs Standard vs Premium)
- [x] T016 Write unit tests for RUC validation and default flag toggle behavior
- [x] T017 Write integration tests verifying invoicing with custom selected signatures
- [x] T018 Run the complete regression test suite and verify manually
