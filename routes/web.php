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
Route::middleware(['auth.jwt'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil (Breeze)
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Partners (Clientes y Proveedores)
    Route::resource('partners', PartnerController::class);

    // Productos
    Route::resource('products', ProductController::class);

    // Inventario (Kardex)
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/',          [InventoryController::class, 'index'])->name('index');
        Route::post('/adjust',   [InventoryController::class, 'adjust'])->name('adjust');
    });

    // Ventas
    Route::resource('sales', SaleController::class)->except(['edit', 'update', 'destroy']);
    Route::post('sales/{sale}/approve',       [SaleController::class, 'approve'])->name('sales.approve');
    Route::post('sales/{sale}/cancel',        [SaleController::class, 'cancel'])->name('sales.cancel');
    Route::post('sales/{sale}/payments',      [SaleController::class, 'storePayment'])->name('sales.payments.store');

    // Compras
    Route::resource('purchases', PurchaseController::class)->except(['edit', 'update', 'destroy']);
    Route::post('purchases/{purchase}/approve',  [PurchaseController::class, 'approve'])->name('purchases.approve');
    Route::post('purchases/{purchase}/cancel',   [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::post('purchases/{purchase}/payments', [PurchaseController::class, 'storePayment'])->name('purchases.payments.store');

    // Auditoría
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

    // Caja Chica
    Route::prefix('cashbox')->name('cashbox.')->group(function () {
        Route::get('/', [App\Modules\CashBox\Controllers\CashBoxController::class, 'index'])->name('index');
        Route::post('/open', [App\Modules\CashBox\Controllers\CashBoxController::class, 'open'])->name('open');
        Route::post('/close', [App\Modules\CashBox\Controllers\CashBoxController::class, 'close'])->name('close');
        Route::post('/adjust', [App\Modules\CashBox\Controllers\CashBoxController::class, 'adjust'])->name('adjust');
        Route::get('/batch-payment', [App\Modules\CashBox\Controllers\CashBoxController::class, 'batchPaymentForm'])->name('batch-payment');
        Route::get('/pending-docs/{partner}', [App\Modules\CashBox\Controllers\CashBoxController::class, 'getPendingDocuments'])->name('pending-docs');
        Route::post('/batch-payment', [App\Modules\CashBox\Controllers\CashBoxController::class, 'storeBatchPayment'])->name('batch-payment.store');
    });
});

require __DIR__ . '/auth.php';
require __DIR__ . '/settings.php';

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

