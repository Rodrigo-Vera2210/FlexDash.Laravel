# Implementation Plan: Centralized Catalog Management (Spec 005)

**Branch**: `005-catalog-management` | **Date**: 2026-06-08 | **Spec**: `/specs/005-catalog-management/spec.md`

## Summary
This plan details the implementation of a centralized settings module to manage auxiliary system tables (Taxes, Categories, Payment Methods) using tabs, inline toggles for status (`is_active`), and AJAX-based modal quick-add controls for product/sale forms.

---

## Technical Context
- **Language/Version**: PHP 8.2+ with Laravel 12
- **Primary Dependencies**: Laravel Framework, Tailwind CSS, AlpineJS, Font Awesome
- **Integrity Constraint**: Use `is_active` deactivation instead of hard deletes when records are referenced in database transactions to protect historic data.

---

## Proposed Changes

### 1. Route Registrations & Sidebar Layout

We will register settings routes and link the catalog dashboard to the sidebar.

#### [NEW] [settings.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/routes/settings.php)
- Define a group protected by the `auth.jwt` middleware.
- Map `GET /settings/catalogs` to `CatalogController@index`.
- Map resource endpoints for `taxes`, `categories`, and `payment-methods` updates.
- Map `POST /settings/catalogs/toggle-status` for dynamic ajax toggles of status.
- Map `DELETE /settings/catalogs/{type}/{id}` to safely delete unused records.

#### [MODIFY] [web.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/routes/web.php)
- Include the new `routes/settings.php` file inside the routes definition.

#### [MODIFY] [app.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/layouts/app.blade.php)
- Append a **Configuración** (`settings.catalogs.index`) link at the bottom of the sidebar navigation with a `fa-solid fa-gears` icon.

---

### 2. CatalogController Implementation

Create a central controller to coordinate all database operations for catalog types.

#### [NEW] [CatalogController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Settings/Controllers/CatalogController.php)
- `index()`: Fetch all taxes, product categories, and payment methods to compact into the dashboard view.
- `storeTax(Request)`, `storeCategory(Request)`, `storePaymentMethod(Request)`: Handle forms and AJAX validation/creation.
- `updateTax(Request)`, `updateCategory(Request)`, `updatePaymentMethod(Request)`: Process inline modifications.
- `toggleStatus(Request)`: Generic method accepting `model` (e.g. `Tax`, `Category`) and `id` to swap `is_active` state.
- `destroy($type, $id)`: Query if the record has relations, deleting it or returning an error if referenced.

---

### 3. Views & Modals (Blade + AlpineJS)

Develop the tabbed dashboard and contextual forms.

#### [NEW] [index.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/settings/catalogs/index.blade.php)
- Consolidate all catalogs into a tabbed layout (Tabs: Impuestos, Categorías, Métodos de Pago).
- Render searchable tables for each catalog category using standard FlexDash aesthetics.
- Integrate AlpineJS modals for adding or editing items.

#### [NEW] [modal-tax.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/settings/catalogs/partials/modal-tax.blade.php)
- reusable form inputs for Tax parameters (name, code, rate).

#### [NEW] [modal-category.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/settings/catalogs/partials/modal-category.blade.php)
- reusable form inputs for Category parameters (name, description).

#### [MODIFY] [products/create.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/products/create.blade.php)
- Place `+` buttons next to the Product Category and Tax fields.
- Bind clicks to open AJAX forms and load the new entries into the selects dynamically.

---

## Verification Plan

### Automated Tests
#### [NEW] [CatalogManagementTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/tests/Feature/CatalogManagementTest.php)
- Verify that authenticated users can see the main tab page.
- Test AJAX requests to add Taxes, Categories, and Payment Methods.
- Verify that toggle status updates `is_active` correctly.
- Test that deleting a referenced tax fails, but deleting an unused category succeeds.
- Test Suite: `php artisan test`

### Manual Verification
- Access `/settings/catalogs` in a web browser.
- Create new catalog items and check search filtering works instantly.
- Toggle status indicators and assert that they change the active state dynamically.
- Try creating a category inside the Product Creation view using the contextual `+` modal.
