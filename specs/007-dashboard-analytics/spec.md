# Feature Specification: Dashboard Analytics (Panel de Analíticas)

**Feature Branch**: `007-dashboard-analytics`

**Created**: 2026-06-16

**Status**: Draft

**Input**: User description: "Conectar todos los servicios al dashboard. Mostrar ingresos por días, semanas y meses. Productos más vendidos (stock e ingreso). Productos más comprados. Categorías más vendidas. Clientes más frecuentes. Montos por cliente y proveedor. Número de transacciones. Últimas ventas. Filtro por mes y año."

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Revenue Analytics by Period (Priority: P1)

As a business owner or manager, I want to view my income (ingresos) broken down by day, week, and month so that I can identify revenue trends and peak periods.

**Why this priority**: Revenue visibility is the primary function of any POS dashboard; it enables immediate financial decisions.

**Independent Test**: Can be tested by navigating to the Dashboard, verifying the revenue chart renders with correct totals from `sales` table, and toggling between daily/weekly/monthly views.

**Acceptance Scenarios**:
1. **Given** I am on the Dashboard, **When** I view the "Ingresos" section, **Then** I see a chart (bar/area) showing revenue grouped by day for the current month, with the option to switch between daily, weekly, and monthly views.
2. **Given** the daily view is active, **When** I switch to "Semanal", **Then** the chart re-renders showing revenue aggregated per ISO week, with each bar/point representing a week's total.
3. **Given** the monthly view is active, **When** I view the current year, **Then** I see 12 bars/points (one per month) with the revenue totals sourced from `SUM(sales.total)` excluding cancelled sales (`status ≠ 'ANULADO'`).

---

### User Story 2 - Top Sold Products (Priority: P1)

As a store manager, I want to see the products with the highest sales both in quantity (units sold) and in revenue (income generated) so that I can make informed stocking and pricing decisions.

**Why this priority**: Identifying best-sellers directly impacts purchasing, inventory replenishment, and pricing strategy.

**Independent Test**: Can be tested by creating several sales with different products, accessing the dashboard, and verifying the top products ranking by both `SUM(sale_details.quantity)` and `SUM(sale_details.subtotal)`.

**Acceptance Scenarios**:
1. **Given** I am on the Dashboard, **When** I view the "Productos Más Vendidos" section, **Then** I see a table/list showing the top 10 products ranked by total quantity sold (`SUM(sale_details.quantity)`), with columns for product name, units sold, and revenue generated.
2. **Given** the product ranking section, **When** I toggle to "Por Ingreso", **Then** the ranking reorders products by revenue (`SUM(sale_details.subtotal)`).

---

### User Story 3 - Top Purchased Products (Priority: P2)

As a purchasing manager, I want to see the products I purchase most frequently so that I can negotiate better supplier deals and optimize procurement.

**Why this priority**: Understanding purchasing patterns enables cost optimization and supplier negotiation leverage.

**Independent Test**: Can be tested by creating purchases with diverse products and verifying the dashboard ranks products by `SUM(purchase_details.quantity)`.

**Acceptance Scenarios**:
1. **Given** I am on the Dashboard, **When** I view the "Productos Más Comprados" section, **Then** I see a ranked list of the top 10 products by purchase quantity (`SUM(purchase_details.quantity)`), with columns for product name, units purchased, and total cost.

---

### User Story 4 - Top Selling Categories (Priority: P2)

As a business owner, I want to see which product categories generate the most revenue so that I can focus marketing and inventory efforts on high-performing categories.

**Why this priority**: Category-level insights drive strategic decisions on product mix and expansion.

**Independent Test**: Can be tested by verifying the dashboard aggregates `SUM(sale_details.subtotal)` grouped by `products.category_id` via the `categories` table.

**Acceptance Scenarios**:
1. **Given** I am on the Dashboard, **When** I view the "Categorías Más Vendidas" section, **Then** I see a bar chart or progress bars showing categories ranked by total revenue, with percentage of total sales displayed.

