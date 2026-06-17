# Tasks: Dashboard Analytics (Spec 007)

**Input**: Design documents from `/specs/007-dashboard-analytics/`

**Prerequisites**: `plan.md`, `spec.md`

---

## Phase 1: Setup & Infrastructure

- [x] T001 Create module directories for Dashboard Service and Tests in `app/Modules/Dashboard/Services/` and `app/Modules/Dashboard/Tests/Unit/`, `app/Modules/Dashboard/Tests/Feature/`.
- [x] T002 Add Chart.js CDN script reference to the dashboard view (or install via npm if preferred).

---

## Phase 2: Service Layer — Core Aggregation Queries (TDD)

- [x] T003 Write failing unit tests in `app/Modules/Dashboard/Tests/Unit/DashboardServiceTest.php` for `getKpiSummary()` — testing total revenue, transaction count, average ticket, estimated profit, accounts receivable/payable.
- [x] T004 Implement `DashboardService::getKpiSummary()` in `app/Modules/Dashboard/Services/DashboardService.php`.
- [x] T005 Write failing unit tests for `getRevenueByDay()`, `getRevenueByWeek()`, and `getRevenueByMonth()`.
- [x] T006 Implement revenue aggregation methods in `DashboardService`.
- [x] T007 Verify KPI and revenue tests pass.

---

## Phase 3: Service Layer — Rankings & Lists (TDD)

- [x] T008 Write failing unit tests for `getTopSoldProductsByQuantity()` and `getTopSoldProductsByRevenue()`.
- [x] T009 Implement top sold products methods in `DashboardService`.
- [x] T010 Write failing unit tests for `getTopPurchasedProducts()`.
- [x] T011 Implement top purchased products method in `DashboardService`.
- [x] T012 Write failing unit tests for `getTopSellingCategories()`.
- [x] T013 Implement top selling categories method in `DashboardService`.
- [x] T014 Write failing unit tests for `getTopFrequentCustomers()`.
- [x] T015 Implement top frequent customers method in `DashboardService`.
- [x] T016 Write failing unit tests for `getAmountsByClient()` and `getAmountsBySupplier()`.
- [x] T017 Implement amounts by client and supplier methods in `DashboardService`.
- [x] T018 Implement `getRecentSales()` method in `DashboardService`.
- [x] T019 Verify all ranking and list tests pass.

---

## Phase 4: Controller Refactoring (TDD)

- [x] T020 Write failing feature tests in `app/Modules/Dashboard/Tests/Feature/DashboardControllerTest.php` for:
  - GET `/dashboard` returns 200 with expected view data.
  - GET `/dashboard?month=3&year=2026` applies filter correctly.
  - Unauthenticated users are redirected to login.
- [x] T021 Refactor `DashboardController::index()` to inject `DashboardService`, accept `month`/`year` query parameters, and delegate all queries to the service.
- [x] T022 Verify feature tests pass.

---

## Phase 5: Frontend — Filter Bar & KPI Cards

- [x] T023 Add the global month/year filter bar to `resources/views/dashboard/index.blade.php` with `<select>` elements for month (1–12, Spanish names) and year, submitting as GET parameters.
- [x] T024 Replace hardcoded KPI card values with real `$kpis` data from the controller:
  - Ventas del Período → `$kpis['total_revenue']`
  - Transacciones → `$kpis['transaction_count']`
  - Ticket Promedio → `$kpis['average_ticket']`
  - Ganancia Estimada → `$kpis['estimated_profit']`

---

## Phase 6: Frontend — Revenue Chart

- [x] T025 Replace the static SVG spline chart with a Chart.js `<canvas>` area/bar chart.
- [x] T026 Implement AlpineJS-driven period toggle buttons (Día / Semana / Mes) that switch the chart dataset dynamically.
- [x] T027 Pass `$revenueByDay` (and weekly/monthly data) as JSON data attributes for Chart.js.

---

## Phase 7: Frontend — Rankings & Tables

- [x] T028 Replace hardcoded categories with dynamic `$topCategories` data in the category progress bars section.
- [x] T029 Replace hardcoded recent sales table with real `$recentSales` data, adding issue date column and clickable links.
- [x] T030 Replace hardcoded frequent customers section with real `$topCustomers` data.
- [x] T031 Add new "Productos Más Vendidos" card with tabs for "Por Cantidad" / "Por Ingreso" using AlpineJS toggle.
- [x] T032 Add new "Productos Más Comprados" card with `$topPurchasedProducts` data.
- [x] T033 Add new "Montos por Cliente" card showing client financial summary table.
- [x] T034 Add new "Montos por Proveedor" card showing supplier financial summary table.

---

## Phase 8: Empty States & Edge Cases

- [x] T035 Implement graceful empty state messages ("Sin datos para este período") for all sections when no data exists for the selected month/year.
- [x] T036 Verify cancelled sales (`ANULADO`) are excluded from all revenue, ranking, and count calculations.

---

## Phase 9: Polish & Verification

- [x] T037 Run the full test suite `php artisan test` and verify all existing + new tests pass (0 failures).
- [x] T038 Execute frontend bundle build `npm run build` and verify assets compile without errors.
- [x] T039 Manual QA: Navigate the dashboard, apply filters, toggle chart views, and verify all widgets display correct data.
- [x] T040 Verify responsive design: test dashboard layout at mobile (375px), tablet (768px), and desktop (1440px) breakpoints.
