# Implementation Plan: PDF Reports & Cash Box Excel Export

**Branch**: `008-pdf-reports-and-cash-excel` | **Date**: 2026-06-17 | **Spec**: `/specs/008-pdf-reports-and-cash-excel/spec.md`

## Summary
This plan details the technical steps to generate premium PDF reports for sales and purchases matching a wave invoice design, integrate download options into lists and detail screens across modules, and export petty cash (caja chica) transactions to Excel (.xlsx) using PhpSpreadsheet.

---

## Technical Context
- **Language/Version**: PHP 8.2+ with Laravel 12
- **Primary Dependencies**: `barryvdh/laravel-dompdf` (PDF Generation), `phpoffice/phpspreadsheet` (Excel Export)
- **Styling**: Tailwind CSS for web views, and Dompdf-compatible inline CSS for PDF templates.
- **Database**: SQLite (Development)

---

## Constitution Check
- **TDD Requirement**: Failing tests for the controller actions and helper/service classes must be written before implementation.
- **Module Architecture**: All controllers and models are located in their respective module folders (`app/Modules/Sale`, `app/Modules/Purchase`, `app/Modules/CashBox`). The new routes will hook into existing controllers.
- **Clean Code**: Keep controller methods short (<30 lines). Business rules for Excel formatting and PDF compiling should be structured cleanly.

---

## Project Structure

We will modify or create the following files:
```text
app/Modules/Sale/
├── Controllers/
│   └── SaleController.php       [MODIFY - add downloadPdf()]
└── Tests/
    └── Feature/
        └── SalePdfTest.php       [NEW]

app/Modules/Purchase/
├── Controllers/
│   └── PurchaseController.php   [MODIFY - add downloadPdf()]
└── Tests/
    └── Feature/
        └── PurchasePdfTest.php   [NEW]

app/Modules/CashBox/
├── Controllers/
│   └── CashBoxController.php    [MODIFY - add exportExcel()]
└── Tests/
    └── Feature/
        └── CashBoxExcelTest.php  [NEW]

resources/views/
├── sales/
│   ├── pdf.blade.php            [NEW - Sales PDF invoice view]
│   ├── index.blade.php          [MODIFY - add download button in table]
│   └── show.blade.php           [MODIFY - add download button in header]
├── purchases/
│   ├── pdf.blade.php            [NEW - Purchase PDF view]
│   ├── index.blade.php          [MODIFY - add download button in table]
│   └── show.blade.php           [MODIFY - add download button in header]
├── dashboard/
│   └── index.blade.php          [MODIFY - add download link to recent sales table]
└── cashbox/
    └── index.blade.php          [MODIFY - add export Excel button in active header]

routes/
└── web.php                      [MODIFY - register new PDF and Excel export routes]
```

---

## Proposed Changes

### 1. Register New Dependencies

#### [MODIFY] [composer.json](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/composer.json)
Add the libraries:
- `"barryvdh/laravel-dompdf": "^3.0"`
- `"phpoffice/phpspreadsheet": "^2.1"`

---

### 2. Routes Registry

#### [MODIFY] [web.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/routes/web.php)
Add these new routes inside the `auth.jwt` middleware group:
```php
// Ventas PDF
Route::get('sales/{sale}/pdf', [SaleController::class, 'downloadPdf'])->name('sales.pdf');

// Compras PDF
Route::get('purchases/{purchase}/pdf', [PurchaseController::class, 'downloadPdf'])->name('purchases.pdf');

// Caja Chica Excel Export
Route::get('cashbox/{cashBox}/export', [CashBoxController::class, 'exportExcel'])->name('cashbox.export');
```

---

### 3. Controller Modifications

#### [MODIFY] [SaleController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Sale/Controllers/SaleController.php)
Implement `downloadPdf(Sale $sale)` method:
```php
public function downloadPdf(Sale $sale)
{
    $sale->load(['partner', 'details.product', 'user', 'tax']);
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.pdf', compact('sale'));
    
    return $pdf->download("factura-{$sale->series}-{$sale->number}.pdf");
}
```

