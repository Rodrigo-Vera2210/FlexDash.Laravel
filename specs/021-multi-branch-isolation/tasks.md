# Tasks: Multi-Branch Operational Isolation

**Input**: Design documents from `/specs/021-multi-branch-isolation/`

**Prerequisites**: plan.md, spec.md

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel
- **[Story]**: US1 (Session Branch Selector), US2 (Operational Isolation), US3 (Dashboard Scoping)

---

## Phase 1: Setup & Foundational

**Purpose**: Set up scoping traits, session controller, and middleware.

- [ ] T001 [P] Create `BelongsToBranch` trait in `app/Traits/BelongsToBranch.php`
- [ ] T002 Create `BranchSessionController` in `app/Http/Controllers/BranchSessionController.php`
- [ ] T003 Create `InitializeActiveBranch` middleware in `app/Http/Middleware/InitializeActiveBranch.php`
- [ ] T004 Register middleware and routes in `bootstrap/app.php` and `routes/web.php`

---

## Phase 2: User Story 2 - Operational Data Isolation by Branch (Priority: P2)

**Goal**: Bind all sales, purchases, cashbox, payments, and movements to the branch scope.

- [ ] T005 [P] [US2] Create test suite `tests/Feature/MultiBranchIsolationTest.php` asserting independent data contexts.
- [ ] T006 [US2] Apply `BelongsToBranch` trait to `Sale` model
- [ ] T007 [US2] Apply `BelongsToBranch` trait to `Purchase` model
- [ ] T008 [US2] Apply `BelongsToBranch` trait to `CashBox` model
- [ ] T009 [US2] Apply `BelongsToBranch` trait to `Payment` model
- [ ] T010 [US2] Apply `BelongsToBranch` trait to `InventoryMovement` model
- [ ] T011 [US2] Apply `BelongsToBranch` trait to `StockTransfer` model

---

## Phase 3: User Story 1 - Session Branch Selector (Priority: P1)

**Goal**: Show branch selector dropdown in the header topbar for Premium plan companies.

- [ ] T012 [P] [US1] Add test cases to `MultiBranchIsolationTest.php` asserting session changes and selector availability.
- [ ] T013 [US1] Include branch selector dropdown in topbar header `resources/views/layouts/app.blade.php`.
- [ ] T014 [US1] Toggle selector visibility based on `auth()->user()->company->max_branches > 1`.

---

## Phase 4: User Story 3 - Branch-Scoped Dashboard Metrics (Priority: P3)

**Goal**: Apply branch filters to the Dashboard metrics calculations.

- [ ] T015 [US3] Modify `DashboardController` or repository queries to scope metrics by the active branch session.
- [ ] T016 [US3] Verify dashboard widgets display metrics for the selected branch.

---

## Phase 5: Polish & Verification

- [ ] T017 Run complete test suite and verify multi-branch operational isolation.
- [ ] T018 Check interface translations and ensure all actions are fully in Spanish.
