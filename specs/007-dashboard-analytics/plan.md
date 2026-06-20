# Implementation Plan: Dashboard Analytics (Spec 007)

**Branch**: `007-dashboard-analytics` | **Date**: 2026-06-16 | **Spec**: `/specs/007-dashboard-analytics/spec.md`

## Summary
This plan details the transformation of the Dashboard module from a partially hardcoded overview to a fully data-driven analytics hub. The existing `DashboardController` and `dashboard/index.blade.php` will be refactored to source all KPIs, charts, rankings, and tables from real database queries. A new `DashboardService` will centralize all aggregation logic. A global month/year filter will be applied to every widget.

---

## Technical Context
- **Language/Version**: PHP 8.2+ with Laravel 12
- **Primary Dependencies**: Laravel Framework, Tailwind CSS, AlpineJS, Font Awesome, Chart.js (new, for dynamic charts)
- **Storage**: SQLite database.
- **Testing**: PHPUnit / Pest (feature and unit tests).
- **Existing State**: The current `DashboardController` has some real queries (sales today, receivables, payables, estimated profit, last 30 days chart) but the Blade view hardcodes several sections (KPI card values, categories distribution, frequent clients list, recent sales table).

---

## Constitution Check
- **TDD Requirement**: Failing tests for service aggregation methods must be written first (unit tests for `DashboardService`, feature tests for controller responses).
- **Module Architecture**: The `DashboardService` will reside inside `app/Modules/Dashboard/Services/` per the constitution mandate (no new services in the deprecated global `app/Services/`).
- **Layered Architecture**: Controller delegates all queries to `DashboardService`; no direct DB queries in the controller. The view receives pre-computed data only.
- **JWT Auth**: Dashboard route already protected under `auth.jwt` middleware — no changes needed.
- **Clean Code**: Each aggregation method in `DashboardService` will be a focused, single-responsibility method (≤ 30 lines). Complex queries will use Eloquent query builder for readability.

---

## Project Structure
We will extend the existing Dashboard module:
```text
app/Modules/Dashboard/
├── Controllers/
│   └── DashboardController.php  [MODIFY — refactor to use DashboardService]
├── Services/
│   └── DashboardService.php     [NEW — centralized analytics queries]
└── Tests/
    ├── Unit/
    │   └── DashboardServiceTest.php   [NEW]
    └── Feature/
        └── DashboardControllerTest.php [NEW]
```

---

## Proposed Changes

### 1. New Service Layer

#### [NEW] [DashboardService.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Dashboard/Services/DashboardService.php)
Centralized service encapsulating all dashboard aggregation queries. Receives `$month` and `$year` parameters for period filtering.

**Methods:**

- `getRevenueByDay(int $month, int $year): Collection` — Returns `SUM(sales.total)` grouped by `date(issue_date)` for the specified month/year, excluding `ANULADO`.
- `getRevenueByWeek(int $month, int $year): Collection` — Returns `SUM(sales.total)` grouped by ISO week number, filtered to weeks that overlap with the specified month.
- `getRevenueByMonth(int $year): Collection` — Returns `SUM(sales.total)` grouped by month for the entire year, excluding `ANULADO`.
- `getKpiSummary(int $month, int $year): array` — Returns an associative array with:
  - `total_revenue` — `SUM(sales.total)` for the period.
  - `transaction_count` — `COUNT(sales.id)` for the period (non-cancelled).
  - `average_ticket` — `total_revenue / transaction_count`.
  - `total_purchases` — `SUM(purchases.total)` for the period (non-cancelled).
  - `accounts_receivable` — `SUM(sales.pending_balance)` where `status = 'APROBADO'`.
  - `accounts_payable` — `SUM(purchases.pending_balance)` where `status = 'APROBADO'`.
  - `estimated_profit` — `SUM((sd.unit_price - sd.cost_price) * sd.quantity)` for the period.
