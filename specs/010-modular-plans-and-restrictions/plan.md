# Technical Implementation Plan: Modular Plans and Subscription Customizations

**Branch**: `010-modular-plans-and-restrictions` | **Date**: 2026-06-21

**Spec**: `/specs/010-modular-plans-and-restrictions/spec.md`

---

## 1. Summary of Technical Approach

1. **Database Schema Setup**:
   - Create a `plans` table to hold dynamic plan definitions (pricing, user counts, transaction limits, modules list).
   - Add columns to the `companies` table: `active_modules` (json), `max_monthly_transactions` (integer), `max_admins` (integer), `max_sellers` (integer) for overrides.
   - Seed default plans (`basic`, `standard`, `premium`) in a migration or database seeder.
2. **Model Extensions**:
   - Create the `Plan` model.
   - Enhance the `Company` model with relationships, attributes accessors, and helper methods.
3. **Module Access Middleware**:
   - Build `EnsureModuleAccess` to block unauthorized module routing.
4. **Transaction Limits Check**:
   - Build checks inside the controllers for `Sale` and `Purchase` to compare current monthly transaction count with `max_monthly_transactions`.
5. **Superadmin Audit Correction**:
   - Update `EnsureJwtAuthenticated` to allow superadmin through to `/superadmin/audits`.
   - Update `layouts/app.blade.php` to link superadmin to the corrected route.
6. **Superadmin CRUD for Plans & Custom Overrides**:
   - Add Plan management views and controllers.
   - Add custom limit configuration forms to the company details view.

---

## 2. Project File Structure

We will create or modify the following files:

```text
app/Models/
└── Plan.php                      [NEW - Eloquent model representing subscription plans]

app/Http/Middleware/
├── EnsureJwtAuthenticated.php    [MODIFY - allow superadmin to bypass /superadmin/audits]
└── EnsureModuleAccess.php        [NEW - check company module access on routes]

app/Modules/Registration/Models/
└── Company.php                   [MODIFY - add plan relations, override accessors, and module checks]

app/Modules/Seller/Services/
└── SellerService.php             [MODIFY - evaluate dynamic seller limit from company property]

app/Modules/Sale/Controllers/
└── SaleController.php            [MODIFY - enforce transaction limits in store()]

app/Modules/Purchase/Controllers/
└── PurchaseController.php        [MODIFY - enforce transaction limits in store()]

app/Modules/SuperAdmin/Controllers/
└── SuperAdminController.php      [MODIFY - add CRUD for plans and custom override endpoints]

app/Modules/SuperAdmin/Services/
└── SuperAdminService.php         [MODIFY - add business logic for CRUD plans and custom overrides]

database/migrations/
├── 2026_06_21_033500_create_plans_table.php                  [NEW - create plans table & override fields in companies]
└── 2026_06_21_033510_seed_default_plans.php                 [NEW - seed basic, standard, premium plans]

routes/
└── web.php                       [MODIFY - register /superadmin/plans routes, /superadmin/audits route, register middleware]

resources/views/
├── layouts/
│   └── app.blade.php             [MODIFY - dynamically show/hide sidebar menu items based on modules access]
├── audit/
│   └── index.blade.php           [MODIFY - render subscription warning badges]
├── superadmin/
│   ├── plans/
│   │   ├── index.blade.php       [NEW - superadmin plan list page]
│   │   └── edit.blade.php        [NEW - superadmin plan edit/create page]
│   └── company-detail.blade.php  [MODIFY - add form to customize modules and overrides]

tests/Feature/
├── PlanManagementTest.php        [NEW - tests superadmin CRUD actions on plans]
└── SubscriptionModuleAccessTest.php [NEW - tests module restrictions, overrides, and transaction limits]
```

---

## 3. Implementation Details

### A. Database Migrations

#### `2026_06_21_033500_create_plans_table.php`
```php
Schema::create('plans', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('code')->unique();
    $table->decimal('price', 8, 2);
    $table->integer('max_admins');
    $table->integer('max_sellers');
    $table->integer('max_monthly_transactions');
    $table->json('modules');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::table('companies', function (Blueprint $table) {
    $table->json('active_modules')->nullable()->after('subscription_status');
    $table->integer('max_monthly_transactions')->nullable()->after('active_modules');
    $table->integer('max_admins')->nullable()->after('max_monthly_transactions');
    $table->integer('max_sellers')->nullable()->after('max_admins');
});
```

#### `2026_06_21_033510_seed_default_plans.php`
Seed plans table with basic, standard, and premium details.

---

### B. Company Model Updates

Extend `Company.php` with:
- `getActiveModulesAttribute()`: returns custom `active_modules` or plan defaults.
- `getMaxAdminsAttribute()`, `getMaxSellersAttribute()`, `getMaxMonthlyTransactionsAttribute()`: return custom overrides or plan defaults.
- `hasModuleAccess(string $module): bool`: checks if `$module` exists in `active_modules`.

---

### C. Middleware `EnsureModuleAccess`

Maps path to modules:
- Check route prefixes and parameters.
- If company does not have access, redirect:
  `return redirect()->route('dashboard')->with('error', 'El módulo está inactivo o no está incluido en su plan.');`

---

### D. Transaction Limit Checking in Controllers

```php
$company = auth()->user()->company;
$limit = $company->max_monthly_transactions;

$salesCount = \App\Modules\Sale\Models\Sale::where('company_id', $company->id)
    ->whereMonth('created_at', now()->month)
    ->whereYear('created_at', now()->year)
    ->count();

$purchasesCount = \App\Modules\Purchase\Models\Purchase::where('company_id', $company->id)
    ->whereMonth('created_at', now()->month)
    ->whereYear('created_at', now()->year)
    ->count();

if (($salesCount + $purchasesCount) >= $limit) {
    throw \Illuminate\Validation\ValidationException::withMessages([
        'limit' => "Límite de transacciones mensuales alcanzado ({$limit}). Pruebe a mejorar su plan o contáctenos."
    ]);
}
```
