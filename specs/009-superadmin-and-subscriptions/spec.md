# Feature Specification: Superadministrator Portal & Subscription Management

**Feature Branch**: `009-superadmin-and-subscriptions`

**Created**: 2026-06-20

**Status**: Draft

**Input**: User description: "Vamos a crear el superadministrador de nuestro sistema. Para esto vamos a crear un nuevo specify como esta en la carpeta '/specs' con sus 3 archivos. Para esto vamos primero a reorganizar los usuarios de nuestro sistema, Divididos en administrador que es el que ya se registra, y vendedor que va a tener un acceso mas limitado al sistema, como ventas y compras. De ahi vamos a trabajar en suscripciones, con 3 planes en mente: a) Plan Basic: 1 administrador y 2 vendedores. b) Plan standard: 2 administradores y 10 vendedores. y c) Plan premiun: acceso ilimitado. Esto significa que necesitamos una seccion para administrar los planes, y sus normas. Y otra seccion donde los administradores pueden crear usuarios vendedores, activiarlos y desactivarlos dependiendo el caso. De igual forma en el panel de superadministrador vamos a crear una seccion para activar y desactivar las suscripciones. Vamos a poder administrar las cantidades de usuarios en una tabla por empresa, si comprometer informacion personal de la empresa como compras y ventas."

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Reorganization of User Roles (Priority: P1)

As a system owner, I want users to be categorized into "Administrador" (existing representatives/owners) and "Vendedor" (sales reps/sellers) with distinct access levels, so that sellers only access customer interactions, sales records, and visual stock checking.

**Why this priority**: Correct access boundaries protect company settings, audit logs, purchasing details, and finance parameters from non-authorized staff.

**Independent Test**:
Log in as a user with the `vendedor` role and attempt to load `/dashboard`, `/purchases`, `/partners?type=proveedor`, `/products`, `/audit`, `/settings/catalogs`, or `/cashbox`. Verify that the system denies access or redirects. Verify that `/sales` (creating sales & registering payments), `/partners?type=cliente`, and `/inventory` (Kardex visual stock details) remain accessible and functional.

**Acceptance Scenarios**:
1. **Given** I am logged in as a `vendedor`, **When** I visit the dashboard, settings, audits, purchases, suppliers, products, or cash box views, **Then** I am redirected to `/sales` (the primary sales screen) with an authorization warning.
2. **Given** I am logged in as a `vendedor`, **When** I visit customers list, create sales, register sale payments, or check stock in Kardex, **Then** the views load and operate successfully.
3. **Given** I am logged in as an `administrador` (e.g. `owner` or `company_representative`), **When** I browse the system, **Then** I have full access to all modules, settings, audits, cash box views, and user management.

---

### User Story 2 - Seller User Management for Administrators (Priority: P1)

As a company administrator, I want to manage my company's sellers (list, create, activate/deactivate) in a dedicated section, keeping count within my subscription plan limits.

**Why this priority**: Business owners must be able to provision accounts for their staff autonomously.

**Independent Test**:
Log in as a company administrator. Navigate to the new "Vendedores" section. Create a seller. Deactivate a seller and check if that seller's login fails. Verify that if standard/basic limits are exceeded, creation is blocked.

**Acceptance Scenarios**:
1. **Given** I am on the "Vendedores" management page, **When** I fill out the creation form (name, email, password), **Then** a new seller user is created for my company (active by default, bypasses OTP verification) and listed on the page.
2. **Given** my company is on "Plan Basic" and already has 2 active sellers, **When** I try to create a 3rd seller, **Then** the form rejects the creation with a validation error stating that the plan limit has been reached.
3. **Given** I toggle a seller's status to "Inactive", **When** that seller attempts to log in, **Then** their authentication is rejected.

---

### User Story 3 - Superadministrator Portal (Priority: P1)

As a system superadministrator, I want to log in to a centralized dashboard where I can view a table listing all companies, user counts, subscription status, and audit/reconcile submitted payment receipts for plan upgrades or renewals.

**Why this priority**: Crucial for SaaS administrative operations, billing management, and global oversight.

**Independent Test**:
Log in as a `superadmin` user. Access `/superadmin/dashboard`. Verify that a summary of all companies is visible showing user counts but no company sales/purchases amounts. Verify that pending upgrade/renewal payments show details and receipt image. Click "Aprobar" on a renewal payment and check if the company's expiration date is extended by 1 month.

