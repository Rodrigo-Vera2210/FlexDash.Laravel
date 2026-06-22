# Technical Implementation Plan: Multi-Tenant Unique Constraints

**Branch**: `012-multi-tenant-unique-constraints` | **Date**: 2026-06-22

**Spec**: `/specs/012-multi-tenant-unique-constraints/spec.md`

---

## 1. Summary of Technical Approach

1. **New Migration**: Create a migration that drops old global unique indexes on `products.code`, `taxes.code`, `categories.name`, `payment_methods.name`, `partners.document_number`, `sales.number`, and `purchases.number`, and replaces them with composite unique indexes scoped by `company_id`.
2. **Validation Rule Updates**: Refactor all controllers that use `'unique:table,column'` string syntax to use Laravel's `Rule::unique()` builder with a `->where('company_id', $companyId)` clause. This ensures that duplicate checks are scoped per tenant at the application level.
3. **Custom Validation Rule (optional helper)**: Create a reusable `UniqueForCompany` rule class (or a helper method in a base service) to standardize company-scoped uniqueness validation across all controllers, reducing boilerplate.
4. **No Model Changes**: The `BelongsToCompany` trait already auto-scopes queries and auto-fills `company_id` on creation. No model-level changes are needed.
5. **Test Suite**: Write feature tests that create records in two different companies with the same "unique" value and assert both succeed, while duplicates within the same company fail.

---

## 2. Project File Structure

```text
database/migrations/
└── 2026_06_22_000001_replace_global_unique_with_company_scoped_unique.php  [NEW]

app/Rules/
└── UniqueForCompany.php                                                     [NEW]

app/Modules/Product/Controllers/ProductController.php                        [MODIFY]
app/Modules/Partner/Controllers/PartnerController.php                        [MODIFY]
app/Modules/Settings/Controllers/CatalogController.php                       [MODIFY]
app/Modules/Sale/Controllers/SaleController.php                              [MODIFY - if number validation exists]
app/Modules/Purchase/Controllers/PurchaseController.php                      [MODIFY - if number validation exists]

tests/Feature/
└── MultiTenantUniqueConstraintsTest.php                                     [NEW]
```

---

## 3. Implementation Details

### A. Migration: Replace Global Unique with Company-Scoped Unique

```php
// 2026_06_22_000001_replace_global_unique_with_company_scoped_unique.php

public function up(): void
{
    // Products: drop unique on 'code', add unique on (company_id, code)
    Schema::table('products', function (Blueprint $table) {
        $table->dropUnique(['code']);
        $table->unique(['company_id', 'code'], 'products_company_code_unique');
    });

    // Taxes: drop unique on 'code', add unique on (company_id, code)
    Schema::table('taxes', function (Blueprint $table) {
        $table->dropUnique(['code']);
        $table->unique(['company_id', 'code'], 'taxes_company_code_unique');
    });

    // Categories: drop unique on 'name', add unique on (company_id, name)
    Schema::table('categories', function (Blueprint $table) {
        $table->dropUnique(['name']);
        $table->unique(['company_id', 'name'], 'categories_company_name_unique');
    });

    // Payment Methods: drop unique on 'name', add unique on (company_id, name)
    Schema::table('payment_methods', function (Blueprint $table) {
        $table->dropUnique(['name']);
        $table->unique(['company_id', 'name'], 'payment_methods_company_name_unique');
    });

    // Partners: drop unique on 'document_number', add unique on (company_id, document_number)
    Schema::table('partners', function (Blueprint $table) {
        $table->dropUnique(['document_number']);
        $table->unique(['company_id', 'document_number'], 'partners_company_document_unique');
    });

    // Sales: drop unique on 'number', add unique on (company_id, number)
    Schema::table('sales', function (Blueprint $table) {
        $table->dropUnique(['number']);
        $table->unique(['company_id', 'number'], 'sales_company_number_unique');
    });

    // Purchases: drop unique on 'number', add unique on (company_id, number)
    Schema::table('purchases', function (Blueprint $table) {
        $table->dropUnique(['number']);
        $table->unique(['company_id', 'number'], 'purchases_company_number_unique');
    });
}
```

