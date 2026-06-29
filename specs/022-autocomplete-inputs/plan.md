# Implementation Plan: Autocomplete Inputs for Catalogs & Documents

**Branch**: `022-autocomplete-inputs` | **Date**: 2026-06-29 | **Spec**: [spec.md](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/specs/022-autocomplete-inputs/spec.md)

## Summary

The goal of this feature is to replace resource-heavy static `<select>` elements with responsive, asynchronous autocomplete inputs for **Clients/Suppliers (Partners)**, **Products**, and **Documents** across the transaction pages. 

We will create a centralized `SearchController` with endpoints secured by `auth.jwt` and `initialize.branch` middleware. The frontend will employ a custom lightweight AlpineJS / Vanilla JS autocomplete widget with a premium overlay style and debouncing support.

## Technical Context

- **Language/Version**: PHP 8.2+
- **Primary Dependencies**: Laravel 12.x, AlpineJS (already loaded in layout) or Vanilla JS
- **Storage**: SQLite
- **Testing**: PHPUnit / Feature Tests
- **Performance Goals**: Autocomplete API response time < 100ms; Debouncing delay: 300ms.

## Constitution Check

- **Test-Driven Development**: Yes, feature tests will cover the new search API endpoints.
- **Layered Architecture**: Yes, all search logic is delegated through queries, and JWT auth is processed at the route middleware level.
- **Technology Stack Constraints**: Uses Laravel & Tailwind CSS.

## Project Structure

### Documentation

```text
specs/022-autocomplete-inputs/
├── spec.md              # Feature specification
├── plan.md              # Implementation plan (this file)
└── tasks.md             # Task list checklist
```

### Proposed Changes

#### [NEW] [SearchController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Http/Controllers/Api/SearchController.php)
Implement endpoints for finding:
- Partners (clients/suppliers) by name or identification.
- Products by name or code.
- Documents (Invoices/Sales) by sequential number.

#### [MODIFY] [api.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/routes/api.php)
Register the autocomplete routes under the authenticated `auth.jwt` and `initialize.branch` middleware group.

#### [MODIFY] Transaction Views
Integrate autocomplete logic on:
- [sales/create.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/sales/create.blade.php) (Ventas)
- [purchases/create.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/purchases/create.blade.php) (Compras)
- [transfers/create.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/inventory/transfers/create.blade.php) (Traslados Inventario)
- [cashbox/batch-payment.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/cashbox/batch-payment.blade.php) (Caja Chica - Pago Masivo)

## Verification Plan

### Automated Tests
- Feature test to verify Search API results matching search terms.
- Feature test to verify branch scoping (cannot search products/documents belonging to other companies or branches when scoped).

```bash
php artisan test --filter AutocompleteSearchTest
```
