# Feature Specification: Multilocal (Múltiples Sucursales), Inventario por Local y Configuración de Duración de Suscripción con Descuentos

**Feature Branch**: `017-multi-branch-and-plans`

**Created**: 2026-06-24

**Status**: Draft

**Input**: User description: "Vamos a aumentar la lógica de los locales para tener varios locales por empresa, y manejar un inventario por cada local (como funciona ahorita), y un inventario general de toda la empresa donde en cada columna sale el total del stock en cada local. También toca modificar la creación de los planes para modificar el número de locales por plan, de igual forma no puedo editar el número de facturas emitidas, así que añádelo. Y de paso vamos a cambiar el modo que se presentan los planes a lo que se crea la cuenta. Teniendo como referencia la imagen adjuntada, ajustándolo a la paleta y estilo de nuestro sistema. También toca colocar un selector de los periodos de meses que quiere contratar como base: 1, 3, 6, 12, 24, 36. De igual forma vamos a dar un descuento por cada periodo 3 meses = 5%, 6 meses = 10%, 12 meses = 15%, 24 meses = 20% y 36 meses = 25%."

---

## 1. Feature Description & Context

This feature introduces a comprehensive multi-branch architecture, expands subscription plan customizability for the platform administrator, and creates a highly premium, dynamic pricing card layout for user signup.

### Problem 1: Single Location/Branch Constraint (Architecture Gap)
Currently, inventory stock is tracked globally for a company directly on the `products` table (`stock` column). Companies are limited to a single establishment configured under their general billing options. In modern retail and business administration, companies operate multiple physical points of sale (locales/sucursales/branches). Each local has its own:
- Dedicated stock of products.
- Dedicated user accounts (vendedores) assigned to that point of sale.
- Distinct transaction ledgers (sales, purchases, cash registers).
- Separate SRI Electronic Invoicing attributes (different `establecimiento` codes, e.g. "001", "002").

### Problem 2: Plan Limit Inflexibility (Superadmin Control Gap)
The superadmin dashboard currently allows creating and editing subscription plans. However:
- The maximum number of locales/branches allowed per plan cannot be configured or limited.
- The `monthly_invoice_limit` (monthly electronic invoice count limit) exists in the database schema but is completely missing from the plan editing form inputs, leaving superadmins unable to set or edit it.

### Problem 3: Basic Signup Plan Selection (UX/Design Gap)
The current signup flow at step 4 (Planes y Pago) displays basic radio buttons with hardcoded features and no billing cycle options. 
To improve conversion rates and look premium:
- The design must match the provided reference image layout (dynamic subscription cards).
- Users must select their desired billing cycle period in months: **1, 3, 6, 12, 24, 36**.
- Discounts must be applied dynamically to the selected period:
  - **3 months**: 5% discount
  - **6 months**: 10% discount
  - **12 months**: 15% discount
  - **24 months**: 20% discount
  - **36 months**: 25% discount
- The UI must dynamically calculate and display:
  - The crossed-out base monthly price.
  - The actual monthly price after discount.
  - The discount percentage badge (e.g. `-15%`).
  - The total calculated amount to pay (e.g. `Obtén 12 meses por S/ 601.80`).

---

## 2. User Stories & Acceptance Criteria

### User Story 1 - Gestión de Múltiples Locales por Empresa (Priority: P1)
> **As a** Company Admin,
> **I want to** create and manage multiple branches/locales for my company,
> **So that** I can configure their names, addresses, and unique SRI establishment codes.

**Acceptance Scenarios**:
1. **Given** I am on the new branch management page, **When** I click "Nuevo Local", **Then** I am presented with a form to enter: Name, Address, Phone, and a 3-digit SRI Establishment Code (e.g., "002").
2. **Given** the branch creation form, **When** I submit the form, **Then** the branch is registered, and its initial stock for all existing products is set to zero.
3. **Given** my company is on a subscription plan with a branch limit of 3, **When** I try to create a 4th active branch, **Then** the system blocks the creation with a validation error indicating that I have reached the limit for my plan.

---

### User Story 2 - Inventario por Local y Columna General (Priority: P0 — Architectural Core)
> **As a** Business Owner / Inventory Manager,
> **I want** product stock to be tracked separately for each branch,
> **So that** I can see how much stock is in each local and look at a general company matrix table.

**Acceptance Scenarios**:
1. **Given** the global product catalog page (`/products`), **When** the page loads, **Then** the stock column displays the total stock across all company branches, along with individual columns for each active branch showing its specific stock.
2. **Given** an entry or adjustment of stock in the inventory panel, **When** recording a movement, **Then** the system requires selecting a specific branch, and updates only the stock of that product in that branch.
3. **Given** a salesperson makes a sale at "Sucursal Norte" (`branch_id = 2`), **When** the sale is approved, **Then** the inventory exit movement is registered under "Sucursal Norte" and decrements stock only for that branch.
4. **Given** a product, **When** stock is decremented in "Sucursal Norte", **Then** the total stock column updates to reflect the sum of all branches.

---

### User Story 3 - Edición Completa de Planes por el Superadministrador (Priority: P1)
> **As a** Platform Superadmin,
> **I want to** set branch limits and electronic invoice limits when editing a plan,
> **So that** I can package and offer distinct pricing tiers.

**Acceptance Scenarios**:
1. **Given** I am creating or editing a plan on the superadmin panel, **When** the form loads, **Then** fields for "Límite de Locales" (`max_branches`) and "Límite de Facturas Mensuales" (`monthly_invoice_limit`) are visible and editable.
2. **Given** the plan form, **When** I save a plan with `max_branches = 3` and `monthly_invoice_limit = 500`, **Then** these values are successfully persisted to the database and correctly applied as subscription rules to all companies subscribed to that plan.

