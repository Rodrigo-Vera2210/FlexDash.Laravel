# Implementation Plan: Superadministrator Portal & Subscription Management

**Branch**: `009-superadmin-and-subscriptions` | **Date**: 2026-06-20 | **Spec**: `/specs/009-superadmin-and-subscriptions/spec.md`

## Summary
This plan details the technical steps to:
1. Reorganize user roles, establishing access restrictions for the new `vendedor` role.
2. Store and enforce subscription plan limits (Basic, Standard, Premium) and activation status in the `companies` table.
3. Add a "Planes y Pago" step to the registration wizard to select plans, specify origin bank, select destination bank, and upload transfer screenshots.
4. Implement a Superadministrator portal to view company metadata, inspect uploaded payment receipt images, and approve/reject company accounts.
5. Create a `BelongsToCompany` trait applying a global query scope by `company_id` to isolate all database resources across enterprises.
6. Setup a middleware block that intercepts operations for companies with inactive subscriptions.

---

## Technical Context
- **Language/Version**: PHP 8.2+ with Laravel 12
- **Database**: SQLite
- **Styling**: Tailwind CSS (loaded via CDN as structured in `layouts/app.blade.php`)
- **Authentication**: JWT-based stateless authentication (enforced via `EnsureJwtAuthenticated` middleware)

---

## Constitution Check
- **TDD Requirement**: Failing feature and integration tests must be written first to test role restrictions, subscription checks, limit enforcement, and controller actions.
- **Module Architecture**: Business logic is separated into two new modules: `app/Modules/Seller` (for seller account provisioning) and `app/Modules/SuperAdmin` (for superadmin portal operations).
- **Service Placement**: All business logic services will reside in their respective module's `Services/` directories.
- **Clean Code**: Controller methods will remain under 30 lines. Database operations will be performed through Laravel Eloquent relationships or Repository patterns where appropriate.
- **JWT Authorization**: Verify role payloads on the JWT. The `EnsureJwtAuthenticated` middleware will decode the token, check the role, verify the status, and authenticate the session.

---

## Project Structure

We will modify or create the following files:
```text
app/Http/Middleware/
├── EnsureJwtAuthenticated.php    [MODIFY - add active company subscription validation]
├── EnsureSuperAdmin.php          [NEW - restrict access to superadmin routes]
└── RestrictSellerAccess.php      [NEW - block seller users from accessing admin routes]

app/Traits/
└── BelongsToCompany.php          [NEW - global query scope trait for database-level tenant isolation]

app/Modules/Seller/
├── Controllers/
│   └── SellerController.php      [NEW - handles listing, creation, and toggling of sellers]
├── Services/
│   └── SellerService.php         [NEW - handles limits validation and creation logic]
└── Tests/
    └── Feature/
        └── SellerManagementTest.php [NEW - TDD tests for plan limits and seller creation]

app/Modules/Registration/
├── Controllers/
│   └── RegistrationController.php [MODIFY - add Plan & Payment wizard step]
└── Services/
    └── RegistrationService.php    [MODIFY - store plan selection, payment data, and receipt image]

app/Modules/SuperAdmin/
├── Controllers/
│   └── SuperAdminController.php  [NEW - handles portal dashboard and company plan actions]
├── Services/
│   └── SuperAdminService.php     [NEW - business logic for listing companies and updating subscriptions]
└── Tests/
    └── Feature/
        └── SuperAdminDashboardTest.php [NEW - TDD tests for superadmin dashboard and actions]

database/migrations/
├── 2026_06_20_000001_add_subscription_fields_to_companies_table.php [NEW - subscription columns]
├── 2026_06_20_000002_create_default_superadmin_user.php             [NEW - seeds superadmin account]
├── 2026_06_20_000003_create_subscription_payments_table.php         [NEW - logs all payments, upgrades, renewals]
└── 2026_06_20_000004_add_company_id_to_all_system_tables.php       [NEW - foreign key in system tables]

resources/views/
├── layouts/
│   └── app.blade.php             [MODIFY - adapt sidebar navigation to hide items by role]
├── registration/
│   └── wizard.blade.php          [MODIFY - render plan and payment registration fields]
├── sellers/
│   ├── index.blade.php           [NEW - admin view of sellers list]
│   └── create.blade.php          [NEW - create seller form]
├── superadmin/
│   └── dashboard.blade.php       [NEW - superadmin companies list and status toggling]
└── subscription/
    └── suspended.blade.php       [NEW - static suspended warning view]

routes/
└── web.php                       [MODIFY - register superadmin, seller management, and suspended routes]
```

---

## Proposed Changes

### 1. Database Schema Extensions

#### [NEW] [2026_06_20_000001_add_subscription_fields_to_companies_table.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/database/migrations/2026_06_20_000001_add_subscription_fields_to_companies_table.php)
Add subscription status and expiration columns to the `companies` table:
```php
Schema::table('companies', function (Blueprint $table) {
    $table->string('subscription_plan')->default('basic'); // basic, standard, premium
    $table->string('subscription_status')->default('pending_approval'); // active, inactive, pending_approval, rejected
    $table->timestamp('subscription_expires_at')->nullable();
});
```

