# Tasks: Autocomplete Inputs for Catalogs & Documents

**Input**: Design documents from `/specs/022-autocomplete-inputs/`

**Prerequisites**: plan.md (required), spec.md (required)

---

## Phase 1: Foundational (Search Controller & API Setup)

**Purpose**: Centralize search endpoints with proper security scoping.

- [ ] T001 Create `SearchController` in `app/Http/Controllers/Api/SearchController.php` implementing:
  - `partners` search (filters by name/RUC/CI, branch/company scope).
  - `products` search (filters by name/code, branch/company scope).
  - `documents` search (filters by sequential number, branch/company scope).
- [ ] T002 Register routes in `routes/api.php` under `auth.jwt` and `initialize.branch` middleware.
- [ ] T003 Write initial feature test `AutocompleteSearchTest.php` in `tests/Feature/AutocompleteSearchTest.php` to verify search behavior.

---

## Phase 2: User Story 1 - Autocomplete for Partners (P1)

**Goal**: Implement autocomplete client/supplier search widget in transaction forms.

- [ ] T004 Create reusable AlpineJS / Vanilla JS Autocomplete widget markup & script styles in layout.
- [ ] T005 Integrate partner autocomplete on Sales creation form (`resources/views/sales/create.blade.php`).
- [ ] T006 Integrate partner autocomplete on Purchases creation form (`resources/views/purchases/create.blade.php`).
- [ ] T007 Integrate partner autocomplete on CashBox Batch Payment form (`resources/views/cashbox/batch-payment.blade.php`).

---

## Phase 3: User Story 2 - Autocomplete for Products (P2)

**Goal**: Implement dynamic autocomplete product search in invoice detail lines and stock transfers.

- [ ] T008 Integrate product autocomplete inside Sales items grid (`resources/views/sales/create.blade.php`).
- [ ] T009 Integrate product autocomplete inside Purchases items grid (`resources/views/purchases/create.blade.php`).
- [ ] T010 Integrate product autocomplete inside Stock Transfers grid (`resources/views/inventory/transfers/create.blade.php`).

---

## Phase 4: User Story 3 - Autocomplete for Documents (P3)

**Goal**: Implement sequential document/invoice autocomplete search for payments.

- [ ] T011 Integrate document search autocomplete inside CashBox batch payment selector.

---

## Phase 5: Verification & Polish

**Purpose**: Test correctness, security, and responsive UI overlays.

- [ ] T012 Run linting, styling, and all PHPUnit verification tests.
- [ ] T013 Verify autocomplete debounce behavior and edge cases (no-results handling).
