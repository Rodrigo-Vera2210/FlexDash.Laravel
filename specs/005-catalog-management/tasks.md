# Tasks: Centralized Catalog Management (Spec 005)

## Phase 1: Routing & Base Controllers

- [x] T001 Define routing for settings and catalogs in `routes/settings.php` and load it inside `routes/web.php`.
- [x] T002 Implement `App\Modules\Settings\Controllers\CatalogController.php` with basic `index()` rendering.
- [x] T003 Append the **Configuración** settings link at the bottom of the sidebar in `resources/views/layouts/app.blade.php`.

---

## Phase 2: CRUD Operations & Status Toggling

- [x] T004 Implement model operations in `CatalogController.php` for adding/updating Taxes, Categories, and Payment Methods.
- [x] T005 Implement dynamic status toggling (`toggleStatus()`) via AJAX.
- [x] T006 Implement safe deletion (`destroy()`) that restricts deletion of referenced items.

---

## Phase 3: Views, Tabs & AJAX Context Modals

- [x] T007 Build the tabbed settings dashboard in `resources/views/settings/catalogs/index.blade.php`.
- [x] T008 Implement modals for Tax and Category creation/editing using AlpineJS.
- [x] T009 Update `resources/views/products/create.blade.php` and `resources/views/products/edit.blade.php` with inline quick-add (`+`) buttons, triggering AJAX creation and auto-selecting new items.

---

## Phase 4: Verification & Testing

- [x] T010 Create feature integration tests in `tests/Feature/CatalogManagementTest.php`.
- [x] T011 Run the full test suite (`php artisan test`) and verify 100% green tests.
- [x] T012 Run the asset build (`npm run build`) to ensure all client-side dependencies compile correctly.
