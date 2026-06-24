# Implementation Plan: Catálogo de Servicios

**Branch**: `015-service-catalog` | **Date**: 2026-06-23 | **Spec**: `/specs/015-service-catalog/spec.md`

## Summary

This plan details the implementation of a Service Catalog module for FlexDash POS, enabling companies to create, manage, and sell services alongside physical products. Services can be taxed (IVA) or tax-exempt, added as line items in sales, and included in electronic invoices. The feature is available to all subscription plans.

---

## Technical Context

- **Language/Version**: PHP 8.2+ with Laravel 12
- **Primary Dependencies**: Laravel Framework, Tailwind CSS, AlpineJS, Font Awesome
- **Storage**: SQLite (primary)
- **Testing**: PHPUnit + Pest
- **Constraints**: Multi-tenant via `BelongsToCompany` trait; JWT authentication; SRI electronic invoicing compliance

---

## Constitution Check

| Principle | Status | Notes |
|-----------|--------|-------|
| TDD (Red-Green-Refactor) | ✅ | Tests defined in Phase 5 |
| Layered Architecture | ✅ | Controller → Service → Model → Migration |
| Module-Based Backend | ✅ | New `app/Modules/Service/` module |
| Clean Code & SOLID | ✅ | Services ≤30 lines per method; SRP enforced |
| Technology Stack | ✅ | Laravel, Tailwind CSS, SQLite |
| JWT Authentication | ✅ | All routes behind `auth.jwt` middleware |
| Localization (Ecuador/SRI) | ✅ | IVA codes in invoice XML; labels in Spanish |
| Migrations in `database/migrations/` | ✅ | Global migration directory |

---

## Proposed Changes

### 1. Database Migrations

#### [NEW] `database/migrations/XXXX_XX_XX_create_service_categories_table.php`
- Create `service_categories` table with fields: `id`, `company_id` (FK), `name` (string 100), `description` (nullable), `is_active` (boolean, default true), timestamps.

#### [NEW] `database/migrations/XXXX_XX_XX_create_services_table.php`
- Create `services` table with fields: `id`, `company_id` (FK), `service_category_id` (nullable FK), `tax_id` (nullable FK), `code` (string 50), `name` (string 200), `description` (text, nullable), `price` (decimal 14,4), `cost` (decimal 14,4, default 0), `is_active` (boolean, default true), timestamps, soft deletes.
- Indexes on: `company_id`, `is_active`, `service_category_id`.

#### [NEW] `database/migrations/XXXX_XX_XX_add_service_id_to_sale_details.php`
- Add `service_id` (unsigned big integer, nullable, FK to `services`) to `sale_details`.
- Modify `product_id` to be nullable (currently NOT NULL).
- Application-layer constraint: exactly one of `product_id` or `service_id` must be non-null.

---

### 2. Module: `app/Modules/Service/`

#### [NEW] [Service.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Service/Models/Service.php)
- Eloquent model with `SoftDeletes` and `BelongsToCompany` traits.
- `$fillable`: `company_id`, `service_category_id`, `tax_id`, `code`, `name`, `description`, `price`, `cost`, `is_active`.
- `$casts`: `price` → `decimal:4`, `cost` → `decimal:4`, `is_active` → `boolean`.
- Relations: `category()` → BelongsTo ServiceCategory, `tax()` → BelongsTo Tax, `saleDetails()` → HasMany SaleDetail.
- Scopes: `scopeActive($query)` → filters `is_active = true`.

#### [NEW] [ServiceCategory.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Service/Models/ServiceCategory.php)
- Eloquent model with `BelongsToCompany` trait.
- `$fillable`: `company_id`, `name`, `description`, `is_active`.
- Relation: `services()` → HasMany Service.

#### [NEW] [ServiceController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Service/Controllers/ServiceController.php)
- Full CRUD resource controller:
  - `index()`: List services with search, category filter, pagination. Load `serviceCategories` for filter dropdown.
  - `create()`: Provide `serviceCategories` and `taxes` for form.
  - `store(Request)`: Validate with `UniqueForCompany` on `code`. Create service.
  - `show(Service)`: Load service with relations.
  - `edit(Service)`: Provide categories, taxes, and service data.
  - `update(Request, Service)`: Update service fields.
  - `destroy(Service)`: Soft delete. Restrict if referenced in sale_details (suggest deactivation).

---

### 3. Model Updates (Existing Modules)

#### [MODIFY] [SaleDetail.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Sale/Models/SaleDetail.php)
- Add `service_id` to `$fillable`.
- Add relation: `service()` → BelongsTo Service.
- Add helper: `isService()` → returns `$this->service_id !== null`.
- Add helper: `isProduct()` → returns `$this->product_id !== null`.
- Add helper: `getItemNameAttribute()` → returns product name or service name.

#### [MODIFY] [Tax.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Models/Tax.php)
- Add relation: `services()` → HasMany Service.

---

### 4. Service Layer Updates

#### [MODIFY] [SaleService.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Services/SaleService.php)
- Update `create()` method to accept items with either `product_id` or `service_id`.
- When processing a service item: set `cost_price` from the service's `cost` field (or 0 if null).
- Skip inventory deduction for service items in the `approve()` method.