#### [MODIFY] [PurchaseController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Purchase/Controllers/PurchaseController.php)
Implement `downloadPdf(Purchase $purchase)` method:
```php
public function downloadPdf(Purchase $purchase)
{
    $purchase->load(['partner', 'details.product', 'user', 'tax']);
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('purchases.pdf', compact('purchase'));
    
    return $pdf->download("compra-{$purchase->series}-{$purchase->number}.pdf");
}
```

#### [MODIFY] [CashBoxController.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/CashBox/Controllers/CashBoxController.php)
Implement `exportExcel(CashBox $cashBox)` method using PhpSpreadsheet:
- Instantiate `Spreadsheet` and get active sheet.
- Set title, headers, style cells (blue background for headers `#0054a6`, white bold text).
- Populate transactions: Date/Time, Concept, User, Type, Amount.
- Apply auto-fitting column widths and numeric formats for money columns.
- Export as `Xlsx` stream to the browser.

---

### 4. PDF Views

#### [NEW] [pdf.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/sales/pdf.blade.php)
Premium Sales Invoice PDF. Features:
- Wavy top header styled in Primary Blue `#0054a6` using inline CSS absolute elements/SVGs.
- Title "INVOICE" and details of sale.
- Detailed items table with alternating white/grey rows.
- Payment info, Terms, and signature block.
- Bottom wave styled footer.
- Designed with standard table-layout for Dompdf compatibility.

#### [NEW] [pdf.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/purchases/pdf.blade.php)
Symmetric purchase layout styled in Teal/Cyan `#00a2e8`.

---

### 5. Frontend View Updates

#### [MODIFY] [index.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/sales/index.blade.php)
- Add a download icon button linked to `route('sales.pdf', $sale)` in the row actions column.

#### [MODIFY] [show.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/sales/show.blade.php)
- Add a `Descargar PDF` button linked to `route('sales.pdf', $sale)` in the actions bar.

#### [MODIFY] [index.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/purchases/index.blade.php)
- Add a download icon button linked to `route('purchases.pdf', $purchase)` in the row actions column.

#### [MODIFY] [show.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/purchases/show.blade.php)
- Add a `Descargar PDF` button linked to `route('purchases.pdf', $purchase)` in the actions bar.

#### [MODIFY] [index.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/dashboard/index.blade.php)
- In the recent sales table, add a small PDF icon next to the invoice link or in a separate column.

#### [MODIFY] [index.blade.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/resources/views/cashbox/index.blade.php)
- Add an `Exportar Excel` button linked to `route('cashbox.export', $activeBox->id)` in the active cash box actions list.

---

### 6. Tests

#### [NEW] [SalePdfTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Sale/Tests/Feature/SalePdfTest.php)
- Assert that `GET /sales/{sale}/pdf` returns 200 and matches the expected PDF mime-type.
- Assert that unauthenticated users are redirected.

#### [NEW] [PurchasePdfTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/Purchase/Tests/Feature/PurchasePdfTest.php)
- Assert that `GET /purchases/{purchase}/pdf` returns 200 and downloads a PDF file.

#### [NEW] [CashBoxExcelTest.php](file:///c:/Users/RodrigoVera/Documents/Cursos/FlexDash/FlexDash.Laravel/app/Modules/CashBox/Tests/Feature/CashBoxExcelTest.php)
- Assert that `GET /cashbox/{cashBox}/export` returns 200 and downloads an `.xlsx` file.

---

## Verification Plan

### Automated Tests
- Run `composer install`
- Run `php artisan test`

### Manual Verification
- Access Sales and Purchases list and detail pages, verify download buttons.
- Verify downloaded PDF layout matches the attached premium wave invoice layout in design and color.
- Verify Cash Box Excel export opens without warnings and lists all transaction details.
