<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Audit\Controllers\AuditController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Inventory\Controllers\InventoryController;
use App\Modules\Partner\Controllers\PartnerController;
use App\Modules\Product\Controllers\ProductController;
use App\Modules\Profile\Controllers\ProfileController;
use App\Modules\Purchase\Controllers\PurchaseController;
use App\Modules\Sale\Controllers\SaleController;

// Route::get('/', function () {
//     return view('welcome');
// });

require __DIR__.'/registration.php';


// Raíz → redirigir a dashboard o login
Route::get('/', fn() => redirect()->route('dashboard'));

// ── Rutas autenticadas ────────────────────────────────────────────────
Route::middleware(['auth.jwt', 'auth.admin_only'])->group(function () {

    // Subscription suspended warning
    Route::get('/subscription-suspended', function () {
        return view('subscription.suspended');
    })->name('subscription.suspended');
    Route::post('/subscription-suspended/payment', [\App\Modules\Registration\Controllers\SubscriptionBillingController::class, 'storePaymentSuspended'])->name('subscription.suspended.payment');
    Route::get('/receipts/{filename}', [\App\Modules\Registration\Controllers\SubscriptionBillingController::class, 'showReceipt'])->name('receipts.show');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil (Breeze)
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Partners (Clientes y Proveedores)
    Route::resource('partners', PartnerController::class)->middleware('auth.module:partners');

    // Productos y Kardex (Inventario)
    Route::middleware('auth.module:kardex')->group(function () {
        Route::resource('products', ProductController::class);
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/',          [InventoryController::class, 'index'])->name('index');
            Route::post('/adjust',   [InventoryController::class, 'adjust'])->name('adjust');
        });
    });

    // Ventas
    Route::middleware('auth.module:ventas')->group(function () {
        Route::resource('sales', SaleController::class)->except(['edit', 'update', 'destroy']);
        Route::post('sales/{sale}/approve',       [SaleController::class, 'approve'])->name('sales.approve');
        Route::post('sales/{sale}/cancel',        [SaleController::class, 'cancel'])->name('sales.cancel');
        Route::post('sales/{sale}/payments',      [SaleController::class, 'storePayment'])->name('sales.payments.store');
        Route::get('sales/{sale}/pdf',            [SaleController::class, 'downloadPdf'])->name('sales.pdf');
    });

    // Compras
    Route::middleware('auth.module:compras')->group(function () {
        Route::resource('purchases', PurchaseController::class)->except(['edit', 'update', 'destroy']);
        Route::post('purchases/{purchase}/approve',  [PurchaseController::class, 'approve'])->name('purchases.approve');
        Route::post('purchases/{purchase}/cancel',   [PurchaseController::class, 'cancel'])->name('purchases.cancel');
        Route::post('purchases/{purchase}/payments', [PurchaseController::class, 'storePayment'])->name('purchases.payments.store');
        Route::get('purchases/{purchase}/pdf',       [PurchaseController::class, 'downloadPdf'])->name('purchases.pdf');
    });

    // Auditoría
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

    // Caja Chica
    Route::prefix('cashbox')->name('cashbox.')->middleware('auth.module:caja_chica')->group(function () {
        Route::get('/', [App\Modules\CashBox\Controllers\CashBoxController::class, 'index'])->name('index');
        Route::post('/open', [App\Modules\CashBox\Controllers\CashBoxController::class, 'open'])->name('open');
        Route::post('/close', [App\Modules\CashBox\Controllers\CashBoxController::class, 'close'])->name('close');
        Route::post('/adjust', [App\Modules\CashBox\Controllers\CashBoxController::class, 'adjust'])->name('adjust');
        Route::get('/batch-payment', [App\Modules\CashBox\Controllers\CashBoxController::class, 'batchPaymentForm'])->name('batch-payment');
        Route::get('/pending-docs/{partner}', [App\Modules\CashBox\Controllers\CashBoxController::class, 'getPendingDocuments'])->name('pending-docs');
        Route::post('/batch-payment', [App\Modules\CashBox\Controllers\CashBoxController::class, 'storeBatchPayment'])->name('batch-payment.store');
        Route::get('/{cashBox}/export', [App\Modules\CashBox\Controllers\CashBoxController::class, 'exportExcel'])->name('export');
    });

    // Seller Management
    Route::middleware('auth.module:settings')->group(function () {
        Route::resource('sellers', \App\Modules\Seller\Controllers\SellerController::class)->only(['index', 'create', 'store']);
        Route::post('sellers/{seller}/toggle', [\App\Modules\Seller\Controllers\SellerController::class, 'toggleStatus'])->name('sellers.toggle');
    });
});

require __DIR__ . '/auth.php';
require __DIR__ . '/settings.php';

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── Rutas de Superadministrador ───────────────────────────────────────
Route::middleware(['auth.jwt', 'auth.superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/dashboard', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/payments', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'paymentsIndex'])->name('payments.index');
        Route::get('/companies/{company}', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'showCompany'])->name('companies.show');
        Route::post('/companies/{company}/approve', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'approveCompany'])->name('companies.approve');
        Route::post('/companies/{company}/reject', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'rejectCompany'])->name('companies.reject');
        Route::post('/companies/{company}/toggle-status', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'toggleStatus'])->name('companies.toggle-status');
        Route::post('/companies/{company}/change-plan', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'changePlan'])->name('companies.change-plan');
        Route::get('/audits', [\App\Modules\Audit\Controllers\AuditController::class, 'index'])->name('audits');
        
        // Plans Management
        Route::get('/plans', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'plansIndex'])->name('plans.index');
        Route::get('/plans/create', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'plansCreate'])->name('plans.create');
        Route::post('/plans', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'plansStore'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'plansEdit'])->name('plans.edit');
        Route::put('/plans/{plan}', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'plansUpdate'])->name('plans.update');
        Route::delete('/plans/{plan}', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'plansDestroy'])->name('plans.destroy');

        // Company Override Custom Limits
        Route::post('/companies/{company}/custom-limits', [\App\Modules\SuperAdmin\Controllers\SuperAdminController::class, 'updateCustomLimits'])->name('companies.custom-limits');
    });