---

### User Story 5 - Most Frequent Customers (Priority: P2)

As a sales manager, I want to see my most frequent customers ranked by number of transactions so that I can prioritize customer relationship management.

**Why this priority**: Customer loyalty insights drive retention strategy and personalized service.

**Independent Test**: Can be tested by querying `COUNT(sales.id)` grouped by `partner_id` for partners of type `cliente` or `ambos` and verifying the ranking matches the dashboard display.

**Acceptance Scenarios**:
1. **Given** I am on the Dashboard, **When** I view the "Clientes Más Frecuentes" section, **Then** I see a list of the top 10 customers ranked by number of transactions, showing customer name, number of purchases, and total amount spent.

---

### User Story 6 - Amounts by Client and Supplier (Priority: P2)

As an accountant, I want to see the total amounts (paid and pending) per client and per supplier so that I can track accounts receivable and payable at a glance.

**Why this priority**: Financial health monitoring requires visibility into outstanding balances by partner.

**Independent Test**: Can be tested by verifying `SUM(sales.total)` and `SUM(sales.pending_balance)` per client, and `SUM(purchases.total)` and `SUM(purchases.pending_balance)` per supplier are correctly displayed.

**Acceptance Scenarios**:
1. **Given** I am on the Dashboard, **When** I view the "Montos por Cliente" section, **Then** I see a list showing each client's total sales amount, paid amount, and pending balance for the selected period.
2. **Given** I am on the Dashboard, **When** I view the "Montos por Proveedor" section, **Then** I see a list showing each supplier's total purchase amount, paid amount, and pending balance for the selected period.

---

### User Story 7 - Transaction Count & Recent Sales (Priority: P1)

As a cashier or manager, I want to see the total number of transactions and a list of the most recent sales so that I can monitor daily activity in real-time.

**Why this priority**: Real-time transaction monitoring is essential for daily operational oversight.

**Independent Test**: Can be tested by counting non-cancelled sales and verifying the number displayed. Recent sales table should show the last 10 sales ordered by `created_at DESC`.

**Acceptance Scenarios**:
1. **Given** I am on the Dashboard, **When** I view the KPI cards, **Then** I see a "Transacciones" card showing the total count of non-cancelled sales for the selected period.
2. **Given** I am on the Dashboard, **When** I view the "Últimas Ventas" table, **Then** I see the 10 most recent sales with columns: Sale Number, Client, Items count, Total, Status, and Date.

---

### User Story 8 - Month/Year Filter (Priority: P1)

As any dashboard user, I want to filter all dashboard data by month and year so that I can analyze historical performance for any given period.

**Why this priority**: Historical comparison is critical for trend analysis and business planning.

**Independent Test**: Can be tested by selecting a past month/year combination and verifying all KPIs, charts, rankings, and tables update to reflect data from that specific period only.

**Acceptance Scenarios**:
1. **Given** I am on the Dashboard, **When** I select "Marzo 2026" from the month/year filter, **Then** all dashboard widgets (revenue chart, product rankings, customer rankings, transaction count, recent sales) reload showing only data from March 2026.
2. **Given** a month/year filter is applied, **When** I switch the revenue chart to "Semanal", **Then** only the weeks within the selected month are shown.
3. **Given** the Dashboard loads initially, **Then** the filter defaults to the current month and year.

---

## Edge Cases