### B. Custom Validation Rule: `UniqueForCompany`

```php
// app/Rules/UniqueForCompany.php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UniqueForCompany implements Rule
{
    public function __construct(
        protected string $table,
        protected string $column,
        protected ?int $ignoreId = null,
    ) {}

    public function passes($attribute, $value): bool
    {
        $companyId = auth()->user()?->company_id;

        $query = DB::table($this->table)
            ->where($this->column, $value)
            ->where('company_id', $companyId);

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        return !$query->exists();
    }

    public function message(): string
    {
        return 'El valor de :attribute ya está en uso.';
    }
}
```

### C. Controller Validation Updates

#### ProductController (store)
```php
// Before:
'code' => 'required|string|max:50|unique:products',

// After:
'code' => ['required', 'string', 'max:50', new UniqueForCompany('products', 'code')],
```

#### ProductController (update)
```php
'code' => ['required', 'string', 'max:50', new UniqueForCompany('products', 'code', $product->id)],
```

#### CatalogController (storeTax)
```php
// Before:
'code' => 'required|string|max:10|unique:taxes,code',

// After:
'code' => ['required', 'string', 'max:10', new UniqueForCompany('taxes', 'code')],
```

#### CatalogController (updateTax)
```php
'code' => ['required', 'string', 'max:10', new UniqueForCompany('taxes', 'code', $tax->id)],
```

#### CatalogController (storeCategory)
```php
// Before:
'name' => 'required|string|max:100|unique:categories,name',

// After:
'name' => ['required', 'string', 'max:100', new UniqueForCompany('categories', 'name')],
```

#### CatalogController (updateCategory)
```php
'name' => ['required', 'string', 'max:100', new UniqueForCompany('categories', 'name', $category->id)],
```

#### CatalogController (storePaymentMethod)
```php
// Before:
'name' => 'required|string|max:50|unique:payment_methods,name',

// After:
'name' => ['required', 'string', 'max:50', new UniqueForCompany('payment_methods', 'name')],
```

#### CatalogController (updatePaymentMethod)
```php
'name' => ['required', 'string', 'max:50', new UniqueForCompany('payment_methods', 'name', $paymentMethod->id)],
```

#### PartnerController (store)
```php
// Before:
'document_number' => 'required|string|max:20|unique:partners',

// After:
'document_number' => ['required', 'string', 'max:20', new UniqueForCompany('partners', 'document_number')],
```

#### PartnerController (update)
```php
'document_number' => ['required', 'string', 'max:20', new UniqueForCompany('partners', 'document_number', $partner->id)],
```

### D. Sale/Purchase Number Generation

The sale and purchase number generation logic (likely in `SaleService`/`PurchaseService` or the controller) already queries within the `BelongsToCompany` global scope. The migration handles the DB-level constraint. If explicit `unique:sales,number` validation exists in the sale/purchase creation flow, it must also be replaced with `UniqueForCompany`.

### E. Feature Tests (`MultiTenantUniqueConstraintsTest.php`)

```php
// Test structure:
// 1. Create two companies (A and B) with their owners
// 2. For each entity type (product, tax, category, payment method, partner):
//    a. Act as Company A owner → create record with value X → assert success
//    b. Act as Company B owner → create record with value X → assert success
//    c. Act as Company A owner → create another record with value X → assert validation error
//    d. Act as Company A owner → update existing record keeping value X → assert success (ignore-self)
```

---

## 4. Verification Plan

### Automated Tests
```bash
php artisan test --filter=MultiTenantUniqueConstraintsTest
```
Expected: All cross-company uniqueness tests pass; same-company duplicates are rejected.

### Manual Verification
1. Run `php artisan migrate` — confirm migration executes without errors.
2. Log in as Company A owner → create product with code `TEST-001` → success.
3. Log in as Company B owner → create product with code `TEST-001` → success.
4. Log in as Company A owner → create another product with code `TEST-001` → validation error.
5. Repeat for taxes (code), categories (name), payment methods (name), and partners (document_number).
6. Verify sale/purchase numbering doesn't collide between companies.