- `getTopSoldProductsByQuantity(int $month, int $year, int $limit = 10): Collection` — Joins `sale_details` → `sales` → `products`, groups by `product_id`, orders by `SUM(quantity) DESC`.
- `getTopSoldProductsByRevenue(int $month, int $year, int $limit = 10): Collection` — Same join, orders by `SUM(subtotal) DESC`.
- `getTopPurchasedProducts(int $month, int $year, int $limit = 10): Collection` — Joins `purchase_details` → `purchases` → `products`, groups by `product_id`, orders by `SUM(quantity) DESC`.
- `getTopSellingCategories(int $month, int $year): Collection` — Joins `sale_details` → `products` → `categories`, groups by `category_id`, returns category name, total revenue, and percentage.
- `getTopFrequentCustomers(int $month, int $year, int $limit = 10): Collection` — Queries `sales` grouped by `partner_id` (type `cliente` or `ambos`), ordered by `COUNT(id) DESC`. Returns partner name, transaction count, and total amount.
- `getAmountsByClient(int $month, int $year, int $limit = 10): Collection` — Aggregates `SUM(total)`, `SUM(paid_amount)`, `SUM(pending_balance)` per client partner for the period.
- `getAmountsBySupplier(int $month, int $year, int $limit = 10): Collection` — Same aggregation for supplier partners via `purchases`.
- `getRecentSales(int $limit = 10): Collection` — Latest sales with `partner`, eager-loaded `details` count, ordered by `created_at DESC`.

### 2. Controller Refactoring

#### [MODIFY] [DashboardController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Dashboard/Controllers/DashboardController.php)

**Current state**: 96-line `index()` method with inline DB queries and some hardcoded data.

**Changes**:
- Inject `DashboardService` via constructor.
- Accept `Request` parameter to read `month` and `year` query parameters (default to `now()->month` and `now()->year`).
- Replace all inline queries with calls to `DashboardService` methods.
- Pass all computed data plus the filter state (`$selectedMonth`, `$selectedYear`) to the view.
- Remove the `$ventasPorDia` 30-day chart logic (replaced by period-aware `getRevenueByDay()`).
- The controller should remain thin (< 30 lines of logic per the constitution).

**New `index()` signature:**
```php
public function index(Request $request): View
{
    $month = (int) $request->query('month', now()->month);
    $year  = (int) $request->query('year', now()->year);

    $kpis                 = $this->service->getKpiSummary($month, $year);
    $revenueByDay         = $this->service->getRevenueByDay($month, $year);
    $topSoldByQty         = $this->service->getTopSoldProductsByQuantity($month, $year);
    $topSoldByRevenue     = $this->service->getTopSoldProductsByRevenue($month, $year);
    $topPurchasedProducts = $this->service->getTopPurchasedProducts($month, $year);
    $topCategories        = $this->service->getTopSellingCategories($month, $year);
    $topCustomers         = $this->service->getTopFrequentCustomers($month, $year);
    $amountsByClient      = $this->service->getAmountsByClient($month, $year);
    $amountsBySupplier    = $this->service->getAmountsBySupplier($month, $year);
    $recentSales          = $this->service->getRecentSales();

    return view('dashboard.index', compact(
        'month', 'year', 'kpis',
        'revenueByDay', 'topSoldByQty', 'topSoldByRevenue',
        'topPurchasedProducts', 'topCategories', 'topCustomers',
        'amountsByClient', 'amountsBySupplier', 'recentSales',
    ));
}
```

### 3. Frontend View Refactoring

#### [MODIFY] [index.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/dashboard/index.blade.php)

**Current state**: 288 lines with hardcoded mock data in KPI cards, categories, recent sales, and frequent clients.

**Changes:**

**3a. Global Filter Bar (New)**
- Add a sticky filter bar at the top with:
  - Month dropdown (`<select>` with months 1–12, localized names in Spanish).
  - Year dropdown (`<select>` dynamically showing years from the earliest sale to current year).
  - "Filtrar" button that submits as GET parameters `?month=X&year=Y`.
- Styled consistently with existing `.card-panel` design system.

**3b. KPI Cards (Replace Hardcoded)**
- Replace hardcoded values (`$1,248.50`, `48`, `$26.01`, `$48,291`) with real data from `$kpis`:
  - **Ventas del Período** → `$kpis['total_revenue']`
  - **Transacciones** → `$kpis['transaction_count']`
  - **Ticket Promedio** → `$kpis['average_ticket']`
  - **Ganancia Estimada** → `$kpis['estimated_profit']`
- Remove percentage comparisons for now (they require previous period data which can be added in a future iteration).

