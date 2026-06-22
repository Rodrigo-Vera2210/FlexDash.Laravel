# Feature Specification: Multi-Tenant Unique Constraints

**Feature Branch**: `012-multi-tenant-unique-constraints`

**Created**: 2026-06-22

**Status**: Draft

---

## 1. Feature Description & Context

FlexDash is a **multi-tenant SaaS POS** where each company (tenant) operates with completely isolated data via the `BelongsToCompany` trait and `company_id` column. However, the current database schema still enforces **global unique constraints** on columns like `products.code`, `taxes.code`, `categories.name`, `payment_methods.name`, `partners.document_number`, and `sales.number` / `purchases.number`. This means:

- **Company A** cannot use product code `PROD-001` if **Company B** already uses it.
- A partner with RUC `20100047218` registered by Company A blocks Company B from registering the same partner.
- Sale/purchase document numbers collide across companies.

### Problem Statement

In a multi-tenant architecture, data isolation must be **complete**. Uniqueness constraints must be **scoped per company** — the combination `(company_id, <field>)` must be unique, not the field alone. This applies to all tenant-owned tables that currently have global `->unique()` constraints.

### Tables & Columns Affected

| Table | Column(s) with Global Unique | Required: Unique per Company |
|---|---|---|
| `products` | `code` | `(company_id, code)` |
| `taxes` | `code` | `(company_id, code)` |
| `categories` | `name` | `(company_id, name)` |
| `payment_methods` | `name` | `(company_id, name)` |
| `partners` | `document_number` | `(company_id, document_number)` |
| `sales` | `number` | `(company_id, number)` |
| `purchases` | `number` | `(company_id, number)` |

### Non-Tenant Tables (No Change Needed)

| Table | Column | Reason |
|---|---|---|
| `users` | `email` | Users are globally unique — a user email is their identity across the entire platform. |
| `plans` | `code` | Plans are a superadmin/global resource, not tenant-scoped. |

### Technical Strategy

1. **Database layer**: A new migration drops the old global unique indexes and replaces them with composite unique indexes `(company_id, <column>)`.
2. **Validation layer**: All Laravel validation rules using `'unique:table,column'` must be updated to use `Rule::unique('table', 'column')->where('company_id', $companyId)` to scope uniqueness checks per tenant.
3. **Model layer**: No changes needed — the `BelongsToCompany` trait already auto-scopes queries and auto-populates `company_id` on creation.

---

## 2. User Scenarios & Testing

### User Story 1: Two Companies Use the Same Product Code (Priority: P1)

As the owner of Company B, I want to create a product with code `PROD-001` even though Company A already has a product with that same code, because each company's catalog is independent.

**Independent Test**:
Log in as Company A owner, create product with code `PROD-001`. Log out. Log in as Company B owner, create product with code `PROD-001`. Both must succeed.

**Acceptance Scenarios**:
1. **Given** Company A has a product with code `PROD-001`, **When** Company B creates a product with code `PROD-001`, **Then** the product is created successfully without a uniqueness error.
2. **Given** Company A has a product with code `PROD-001`, **When** Company A tries to create another product with code `PROD-001`, **Then** a validation error is returned: "El código ya está en uso."
3. **Given** Company A edits product `PROD-002` and changes code to `PROD-001` (which already exists in Company A), **Then** a validation error is returned.
4. **Given** Company A edits product `PROD-001` and keeps the same code `PROD-001`, **Then** the update succeeds (ignore-self rule).

---

### User Story 2: Two Companies Register the Same Partner Document (Priority: P1)

As the owner of Company B, I want to register a partner with RUC `20100047218` even if Company A already has that partner, because partners belong to each company's own contact list.

**Independent Test**:
Create partner with document `20100047218` in Company A. Then create partner with document `20100047218` in Company B. Both must succeed.

**Acceptance Scenarios**:
1. **Given** Company A has partner with document `20100047218`, **When** Company B creates a partner with `20100047218`, **Then** the partner is created successfully.
2. **Given** Company A has partner with document `20100047218`, **When** Company A tries to create another partner with `20100047218`, **Then** a validation error is returned.

---

### User Story 3: Independent Tax and Category Catalogs per Company (Priority: P1)

As a company administrator, I want to create taxes and categories with names/codes that other companies may already use, because each company manages its own catalog independently.

**Independent Test**:
Create tax with code `IGV` in Company A. Create tax with code `IGV` in Company B. Both succeed. Company A tries to create another `IGV` — fails.

**Acceptance Scenarios**:
1. **Given** Company A has tax code `IGV`, **When** Company B creates tax code `IGV`, **Then** it succeeds.
2. **Given** Company A has category `Electrónica`, **When** Company B creates category `Electrónica`, **Then** it succeeds.
3. **Given** Company A has payment method `Efectivo`, **When** Company B creates payment method `Efectivo`, **Then** it succeeds.
4. **Given** Company A already has tax code `IGV`, **When** Company A tries to create another tax with code `IGV`, **Then** validation fails with "El código ya está en uso."

---

### User Story 4: Independent Sale/Purchase Numbering per Company (Priority: P1)

As Company B, I want my first sale to have number `F001-00000001` regardless of what numbers Company A has used.

**Acceptance Scenarios**:
1. **Given** Company A has sale number `F001-00000001`, **When** Company B creates a sale that generates number `F001-00000001`, **Then** it succeeds.
2. **Given** Company A has sale `F001-00000001`, **When** Company A's system generates a new sale, **Then** the next sequential number `F001-00000002` is assigned.

---

## 3. Constraints & Non-Goals

- **Constraint**: The `company_id` column already exists on all affected tables (added in migration `2026_06_20_225745`). This spec does NOT add `company_id` — it adjusts uniqueness semantics.
- **Constraint**: SQLite supports composite unique indexes via `CREATE UNIQUE INDEX`. The migration must be SQLite-compatible.
- **Constraint**: The `users.email` column remains **globally unique** — users are not tenant-scoped records.
- **Constraint**: The `plans.code` column remains **globally unique** — plans are superadmin-managed.
- **Non-Goal**: Implementing a global scope bypass for superadmin queries is out of scope (already handled by `BelongsToCompany` trait).
- **Non-Goal**: Data migration or deduplication of existing records — this feature assumes no collisions exist in the current single-company dataset.