**Acceptance Scenarios**:
1. **Given** I am logged in as a `superadmin`, **When** I access the dashboard, **Then** I see KPI cards (Total Companies, Active/Inactive Subscriptions, Pending Payments) and a table of all registered enterprises showing name, plan, status, and expiration date.
2. **Given** I click "Activar/Desactivar" on an enterprise's subscription status in the table, **When** I confirm, **Then** the database updates and the status flips.
3. **Given** I audit a pending payment for a plan change or renewal, **When** I click "Aprobar", **Then** the company's plan is updated or its expiration date is extended by the subscription period (minimum 1 month).

---

### User Story 4 - Subscription Enforcement & Access Suspension (Priority: P1)

As a system owner, I want companies with deactivated subscriptions to be suspended from system operations, so that only paying or active companies can use the software.

**Why this priority**: Essential to enforce subscription business models.

**Independent Test**:
In the Superadmin dashboard, deactivate the subscription of a target company. Attempt to log in with an administrator account of that company, or refresh any page while logged in. Verify they are locked out and redirected to a suspension warning view.

**Acceptance Scenarios**:
1. **Given** an administrator of Company A is logged in, **When** the Superadmin deactivates Company A's subscription and the administrator loads any route, **Then** they are redirected to `/subscription-suspended` warning page.
2. **Given** a user attempts to log in to a deactivated company, **When** credentials are input, **Then** they are blocked with a message indicating their subscription is suspended.

---

### User Story 5 - Plan Selection and Payment Registration in Wizard (Priority: P1)

As a registering company administrator, I want to select my subscription plan (Basic or Standard) and register my bank transfer payment details (Origin Bank, Destination Account, and Receipt Image upload) during the registration wizard, so that my subscription request can be submitted for verification.

**Why this priority**: Payments must be registered at signup before company accounts can be activated.

**Independent Test**:
Start the registration wizard. Complete steps 1-3. Select a plan and input payment details (Bank of origin, destination account) and upload a mock receipt image. Complete registration. Verify that the company is created with `subscription_status` as `pending_approval` and subscription fields populated.

**Acceptance Scenarios**:
1. **Given** I am on the new Plan and Payment step of the registration wizard, **When** I choose a plan and fill in my bank of origin, select a destination account, and upload the transfer receipt image, **Then** the data is saved to my session.
2. **Given** I submit the registration review page, **When** the transaction completes, **Then** a Company is created with `subscription_status = 'pending_approval'` and user status `pending_activation`, and the receipt image is stored in public storage.

---

### User Story 6 - Superadmin Payment Approval Workflow (Priority: P1)

As a superadministrator, I want to view a list of pending company subscriptions along with their bank payment details and receipt images, so that I can approve or reject the activation.

**Why this priority**: Required to reconcile manual bank transfers before granting system access.

**Independent Test**:
Log in as superadmin. Navigate to the pending subscriptions tab. Inspect the bank details and receipt image. Click "Aprobar" or "Rechazar" and verify the company's status in the database.

**Acceptance Scenarios**:
1. **Given** I am on the Superadmin dashboard, **When** I look at the pending subscriptions table, **Then** I see the Bank of Origin, Destination Account, and a link/thumbnail to view the receipt image.
2. **Given** I click "Aprobar" on a pending subscription, **When** I confirm, **Then** the company `subscription_status` is updated to `active` and its administrator user is notified/activated.
3. **Given** I click "Rechazar" on a pending subscription, **When** I confirm, **Then** the company `subscription_status` is set to `rejected` (blocking their login).

---

### User Story 7 - Multi-Enterprise Tenant Data Isolation (Priority: P1)

As a company user, I want all records created under my account (sales, purchases, products, payments, cashbox, audits, partners, categories) to be strictly isolated by `company_id`, so that other enterprises cannot see or access our business data.

**Why this priority**: Multi-tenant data integrity and privacy are non-negotiable security constraints.

**Independent Test**:
Create two companies (A and B). Create a product for Company A. Log in as a user from Company B and list products. Verify that Company A's product is not returned. Attempt to query Company A's product directly by ID and verify it returns a 404.