**3c. Revenue Chart (Replace Static SVG)**
- Replace the static hardcoded SVG spline chart with a Chart.js `<canvas>` chart.
- Default to daily view for the selected month.
- Add period toggle buttons (Día / Semana / Mes) that use AlpineJS to switch datasets.
- Pass `$revenueByDay` as JSON data attribute for Chart.js consumption.
- The chart will use the project color palette (`--primary: #0A7EA5`).

**3d. Top Selling Categories (Replace Hardcoded)**
- Replace the hardcoded `['Electrónica', 'Alimentos', ...]` array with `$topCategories` from the service.
- Maintain the existing progress bar design but with dynamic percentages.

**3e. Recent Sales Table (Replace Hardcoded)**
- Replace the hardcoded `['#TRX-9081', ...]` array with `$recentSales` from the service.
- Add issue date column.
- Link sale numbers to their detail page (`route('sales.show', $sale->id)`).

**3f. Frequent Customers (Replace Hardcoded)**
- Replace the hardcoded `['Carlos Pérez', ...]` array with `$topCustomers` from the service.
- Show real transaction counts and totals.

**3g. New Sections (Added)**
- **Productos Más Vendidos** card: Table with tabs for "Por Cantidad" and "Por Ingreso" using `$topSoldByQty` / `$topSoldByRevenue`. Toggle via AlpineJS.
- **Productos Más Comprados** card: Simple ranked list from `$topPurchasedProducts`.
- **Montos por Cliente** card: Table showing client name, total, paid, pending for the period.
- **Montos por Proveedor** card: Table showing supplier name, total, paid, pending for the period.

**Layout reorganization:**
```
Row 1: Filter Bar (full width)
Row 2: 4 KPI Cards (grid 4 cols)
Row 3: Revenue Chart (2/3) + Top Categories (1/3)
Row 4: Top Sold Products (1/2) + Top Purchased Products (1/2)
Row 5: Recent Sales Table (2/3) + Frequent Customers (1/3)
Row 6: Amounts by Client (1/2) + Amounts by Supplier (1/2)
```

### 4. Chart.js Integration

#### [MODIFY] [package.json](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/package.json)
- Add `chart.js` as a dependency: `npm install chart.js`.

#### [MODIFY] [vite.config.js](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/vite.config.js) (only if needed)
- No changes expected — Chart.js will be loaded via CDN `<script>` tag in the dashboard view for simplicity and to avoid bundling overhead, keeping it consistent with the existing AlpineJS approach.

### 5. Tests

#### [NEW] [DashboardServiceTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Dashboard/Tests/Unit/DashboardServiceTest.php)
Unit tests for each `DashboardService` method:
- Test revenue aggregation by day with known seed data.
- Test revenue aggregation by week.
- Test KPI summary calculations.
- Test top sold products ranking (quantity and revenue).
- Test top purchased products ranking.
- Test top categories ranking.
- Test frequent customers ranking.
- Test amounts by client and supplier.
- Test period filter correctly excludes out-of-range sales.
- Test cancelled sales are excluded from all metrics.

#### [NEW] [DashboardControllerTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Dashboard/Tests/Feature/DashboardControllerTest.php)
Feature tests:
- Test GET `/dashboard` returns 200 with expected view variables.
- Test GET `/dashboard?month=3&year=2026` returns filtered data.
- Test default month/year when no query parameters.
- Test authentication requirement (unauthenticated request redirected to login).

---

## Verification Plan

### Automated Tests
- `php artisan test --filter=DashboardServiceTest` — All unit tests for aggregation logic.
- `php artisan test --filter=DashboardControllerTest` — All feature tests for controller responses.

### Manual Verification
- Access `/dashboard` and verify all KPI cards show real values from the database (not hardcoded).
- Change the month/year filter and confirm all widgets update correctly.
- Toggle the revenue chart between Day/Week/Month views.
- Toggle the "Productos Más Vendidos" between "Por Cantidad" and "Por Ingreso".
- Verify the "Últimas Ventas" table shows real sales with clickable links.
- Verify "Clientes Más Frecuentes" shows real customer data.
- Verify "Montos por Cliente" and "Montos por Proveedor" show correct financial summaries.
- Select a month with no data and verify graceful empty state messages.
- Run `npm run build` to ensure frontend assets compile correctly.
