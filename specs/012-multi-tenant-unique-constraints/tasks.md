# Tasks: Multi-Tenant Unique Constraints

## Phase 1: Database Migration

- [x] T001 Create migration `2026_06_22_000001_replace_global_unique_with_company_scoped_unique.php` that drops the global unique index on `products.code` and adds composite unique `(company_id, code)`.
- [x] T002 In the same migration, drop the global unique index on `taxes.code` and add composite unique `(company_id, code)`.
- [x] T003 In the same migration, drop the global unique index on `categories.name` and add composite unique `(company_id, name)`.
- [x] T004 In the same migration, drop the global unique index on `payment_methods.name` and add composite unique `(company_id, name)`.
- [x] T005 In the same migration, drop the global unique index on `partners.document_number` and add composite unique `(company_id, document_number)`.
- [x] T006 In the same migration, drop the global unique index on `sales.number` and add composite unique `(company_id, number)`.
- [x] T007 In the same migration, drop the global unique index on `purchases.number` and add composite unique `(company_id, number)`.
- [x] T008 Run `php artisan migrate` and verify migration applies cleanly on SQLite.

---

## Phase 2: Reusable Validation Rule

- [x] T009 Create `app/Rules/UniqueForCompany.php` — a custom validation rule that checks uniqueness scoped by `company_id` of the authenticated user. Supports `ignoreId` for update operations.
- [x] T010 Verify the rule returns the Spanish error message: `"El valor de :attribute ya está en uso."`.

---

## Phase 3: Controller Validation Updates

- [x] T011 Modify `app/Modules/Product/Controllers/ProductController.php` — replace `'unique:products'` in `store()` with `new UniqueForCompany('products', 'code')`. Add code validation with `UniqueForCompany` ignore-self in `update()`.
- [x] T012 Modify `app/Modules/Settings/Controllers/CatalogController.php` — replace `'unique:taxes,code'` in `storeTax()` with `new UniqueForCompany('taxes', 'code')`. Update `updateTax()` with ignore-self.
- [x] T013 Modify `app/Modules/Settings/Controllers/CatalogController.php` — replace `'unique:categories,name'` in `storeCategory()` with `new UniqueForCompany('categories', 'name')`. Update `updateCategory()` with ignore-self.
- [x] T014 Modify `app/Modules/Settings/Controllers/CatalogController.php` — replace `'unique:payment_methods,name'` in `storePaymentMethod()` with `new UniqueForCompany('payment_methods', 'name')`. Update `updatePaymentMethod()` with ignore-self.
- [x] T015 Modify `app/Modules/Partner/Controllers/PartnerController.php` — replace `'unique:partners'` in `store()` with `new UniqueForCompany('partners', 'document_number')`. Add `document_number` validation with `UniqueForCompany` ignore-self in `update()`.
- [x] T016 Review `app/Modules/Sale/Controllers/SaleController.php` and `app/Modules/Purchase/Controllers/PurchaseController.php` — if sale/purchase number validation uses `'unique:sales,number'` or `'unique:purchases,number'`, replace with `UniqueForCompany`. If number is auto-generated (no user input validation), confirm the `BelongsToCompany` global scope already handles isolation and the DB composite unique index is sufficient.

---

## Phase 4: Test Suite

- [x] T017 Create `tests/Feature/MultiTenantUniqueConstraintsTest.php` with setup that creates two companies (A and B) and their respective owner users.
- [x] T018 Write test: Product code uniqueness — Company A creates `PROD-001`, Company B creates `PROD-001` → both succeed. Company A creates another `PROD-001` → validation fails.
- [x] T019 Write test: Tax code uniqueness — Company A creates `IGV`, Company B creates `IGV` → both succeed. Company A duplicate → fails.
- [x] T020 Write test: Category name uniqueness — Company A creates `Electrónica`, Company B creates `Electrónica` → both succeed. Company A duplicate → fails.
- [x] T021 Write test: Payment method name uniqueness — Company A creates `Efectivo`, Company B creates `Efectivo` → both succeed. Company A duplicate → fails.
- [x] T022 Write test: Partner document_number uniqueness — Company A creates `20100047218`, Company B creates `20100047218` → both succeed. Company A duplicate → fails.
- [x] T023 Write test: Update with ignore-self — Company A updates a product keeping its same code → succeeds. Company A updates a product changing its code to an existing one → fails.

---

## Phase 5: Verification & Polish

- [x] T024 Run `php artisan test --filter=MultiTenantUniqueConstraintsTest` — all assertions must pass.
- [x] T025 Run the full test suite `php artisan test` and confirm zero regressions.
- [x] T026 Manual smoke test: Log in as Company A, create a product with code `TEST-001`. Log in as Company B, create a product with code `TEST-001`. Verify both exist independently.
- [x] T027 Manual smoke test: As Company A, try to create a second product with code `TEST-001` → verify validation error message appears correctly.