**Acceptance Scenarios**:
1. **Given** I am authenticated as a tenant user, **When** I execute any query on system tables, **Then** the system automatically applies a global scope filtering by `company_id = auth()->user()->company_id`.
2. **Given** I try to load a record from another company directly via URL, **When** the request is resolved, **Then** the database query returns null and the system aborts with a `404 Not Found` response.

---

### User Story 8 - Subscription Plan Upgrades and Renewals (Priority: P1)

As a company administrator, I want to request a plan change (upgrade/downgrade) or a subscription renewal in a dedicated "Suscripción" settings panel by registering my bank transfer payment details (Origin Bank, Destination Bank, screenshot upload of receipt), so that the change or renewal is processed once audited by the superadmin.

**Why this priority**: Empowers administrators to manage their accounts and billing operations autonomously, avoiding manual external channels.

**Independent Test**:
Log in as a company admin. Navigate to `/settings/subscription`. Click "Cambiar Plan" (e.g. from Basic to Standard) or "Renovar Suscripción". Select standard plan, fill bank details, upload a receipt image, and submit. Verify that a pending payment record is created in the database and is visible on the superadmin's pending approvals list.

**Acceptance Scenarios**:
1. **Given** I am on the company subscription management page, **When** I choose to upgrade my plan or renew my subscription, and input bank of origin, select destination account, and upload the transfer receipt image, **Then** a pending subscription payment record is generated for my company.
2. **Given** my company has a pending subscription payment request, **When** the superadmin clicks "Aprobar", **Then** my company plan is updated or the subscription expiration date is extended by the subscription duration (minimum 1 month).

---

### User Story 9 - Expiration Alert Banners (Priority: P1)

As a company user, I want to see a prominent alert banner at the top of the interface when my subscription is within 5 days of expiring, so that the company administrator knows to register a renewal payment.

**Why this priority**: Essential to alert users beforehand to prevent sudden lockout and operational disruptions.

**Independent Test**:
Set a company's subscription expiration date in the database to 3 days in the future. Log in as an administrator or seller of that company. Verify that a warning banner is shown on the dashboard and other views. Update the expiration date to 10 days in the future and verify that the banner is hidden.

**Acceptance Scenarios**:
1. **Given** a company's subscription is active, **When** the expiration date is 5 days or less in the future, **Then** a yellow warning alert banner is displayed in the main layout saying "Su suscripción vencerá en [X] días. Por favor registre su pago para renovarla."
2. **Given** the expiration date has passed (`now() > subscription_expires_at`), **When** any user of the company makes a request, **Then** they are blocked and redirected to `/subscription-suspended` warning page.

---

## Edge Cases

- **Session Expiry & Middle-of-Session Suspension**: A company might be suspended while its employees are actively using the application. The JWT authentication middleware must check the company's subscription status on *every request* (scoping or caching company status) to immediately block access.
- **Role Transition of Representative**: Registered user roles `owner` or `company_representative` must count as "Administradores" for the plan limits.
- **Downgrading Plan with Overflowing Users**: If a superadmin downgrades a company from Standard to Basic (limit: 2 sellers), but they have 5 active sellers, what happens?
  - Rule: Existing users remain in the DB, but the admin cannot create or reactivate any users until they delete/deactivate enough users to fall below the new limit. A notification warning will be displayed on their dashboard.
- **Accidental Superadmin Scoping**: The superadmin user MUST have `company_id = null`. The system must never associate a superadmin with a tenant, nor allow them to access data pages like `/sales` or `/purchases` (they are redirected to `/superadmin/dashboard` on login).

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Implement the seller role `vendedor`. A seller can only see and interact with:
  - Sales (`sales.*` routes) where they can register sales and their payments.
  - Customers (`partners.*` routes) but restricted to Customer list/details (`?type=cliente`).
  - Kardex / Inventory (`inventory.*` routes) with visual-only access to confirm product stocks.
- **FR-002**: Deny `vendedor` users access to:
  - Dashboard (`/dashboard` route, redirecting them to `/sales` instead).
  - Purchases (`purchases.*` routes)
  - Suppliers (`partners.*` routes with `?type=proveedor`)
  - Products management (`products.*` routes)
  - Cash Box (`cashbox.*` routes)
  - Audit Logs (`audit.*` routes)
  - Configuration (`settings.*` routes)
  - User management sections