#### [NEW] [2026_06_20_000002_create_default_superadmin_user.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/database/migrations/2026_06_20_000002_create_default_superadmin_user.php)
Creates a default `superadmin` user account:
- Name: `Super Admin`
- Email: `superadmin@flexdash.com`
- Password: Hashed password
- Role: `superadmin`
- Status: `active`
- Company ID: `null`

#### [NEW] [2026_06_20_000003_create_subscription_payments_table.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/database/migrations/2026_06_20_000003_create_subscription_payments_table.php)
Creates a table to log all subscription payment entries, upgrades, and renewals:
```php
Schema::create('subscription_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
    $table->string('plan'); // basic, standard
    $table->string('bank_origin');
    $table->string('account_destination');
    $table->string('receipt_path');
    $table->string('status')->default('pending'); // pending, approved, rejected
    $table->string('type')->default('signup'); // signup, upgrade, renewal
    $table->timestamps();
});
```

#### [NEW] [2026_06_20_000004_add_company_id_to_all_system_tables.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/database/migrations/2026_06_20_000004_add_company_id_to_all_system_tables.php)
Iterates over all data tables (categories, taxes, payment_methods, partners, products, inventory_movements, sales, sale_details, purchases, purchase_details, payments, audit_logs, cash_boxes) and adds a nullable foreign key column:
```php
Schema::table($table, function (Blueprint $table) {
    $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
});
```

---

### 2. Tenant Isolation Global Scope

#### [NEW] [BelongsToCompany.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Traits/BelongsToCompany.php)
Implements Laravel's global query scoping for data separation:
- During model boot, registers a query scope that automatically filters queries by `company_id = auth()->user()->company_id` if a user is authenticated (excluding superadmin).
- Hook model's `creating` event to automatically populate the `company_id` column of the saving record with the authenticated user's `company_id`.
- Applied to all tenant models (`Category`, `Tax`, `PaymentMethod`, `Partner`, `Product`, `InventoryMovement`, `Sale`, `Purchase`, `Payment`, `AuditLog`, `CashBox`).

### 3. Middleware & JWT Modifications

#### [MODIFY] [EnsureJwtAuthenticated.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Http/Middleware/EnsureJwtAuthenticated.php)
Update middleware to check user role and company subscription:
1. Decode JWT.
2. If `user->role !== 'superadmin'`, retrieve the associated company.
3. Check `company->subscription_status === 'active'`.
4. If company status is `pending_approval`, `inactive`, `rejected`, or `suspended`:
   - If the request expects JSON, return a `403 Forbidden` response.
   - If the request is a standard web route, redirect to `/subscription-suspended` (showing their status e.g. "Pendiente de Aprobación" or "Suspendida").

#### [NEW] [EnsureSuperAdmin.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Http/Middleware/EnsureSuperAdmin.php)
Ensure only superadmins can load routes under `/superadmin/`:
```php
public function handle(Request $request, Closure $next)
{
    if (auth()->user()?->role !== 'superadmin') {
        return redirect()->route('dashboard')->with('error', 'Acceso denegado. Se requiere cuenta de Superadministrador.');
    }
    return $next($request);
}
```

#### [NEW] [RestrictSellerAccess.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Http/Middleware/RestrictSellerAccess.php)
Enforces access limits for `vendedor` users:
- Block access to `/dashboard` (Dashboard), `/purchases` (Compras), `/products` (Productos), `/partners` of type supplier (`?type=proveedor`), `/cashbox` (Caja Chica), `/audit` (Auditoría), and `/settings` (Configuración).
- Allow access to `/sales` (Ventas, including registering sales and payments), `/partners` of type customer (`?type=cliente`), and `/inventory` (Kardex).
- Redirect: Any attempt to access `/dashboard` or other blocked routes redirects to `/sales` with an warning message.

---

### 4. Registration Wizard Payment Step

#### [MODIFY] [RegistrationController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Registration/Controllers/RegistrationController.php)
- Insert a new step in the registration wizard flow: Step 4 "Planes y Pago".
- Add action methods `showPlanSelection()` and `postPlanSelection(Request $request)` to parse plan, origin bank, selected destination account, and the uploaded receipt image.
- Validate file uploads: screenshots must be valid images (JPEG, PNG, max 4MB).

#### [MODIFY] [RegistrationService.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Registration/Services/RegistrationService.php)
- Store the uploaded screenshot to `public/receipts/` directory.
- Create the company record with:
  - `subscription_plan` (e.g. basic, standard)
  - `subscription_status` = `'pending_approval'`
  - `payment_bank_origin`
  - `payment_account_destination`
  - `payment_receipt_path`
- Create the admin user with status `'pending_activation'` (which blocks their login until company is activated).

---

### 5. Business Services & Superadmin Controllers

