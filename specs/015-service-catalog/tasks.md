# Tasks: 015 Catálogo de Servicios

## Phase 1: Database Schema & Migrations

- [x] T001 Create migration for `service_categories` table (`id`, `company_id` FK, `name`, `description`, `is_active`, timestamps)
- [x] T002 Create migration for `services` table (`id`, `company_id` FK, `service_category_id` FK nullable, `tax_id` FK nullable, `code`, `name`, `description`, `price`, `cost`, `is_active`, timestamps, soft deletes)
- [x] T003 Create migration to add `service_id` (nullable FK) to `sale_details` and make `product_id` nullable
- [x] T004 Run database migrations and verify schema integrity

---

## Phase 2: Eloquent Models & Relations

- [x] T005 [P] Create `ServiceCategory` model (`app/Modules/Service/Models/ServiceCategory.php`) with `BelongsToCompany` trait, fillable, casts, and `services()` HasMany relation
- [x] T006 [P] Create `Service` model (`app/Modules/Service/Models/Service.php`) with `SoftDeletes`, `BelongsToCompany` traits, fillable, casts, `scopeActive`, and relations: `category()`, `tax()`, `saleDetails()`
- [x] T007 Update `SaleDetail` model: add `service_id` to fillable, add `service()` BelongsTo relation, add `isService()` and `isProduct()` helpers, add `getItemNameAttribute()` accessor
- [x] T008 Update `Tax` model: add `services()` HasMany relation

---

## Phase 3: User Story 1 — Gestión del Catálogo de Servicios (P1)

**Goal**: Full CRUD for services with tax assignment and deactivation support.

**Independent Test**: Navigate to `/services`, create a service with code/name/price/tax, verify it appears in the list.

- [x] T009 Create `ServiceController` (`app/Modules/Service/Controllers/ServiceController.php`) with `index`, `create`, `store`, `show`, `edit`, `update`, `destroy` actions
- [x] T010 Register service resource routes in `routes/web.php` inside authenticated group (no `auth.module` restriction)
- [x] T011 Add "Servicios" link to sidebar navigation in `app.blade.php` with `fa-solid fa-screwdriver-wrench` icon
- [x] T012 [P] Create `resources/views/services/index.blade.php` — service table with search, category filter, pagination, status badges
- [x] T013 [P] Create `resources/views/services/create.blade.php` — form with code, name, description, category, price, cost, tax, is_active
- [x] T014 [P] Create `resources/views/services/edit.blade.php` — pre-populated edit form
- [x] T015 [P] Create `resources/views/services/show.blade.php` — detail view with edit/delete actions

**Checkpoint**: Service CRUD is fully functional. Users can create, list, view, edit, and deactivate services.

---

## Phase 4: User Story 2 — Agregar Servicios a Ventas (P1)

**Goal**: Services can be added as line items in sales alongside products.

**Independent Test**: Create a sale with a product + service, approve it, verify totals correct and no inventory for services.

- [x] T016 Update `SaleController::create()` to pass both `products` and `services` to the view
- [x] T017 Update `SaleController::store()` validation to accept `items.*.product_id` (nullable) and `items.*.service_id` (nullable) with `required_without` cross-validation
- [x] T018 Update `SaleService::create()` to handle service items: set `service_id`, resolve `cost_price` from service cost
- [x] T019 Update `SaleService::approve()` to skip inventory deduction for service items (`isService()` check)
- [x] T020 Update `resources/views/sales/create.blade.php`: unified item search including products + services, visual badges ("Producto"/"Servicio"), hide stock indicator for services
- [x] T021 Update `resources/views/sales/show.blade.php`: display correct item name (product or service), add "Servicio" badge for service lines
- [x] T022 Update `resources/views/sales/pdf.blade.php`: render service line items with their codes and descriptions

**Checkpoint**: Sales can include both products and services. Tax and total calculations are correct. Inventory only affected by products.

---

## Phase 5: User Story 3 — Facturación Electrónica de Servicios (P2)

**Goal**: Services in sales are correctly represented in electronic invoice XML and RIDE PDF.

**Independent Test**: Emit an electronic invoice for a sale with services, verify XML `<detalles>` and SRI validation.

- [x] T023 Update `ElectronicInvoicingService` to generate XML `<detalles>` for service items: use service `code` as `codigoPrincipal`, service `name` as `descripcion`, apply correct IVA codes
- [x] T024 Verify RIDE PDF generation includes service line items correctly (existing PDF generation should work if `show.blade.php` and detail loading is correct)

**Checkpoint**: Electronic invoices including services pass SRI validation. RIDE PDF shows all line items.

---

## Phase 6: User Story 4 — Catálogo de Configuración (P3)

**Goal**: Service categories manageable from the settings catalog page.

**Independent Test**: Create a service category in settings, assign it to a service.

- [x] T025 Update `CatalogController::index()` to pass `serviceCategories` to the view
- [x] T026 Add `storeServiceCategory()` and `updateServiceCategory()` methods to `CatalogController`
- [x] T027 Update `resources/views/settings/catalogs/index.blade.php` to add "Categorías de Servicios" tab with CRUD table and modal

**Checkpoint**: Service categories are manageable from the centralized catalog settings.

---

## Phase 7: Verification & Tests

- [x] T028 Write feature tests for service CRUD (`tests/Feature/ServiceCatalogTest.php`): create, read, update, soft delete, unique code validation, tax assignment, deactivation
- [x] T029 Write integration tests for sales with services (`tests/Feature/ServiceSaleIntegrationTest.php`): mixed sale, service-only sale, no inventory for services, tax calculations
- [x] T030 Write integration tests for electronic invoicing with services: XML generation, IVA codes, SRI-compliant output
- [x] T031 Run the complete test suite (`php artisan test`) and verify zero failures
- [x] T032 Run asset build (`npm run build`) and verify client-side compilation client-side compilation