- **FR-003**: Create a "Vendedores" section under `/sellers` for company admins. They can:
  - View a table of all user accounts under their company.
  - Create a new seller user (which defaults to status `active` and bypasses OTP).
  - Toggle user active status (to block logins).
- **FR-004**: Implement subscription limits for the 3 plans:
  - **Basic**: Max 1 administrator, Max 2 sellers.
  - **Standard**: Max 2 administrators, Max 10 sellers.
  - **Premium**: Unlimited administrators, Unlimited sellers.
- **FR-005**: Block creation or activation of users if it violates the active company plan limits.
- **FR-006**: Create a Superadministrator panel at `/superadmin/dashboard` accessible ONLY by users with role `superadmin`.
- **FR-007**: The Superadmin portal MUST show:
  - KPIs: Total Companies, Active, Inactive, Pending Approval.
  - A table of all Companies showing: ID, Name, Plan, Status, Expiration Date, Admin count, Seller count, and Actions.
  - Actions: Audit pending upgrade/renewal receipts, Toggle subscription status (Active/Inactive).
  - No details regarding sales or purchases values are shown to Superadmin to protect business confidentiality.
- **FR-008**: Block any request (except log out) for users belonging to a company with status `inactive`, `suspended`, `rejected`, or `pending_approval` and redirect them to `/subscription-suspended` (or a pending approval page).
- **FR-009**: The registration wizard MUST include a "Planes y Pago" step containing:
  - Plan selection cards (Basic, Standard).
  - Origin bank text input field.
  - System destination bank accounts selector.
  - File upload for the transfer/deposit receipt image.
- **FR-010**: New registrations must save the uploaded image to secure storage and create the Company with `subscription_status = 'pending_approval'`.
- **FR-011**: The Superadmin panel MUST provide a "Suscripciones Pendientes" view where the admin can:
  - View the payment data (Origin Bank, Destination Bank, timestamp, type: 'signup', 'renewal', 'upgrade').
  - Inspect the uploaded receipt image in a modal/lightbox.
  - Click "Aprobar" to activate/renew/upgrade the company subscription and extend expiration date, or "Rechazar" to reject it.
- **FR-012**: Multi-Enterprise Isolation: All system tables (including `categories`, `taxes`, `payment_methods`, `partners`, `products`, `inventory_movements`, `sales`, `sale_details`, `purchases`, `purchase_details`, `payments`, `audit_logs`, `cash_boxes`) MUST contain a `company_id` (or `idEmpresa`) foreign key column.
- **FR-013**: Enable a Global Query Scope in the database layer that automatically filters all model reads and writes by `company_id = auth()->user()->company_id` when a non-superadmin user is authenticated.
- **FR-014**: Direct query lookups using route-model binding or explicit find calls for records of a different company MUST fail with a `404 Not Found` response.
- **FR-015**: Plan upgrades and renewals MUST be initiated exclusively by company administrators through their subscription settings panel.
- **FR-016**: A request to change a plan or renew the subscription requires registering a new payment (providing Origin Bank, selecting destination account, and uploading the receipt image), which creates a pending payment record.
- **FR-017**: Subscription Duration: When a subscription payment (signup, renewal, or upgrade) is approved by the Superadmin, the company's expiration date (`subscription_expires_at`) is set or extended by a minimum duration of 1 month.
- **FR-018**: Expiration Warnings: If a company's subscription is active but expires in 5 days or less, a prominent warning banner is displayed at the top of the company's workspace: "Su suscripción vencerá en [X] días. Por favor registre su pago para renovarla."
- **FR-019**: Automated Lockout: If the system date passes the company's `subscription_expires_at`, their status automatically becomes inactive, immediately locking out users.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Role access checks run in < 1ms on every request through middleware.
- **SC-002**: A company with subscription status `inactive` or `pending_approval` cannot perform any read or write operation on the system.
- **SC-003**: Verification is in place preventing company admins from registering more users than allowed by their plan limits.
- **SC-004**: No database queries relating to sales, purchases, or payments are executed or made available on the Superadmin routes.
- **SC-005**: Global query scopes automatically isolate all tenant tables by `company_id` ensuring 100% data confidentiality between enterprises.
- **SC-006**: Direct URL manipulation trying to access foreign company IDs is automatically aborted with a 404.
