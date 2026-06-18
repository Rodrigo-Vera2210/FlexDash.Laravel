# Tasks: PDF Reports & Cash Box Excel Export (Spec 008)

**Input**: Design documents from `/specs/008-pdf-reports-and-cash-excel/`

**Prerequisites**: `plan.md`, `spec.md`

---

## Phase 1: Setup & Dependencies

- [x] T001 Propose adding `"barryvdh/laravel-dompdf": "^3.0"` and `"phpoffice/phpspreadsheet": "^2.1"` to `composer.json` requirements.
- [x] T002 Propose running `composer update` to install the new packages.
- [x] T003 [P] Verify package registration and service providers auto-discovery.

---

## Phase 2: Sales Invoice PDF Generation (TDD)

- [x] T004 Write failing feature tests in `app/Modules/Sale/Tests/Feature/SalePdfTest.php` to assert:
  - `GET /sales/{sale}/pdf` returns 200 with PDF content type.
  - Guest requests are redirected to login.
  - Non-existent sale returns 404.
- [x] T005 Implement `SaleController::downloadPdf()` in `app/Modules/Sale/Controllers/SaleController.php` to load sale details and trigger the PDF generator.
- [x] T006 Create the PDF layout view in `resources/views/sales/pdf.blade.php` with premium wave design and Primary Blue palette (`#0054a6`).
- [x] T007 Register the `sales.pdf` route in `routes/web.php` inside the JWT auth group.
- [x] T008 Verify that the Sale PDF tests pass successfully.

---

## Phase 3: Purchase Order PDF Generation (TDD)

- [x] T009 Write failing feature tests in `app/Modules/Purchase/Tests/Feature/PurchasePdfTest.php` to assert:
  - `GET /purchases/{purchase}/pdf` returns 200 with PDF content.
  - Guest requests are redirected.
- [x] T010 Implement `PurchaseController::downloadPdf()` in `app/Modules/Purchase/Controllers/PurchaseController.php`.
- [x] T011 Create the PDF layout view in `resources/views/purchases/pdf.blade.php` with premium wave design and Teal/Cyan palette (`#00a2e8`).
- [x] T012 Register the `purchases.pdf` route in `routes/web.php` inside the JWT auth group.
- [x] T013 Verify that the Purchase PDF tests pass successfully.

---

## Phase 4: Cash Box Excel Export (TDD)

- [x] T014 Write failing feature tests in `app/Modules/CashBox/Tests/Feature/CashBoxExcelTest.php` to assert:
  - `GET /cashbox/{cashBox}/export` downloads a valid `.xlsx` spreadsheet for a cash box session.
  - Unauthenticated requests are rejected.
- [x] T015 Implement `CashBoxController::exportExcel()` in `app/Modules/CashBox/Controllers/CashBoxController.php` using PhpSpreadsheet (styled blue headers, formatted currency, auto-fitted columns).
- [x] T016 Register the `cashbox.export` route in `routes/web.php` inside the JWT auth group.
- [x] T017 Verify that the Cash Box Excel export tests pass successfully.

---

## Phase 5: Frontend UI Integration

- [x] T018 [P] Add a PDF download icon button next to each sale in `resources/views/sales/index.blade.php` table.
- [x] T019 Add a "Descargar PDF" button in the action bar of the sale detail view `resources/views/sales/show.blade.php`.
- [x] T020 [P] Add a PDF download icon button next to each purchase in `resources/views/purchases/index.blade.php` table.
- [x] T021 Add a "Descargar PDF" button in the action bar of the purchase detail view `resources/views/purchases/show.blade.php`.
- [x] T022 [P] Add a small PDF download icon next to sale numbers in the Dashboard recent sales table `resources/views/dashboard/index.blade.php`.
- [x] T023 Add an "Exportar Excel" button/icon in the header actions bar for active cash box sessions in `resources/views/cashbox/index.blade.php`.

---

## Phase 6: Polish & Verification

- [x] T024 Test unicode character rendering in PDFs (verify Spanish characters like accents and the Soles symbol `S/` render correctly without question marks).
- [x] T025 Run the entire test suite `php artisan test` and verify all tests pass.
- [x] T026 Build frontend assets `npm run build` to ensure compilation is clean.
- [x] T027 Manual verification: Download and inspect generated PDFs and Excel sheet to ensure styling matches spec.