#### [MODIFY] [ElectronicInvoicingService.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Billing/Services/ElectronicInvoicingService.php)
- Update the XML `<detalles>` generation to handle service details:
  - Use the service's `code` as `codigoPrincipal`.
  - Use the service's `name` as `descripcion`.
  - Set `cantidad` and `precioUnitario` from the sale detail.
  - Generate `<impuestos>` block based on the service's tax or 0% if exempt.

---

### 5. Routes & Navigation

#### [MODIFY] [web.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/routes/web.php)
- Add service resource routes inside the authenticated group **without** `auth.module` middleware (available to all plans):
  ```php
  Route::resource('services', ServiceController::class);
  ```

#### [MODIFY] [app.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/layouts/app.blade.php)
- Add "Servicios" link in the sidebar navigation with `fa-solid fa-screwdriver-wrench` icon, positioned after "Productos" / "Inventario".

---

### 6. Views (Blade + Tailwind + AlpineJS)

#### [NEW] [resources/views/services/index.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/services/index.blade.php)
- Table listing services: Code, Name, Category, Price, Tax Rate, Status (Active/Inactive), Actions.
- Search bar and category filter.
- Pagination.
- "Nuevo Servicio" button.

#### [NEW] [resources/views/services/create.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/services/create.blade.php)
- Form with fields: Code, Name, Description, Category (dropdown), Price, Cost (optional), Tax (dropdown with "Exento" option), Is Active toggle.
- Quick-add `+` button next to category dropdown for inline creation.

#### [NEW] [resources/views/services/edit.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/services/edit.blade.php)
- Same form as create, pre-populated with existing data.

#### [NEW] [resources/views/services/show.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/services/show.blade.php)
- Detail view of the service with edit/delete actions.
- Show related sale history (recent sales containing this service).

#### [MODIFY] [resources/views/sales/create.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/sales/create.blade.php)
- Update the items search/selector to include both products and services.
- When adding a service item, send `service_id` instead of `product_id`.
- Add visual differentiation: badge "Producto" (blue) vs "Servicio" (green) next to each item.
- When a service is selected, hide/disable the "stock" indicator since services have no inventory.

#### [MODIFY] [resources/views/sales/show.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/sales/show.blade.php)
- Update the details table to display the correct item name whether it's a product or service.
- Add "Servicio" badge for service line items.

#### [MODIFY] [resources/views/sales/pdf.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/sales/pdf.blade.php)
- Update PDF template to correctly render service line items with their codes and descriptions.

---

### 7. Catalog Integration (P3)

#### [MODIFY] [CatalogController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Settings/Controllers/CatalogController.php)
- Add a new tab "Categorías de Servicios" in the `index()` method.
- Add `storeServiceCategory(Request)` and `updateServiceCategory(Request)` methods.
- Add service categories to the existing toggle/delete logic.

#### [MODIFY] [resources/views/settings/catalogs/index.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/settings/catalogs/index.blade.php)
- Add a new tab panel for "Categorías de Servicios" with the standard CRUD table/modal pattern.

---

## 8. Validation Rules

### Store Service
| Field | Rules |
|-------|-------|
| `service_category_id` | `nullable\|exists:service_categories,id` |
| `tax_id` | `nullable\|exists:taxes,id` |
| `code` | `required\|string\|max:50\|UniqueForCompany('services', 'code')` |
| `name` | `required\|string\|max:200` |
| `description` | `nullable\|string` |
| `price` | `required\|numeric\|min:0` |
| `cost` | `nullable\|numeric\|min:0` |
| `is_active` | `boolean` |

### Store Sale (updated items validation)
| Field | Rules |
|-------|-------|
| `items.*.product_id` | `nullable\|exists:products,id\|required_without:items.*.service_id` |
| `items.*.service_id` | `nullable\|exists:services,id\|required_without:items.*.product_id` |
| `items.*.quantity` | `required\|numeric\|min:0.01` |
| `items.*.unit_price` | `required\|numeric\|min:0` |

---

## 9. Verification Plan

### Automated Tests

#### [NEW] [ServiceCatalogTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/tests/Feature/ServiceCatalogTest.php)
- Test CRUD operations for services (create, read, update, soft delete).
- Test unique code validation per company.
- Test tax assignment (with IVA, without IVA / exento).
- Test deactivation prevents appearance in sale forms.
- Test deletion prevention when referenced in sale_details.

#### [NEW] [ServiceSaleIntegrationTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/tests/Feature/ServiceSaleIntegrationTest.php)
- Test creating a sale with only services.
- Test creating a sale with mixed products and services.
- Test that approving a sale with services does NOT create inventory movements for services.
- Test that tax calculations include service taxes correctly.
- Test that service-only sales have zero inventory impact.

#### Test Suite Command
```bash
php artisan test --filter=ServiceCatalog
php artisan test --filter=ServiceSaleIntegration
php artisan test
```

### Manual Verification
- Navigate to `/services` and create a service with IVA 15%.
- Create a sale including both a product and the new service.
- Approve the sale and verify inventory deducted only for the product.
- Emit an electronic invoice and verify the XML includes the service line with correct IVA.
- Download the PDF and verify the service appears in the line items.
- Log in with a Basic plan account and confirm access to the services section.