#### [NEW] [SellerService.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Seller/Services/SellerService.php)
Provides logic to manage seller sub-accounts and validate limits:
- `checkLimitReached(Company $company)`: Calculates current user counts under the company.
  - Basic: limit 1 admin, 2 sellers.
  - Standard: limit 2 admins, 10 sellers.
  - Premium: unlimited.
- `createSeller(array $data, Company $company)`: Validates active limits. If limit is exceeded, throws a `ValidationException`. If valid, creates user with role `vendedor`, status `active`, bypassing OTP.

#### [NEW] [SuperAdminService.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/SuperAdmin/Services/SuperAdminService.php)
Provides data for the superadmin dashboard:
- `getDashboardMetrics()`: Calculates companies totals, active, inactive, and pending approvals.
- `getCompaniesList()`: Fetches companies with counts of admin and seller users.
- `toggleSubscription(Company $company)`: Changes status between `active` and `inactive`.
- `updatePlan(Company $company, string $plan)`: Changes plan type.
- `approveCompany(Company $company)`: Approves company, sets status to `active`, and activates its administrator user account.
- `rejectCompany(Company $company)`: Rejects company, sets status to `rejected`.

---

### 6. Controllers & Routes

#### [MODIFY] [web.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/routes/web.php)
Register new route groups and middleware definitions:
- Register `subscription.suspended` static route.
- Register `sellers` resource inside standard `auth.jwt` and restricted to admin/representative roles.
- Register `superadmin` prefix group guarded by `auth.jwt` and `EnsureSuperAdmin` middleware.
- Register `superadmin.companies.approve` and `superadmin.companies.reject` POST endpoints.

#### [NEW] [SellerController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Seller/Controllers/SellerController.php)
Sellers resource endpoints:
- `index()`: Display list of company sellers.
- `create()`: Render creation form.
- `store()`: Validate request and invoke `SellerService->createSeller()`.
- `toggleStatus()`: Activate/deactivate a seller account.

#### [NEW] [SuperAdminController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/SuperAdmin/Controllers/SuperAdminController.php)
Superadmin routes:
- `dashboard()`: Fetch metrics and lists (active/pending).
- `approveCompany(Company $company)`: Approve payment and activate.
- `rejectCompany(Company $company)`: Reject subscription.
- `toggleSubscription(Company $company)`: Action method.
- `changePlan(Company $company, Request $request)`: Update plan.

---

### 7. Views & Navigation Layout

#### [MODIFY] [app.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/layouts/app.blade.php)
Render the sidebar dynamically based on roles:
- **Superadmin**: Hides commercial, inventory, finance sidebar items. Shows only a new `Superadmin Panel` link and Audit logs.
- **Admin**: Shows everything, including the new "Vendedores" management link under the "Sistema" section.
- **Seller**: Shows only Ventas, Clientes (`partners.index` with `type=cliente`), and Kardex. Hides Dashboard, Compras, Proveedores, Productos, Caja Chica, Auditoría, Configuración, and Vendedores links.

#### [NEW] [suspended.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/subscription/suspended.blade.php)
Suspended/Pending warning panel page: red/yellow alert design informing the representative about their subscription status (e.g. pending superadmin verification).

#### [NEW] [index.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/sellers/index.blade.php)
Admin listing view showing sellers names, emails, active/inactive statuses, and a toggle status button.

#### [NEW] [dashboard.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/superadmin/dashboard.blade.php)
Premium layout displaying KPI cards and a companies table. Includes:
- A dedicated "Suscripciones Pendientes" tab listing registrations, origin bank, destination account, and a preview modal of their receipt image.
- "Aprobar" & "Rechazar" actions.
- An "Empresas Registradas" tab with status toggling and plan changes.

#### [MODIFY] [wizard.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/registration/wizard.blade.php)
- Add "Planes y Pago" wizard step design: plan radio cards (Basic vs Standard), Bank accounts listing, Origin Bank input, and File upload drag & drop box.

---

## Verification Plan

### Automated Tests
We will write feature tests to verify:
1. `SellerManagementTest.php`:
   - Admin can view, create, and toggle sellers.
   - Seller creation enforces limit validation per company plan (Basic, Standard).
   - Inactive sellers cannot log in.
2. `SuperAdminDashboardTest.php`:
   - Non-superadmins are rejected from `/superadmin/*`.
   - Superadmin can toggle subscription status, change plans, and approve/reject pending company accounts.
3. `SubscriptionEnforcementTest.php`:
   - Requests from users belonging to inactive or pending companies are redirected to `/subscription-suspended`.
4. `TenantIsolationTest.php`:
   - Verify that all database operations automatically isolate records by `company_id` using the `BelongsToCompany` trait.
   - Assert that users from Company A cannot retrieve or update records of Company B.

Run tests using:
```bash
php artisan test
```

### Manual Verification
- Register a new company, select a plan, upload a receipt. Verify that redirect blocks access immediately.
- Log in as Superadmin, inspect receipt image in modal, approve the company.
- Log in as the company admin, verify access is restored, and try to create sellers.
- Deactivate the company and check lockout.
- Check that sales/purchases made by different companies are isolated.