---

### User Story 4 - Selector de Periodo de Pago Dinámico con Descuentos (Priority: P1 — Premium UI)
> **As a** New Customer,
> **I want to** select my subscription duration and see dynamic pricing cards during registration,
> **So that** I can enjoy discounts on longer plans and understand the payment details clearly.

**Acceptance Scenarios**:
1. **Given** I am at the "Planes y Pago" step of the registration wizard, **When** I view the page, **Then** I see:
   - A dropdown or select button bar for subscription duration: **1, 3, 6, 12, 24, 36 meses**.
   - Premium cards layout (Basic, Standard, Premium) matching the reference design layout with active hover and highlighted badge ("MÁS VENDIDO") for Standard.
2. **Given** the pricing selector, **When** I toggle the subscription duration from 1 month to 12 months, **Then** the monthly price shown on each card dynamically updates using Javascript to apply a **15% discount** (e.g. Standard becomes $50.15/month instead of $59.00/month, showing the crossed-out original price, a `-15%` discount badge, and a summary text `Obtén 12 meses por S/ 601.80`).
3. **Given** the selected plan and duration, **When** I submit the form, **Then** the created pending payment record stores:
   - The selected plan code.
   - The duration in months (e.g., 12).
   - The discount percentage (e.g., 15.00).
   - The total amount calculated (e.g., 601.80).
4. **Given** a pending registration payment, **When** the superadmin approves the payment, **Then** the company's subscription expiration date (`subscription_expires_at`) is set to `now()->addMonths($payment->duration_months)`.

---

## 3. Requirements

### Functional Requirements
- **FR-001**: Model `Branch` (Locales) with fields `company_id`, `name`, `address`, `phone`, `establishment_code`, and `is_active`.
- **FR-002**: A company's active branches count must not exceed its subscription plan's `max_branches` limit.
- **FR-003**: The general product inventory table must display distinct columns for the stock of each active branch, plus a final column showing the sum of all branches (total stock).
- **FR-004**: Each `InventoryMovement` must register the `branch_id` in which the stock movement occurred.
- **FR-005**: All transaction entities (Sales, Purchases, Cash Boxes) must be assigned a `branch_id` upon creation to segregate transactions per physical local.
- **FR-006**: Users (administrators and sellers) must have a assigned `branch_id` in the database to restrict their operational transactions to their assigned branch.
- **FR-007**: Add `max_branches` and `monthly_invoice_limit` fields to the Superadmin plan creation/editing views and controller validation.
- **FR-008**: Update the registration wizard's billing view with a dynamic months duration selector (1, 3, 6, 12, 24, 36) and apply the correct discounts:
  - 3 months: 5% discount
  - 6 months: 10% discount
  - 12 months: 15% discount
  - 24 months: 20% discount
  - 36 months: 25% discount
- **FR-009**: Store `duration_months`, `discount_percentage`, and total `amount` in `subscription_payments` table during signup.
- **FR-010**: Extend subscription expiration in `SuperAdminService::approveCompany()` based on the payment's `duration_months` attribute instead of hardcoding `1 month`.

---

## 4. Proposed Database Changes

### New Migration: `create_branches_and_branch_product_tables`
- **branches**:
  - `id` (primary key)
  - `company_id` (foreign key → `companies`)
  - `name` (string)
  - `address` (string, nullable)
  - `phone` (string, nullable)
  - `establishment_code` (string, 3 chars, e.g. "001")
  - `is_active` (boolean, default true)
  - `timestamps`
- **branch_product** (Inventory stock per branch):
  - `id` (primary key)
  - `branch_id` (foreign key → `branches`)
  - `product_id` (foreign key → `products`)
  - `stock` (decimal: 14, 4, default 0)
  - Unique constraint on `[branch_id, product_id]`

### New Migration: `add_branch_id_to_operational_tables`
- Add `branch_id` column (foreign key → `branches`, nullable/restrict) to:
  - `users`
  - `sales`
  - `purchases`
  - `cash_boxes`
  - `inventory_movements`

### New Migration: `add_duration_and_pricing_fields_to_subscription_payments`
- Add columns to `subscription_payments`:
  - `duration_months` (integer, default 1)
  - `discount_percentage` (decimal: 5, 2, default 0)
  - `amount` (decimal: 10, 2, default 0)

### New Migration: `add_max_branches_to_plans_and_companies`
- Add columns:
  - `max_branches` (integer, default 1) to `plans` and `companies` tables.
  - Update default seeder to set basic: 1, standard: 3, premium: 9999.

---

## 5. Security & Validation Constraints
1. **Multi-Tenant Scoping**: All branch, branch-stock, and operational queries must respect the current user's `company_id` context.
2. **Operational Access**: Users assigned to a specific branch cannot record sales, purchases, or adjust inventory for a different branch.
3. **Limit Enforcement**: Creating a branch must trigger a check against the company's active branch limit.
4. **Validation**: Duration of months selected at signup must belong to the approved array: `[1, 3, 6, 12, 24, 36]`.

---

## 6. Success Criteria
- **SC-001**: Stock is correctly segmented per branch in the database, and the `/products` view renders dynamic stock columns for all active branches.
- **SC-002**: Sales decrements stock only at the branch where the sale occurred.
- **SC-003**: Superadmin can create/edit plan locales and invoice limits without errors.
- **SC-004**: Registration pricing calculator dynamically updates price and discount badges instantly with no latency when duration is clicked.
- **SC-005**: All integration tests for multi-branch sales, purchases, and subscriptions pass.