- **No Data for Selected Period**: If the selected month/year has no sales, purchases, or transactions, all widgets must gracefully display zero values or a "Sin datos para este período" message instead of errors or blank spaces.
- **Cancelled Sales Exclusion**: All revenue, product ranking, and transaction count calculations MUST exclude sales/purchases with `status = 'ANULADO'`. Cancelled transactions should never inflate revenue metrics.
- **Products Without Sales**: Products that have never been sold must not appear in the "Productos Más Vendidos" ranking. Similarly for purchased products.
- **Partners With Multiple Types**: Partners with `type = 'ambos'` should appear in both the client and supplier sections where applicable, not be excluded from either.
- **Large Datasets**: Revenue charts and ranking queries must be performant (< 500ms) even with thousands of sales/purchase records, using proper indexes and efficient aggregation queries.
- **Currency Consistency**: All amounts displayed must use the system's default currency format (PEN/S/), consistent with the existing sale/purchase models.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Dashboard MUST display a revenue chart with daily, weekly, and monthly aggregation views, sourced from `sales.total` excluding cancelled sales.
- **FR-002**: Dashboard MUST show top 10 sold products ranked by quantity (`SUM(sale_details.quantity)`) with a toggle to rank by revenue (`SUM(sale_details.subtotal)`).
- **FR-003**: Dashboard MUST show top 10 purchased products ranked by purchase quantity (`SUM(purchase_details.quantity)`).
- **FR-004**: Dashboard MUST show top selling categories ranked by revenue (`SUM(sale_details.subtotal)` grouped by `products.category_id`).
- **FR-005**: Dashboard MUST show top 10 most frequent customers ranked by transaction count (`COUNT(sales.id)` per `partner_id` for clients).
- **FR-006**: Dashboard MUST show total amounts (total, paid, pending) per client and per supplier for the selected period.
- **FR-007**: Dashboard MUST display the total number of non-cancelled transactions (sales count) for the selected period.
- **FR-008**: Dashboard MUST display the 10 most recent sales with Sale Number, Client Name, Items Count, Total Amount, Status, and Issue Date.
- **FR-009**: Dashboard MUST provide a month/year filter that applies globally to all widgets. Defaults to the current month/year on initial load.
- **FR-010**: All aggregation queries MUST support filtering by `issue_date` month and year derived from the global filter.

### Key Entities (Existing — No New Tables)

- **Sale**: Revenue source. Fields: `total`, `status`, `issue_date`, `partner_id`, `pending_balance`, `paid_amount`.
- **SaleDetail**: Line items. Fields: `product_id`, `quantity`, `unit_price`, `cost_price`, `subtotal`.
- **Purchase**: Cost source. Fields: `total`, `status`, `issue_date`, `partner_id`, `pending_balance`, `paid_amount`.
- **PurchaseDetail**: Line items. Fields: `product_id`, `quantity`, `unit_cost`, `subtotal`.
- **Product**: Catalog. Fields: `name`, `code`, `category_id`, `stock`, `price`, `cost`.
- **Category**: Product grouping. Fields: `name`.
- **Partner**: Client/Supplier. Fields: `type`, `business_name`, `trade_name`.
- **Payment**: Transaction records. Polymorphic to Sale/Purchase.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: All dashboard widgets display real data from the database — zero hardcoded/mock values in the final implementation.
- **SC-002**: Changing the month/year filter updates all 8+ dashboard sections within 2 seconds.
- **SC-003**: Revenue chart correctly reflects `SUM(sales.total)` for the selected granularity (day/week/month) with < 1% error margin.
- **SC-004**: Product, category, and customer rankings are verifiable against raw SQL queries on the database.
- **SC-005**: The dashboard page loads in under 3 seconds with up to 10,000 sales records.

---

## Assumptions

- This feature enhances the existing `DashboardController` and `dashboard/index.blade.php` rather than creating entirely new endpoints.
- The existing dashboard view currently uses hardcoded mock data in some sections (KPI cards, recent sales table, categories, frequent clients) — this spec replaces all hardcoded data with real queries.
- The revenue chart will continue to use SVG-based rendering (inline SVG or a lightweight JS chart library like Chart.js) for consistency with the existing design system.
- The filter mechanism uses standard GET parameters (`?month=6&year=2026`) for bookmarkability and simplicity, not requiring AJAX for the initial implementation.
