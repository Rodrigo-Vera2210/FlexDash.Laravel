# Feature Specification: Modular Plans and Subscription Customizations

**Feature Branch**: `010-modular-plans-and-restrictions`

**Created**: 2026-06-21

**Status**: Draft

---

## 1. Feature Description & Context

This feature introduces a fully dynamic, database-driven Plan Management system for FlexDash. We are moving away from hardcoded subscription plan structures to allow complete flexibility and control for the Platform Superadministrator. 

Each subscription plan is defined by 4 major variables:
1. **Administrators Count**: The maximum allowed number of administrators (active users with roles `owner` or `company_representative`).
2. **Sellers Count**: The maximum allowed number of active seller accounts (role `vendedor`).
3. **Monthly Transactions Limit**: The combined maximum number of monthly transactions (Sales + Purchases) a company is allowed to record.
4. **Accessible Modules**: The exact modules enabled under the plan. By default, the POS basic modules are:
   - **Ventas** (Sales routing and checkout)
   - **Clientes** (Customer directories and details)
   - **Caja Chica** (Cashbox opening, adjustments, closure)
   - **Settings** (Sellers, Catalog, Profile, Subscription settings)
   - **Kardex** (Inventory adjustments and manual product stock management)
   - Starting from the second plan (Standard onwards), **Compras** (Purchases log) and **Proveedores** (Supplier registers) are incorporated.

Crucially, **everything must be fully modular**. The Superadministrator must be able to add or remove any module or override specific limit variables on a **per-company level** (custom subscription overrides), and the application must adapt its navigation and restrict endpoint access dynamically.

Additionally, this specification solves the issue of the Superadministrator being redirected to the dashboard when trying to view the platform audit log.

---

## 2. User Scenarios & Testing

### User Story 1: Dynamic Plan Administration by Superadmin (Priority: P1)
As a platform Superadministrator, I want a section in the portal to manage active subscription plans (create, read, edit, delete) where I can specify the pricing, administrators limit, sellers limit, monthly transaction limit, and default active modules.

**Independent Test**:
Log in as superadmin, navigate to `/superadmin/plans`. Create a new plan named "Premium" with custom variables, modify its limits, and delete a plan. Ensure the changes are reflected in database.

**Acceptance Scenarios**:
1. **Given** I am logged in as a superadmin on `/superadmin/dashboard`, **When** I click "Administración de Planes" in the sidebar, **Then** I am taken to `/superadmin/plans` showing a list of current active plans.
2. **Given** I create a new plan, **When** I fill in the form (name, code, price, max admins, max sellers, max monthly transactions, and check default modules), **Then** the plan is saved and appears in the registered list.
3. **Given** I modify an existing plan, **When** I save the changes, **Then** the new defaults apply to all companies subscribed to that plan that do not have custom overrides.

---

### User Story 2: Subscription Module and Limit Customization (Priority: P1)
As a platform Superadministrator, I want to view a company's details, see its default plan settings, and override its active modules or transaction limits dynamically, so that I can tailor a custom subscription package for specific clients.

**Independent Test**:
Log in as superadmin, select a company under "Basic" plan. Go to its detail page. Override its limits to allow 5 sellers and check the "Compras" module. Log in as an administrator of that company and verify that the "Compras" module is now visible and accessible.

**Acceptance Scenarios**:
1. **Given** I am on a company's detail page, **When** I customize its active modules (e.g., adding/removing checkmarks) or change its custom seller limit, **Then** the custom configurations are stored in the database for that company.
2. **Given** a company on "Basic" plan has a custom override enabling "Compras", **When** its administrators log in, **Then** they can access the "Compras" section, which would normally be restricted on the basic plan.
3. **Given** a company has active modules customized, **When** a module is turned off (e.g., "Caja Chica"), **Then** the corresponding menu disappears from the client's sidebar and attempts to access `/cashbox` redirect to the dashboard with an access error.

---

### User Story 3: Monthly Transaction Limits Enforcement (Priority: P2)
As a company user, I want the system to restrict me from registering new Sales or Purchases if my company has reached its monthly transaction limit, so that I am prompted to upgrade my plan or contact support.

**Independent Test**:
Set a company's `max_monthly_transactions` to 3. Create 3 sales in the current calendar month. Attempt to create a 4th sale or a purchase. Verify that the creation is blocked and an error is shown.

**Acceptance Scenarios**:
1. **Given** my company has registered 100 sales and purchases in June 2026, **And** our limit is 100, **When** I submit a new Sale or Purchase form, **Then** the request is rejected with a validation error: "El límite de transacciones mensuales para su suscripción (100) ha sido alcanzado. Por favor, solicite una mejora de plan."
2. **Given** my company has reached the monthly limit, **When** a new month starts (July 2026), **Then** the transaction counters reset, allowing me to register transactions up to the limit again.

---

### User Story 4: Superadmin Audit Navigation Fix (Priority: P1)
As a platform Superadministrator, I want to click on the "Auditoría" section in the sidebar and view the complete system audit logs without being redirected back to the dashboard.

**Independent Test**:
Log in as superadmin, click "Auditoría" in the sidebar. Verify that `/superadmin/audits` loads successfully showing logs across all companies and includes custom subscription audit logs.

**Acceptance Scenarios**:
1. **Given** I am a superadmin, **When** I visit the Audit page via the sidebar link, **Then** I am NOT redirected to `/superadmin/dashboard` or `/dashboard`.
2. **Given** I am on the Audit list page, **When** I inspect subscription status changes, **Then** I see descriptive warning badges (e.g. "Suscripción") instead of default fallback labels.
