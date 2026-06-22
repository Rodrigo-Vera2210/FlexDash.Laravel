<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Settings\Controllers\CatalogController;

Route::middleware(['auth.jwt', 'auth.module:settings'])->group(function () {
    Route::get('/settings/catalogs', [CatalogController::class, 'index'])->name('settings.catalogs.index');
    
    // Subscription Settings
    Route::get('/settings/subscription', [\App\Modules\Registration\Controllers\SubscriptionBillingController::class, 'index'])->name('settings.subscription.index');
    Route::post('/settings/subscription/payment', [\App\Modules\Registration\Controllers\SubscriptionBillingController::class, 'storePayment'])->name('settings.subscription.payment.store');
    
    // Taxes CRUD
    Route::post('/settings/catalogs/taxes', [CatalogController::class, 'storeTax'])->name('settings.catalogs.taxes.store');
    Route::put('/settings/catalogs/taxes/{tax}', [CatalogController::class, 'updateTax'])->name('settings.catalogs.taxes.update');
    
    // Categories CRUD
    Route::post('/settings/catalogs/categories', [CatalogController::class, 'storeCategory'])->name('settings.catalogs.categories.store');
    Route::put('/settings/catalogs/categories/{category}', [CatalogController::class, 'updateCategory'])->name('settings.catalogs.categories.update');

    // Payment Methods CRUD
    Route::post('/settings/catalogs/payment-methods', [CatalogController::class, 'storePaymentMethod'])->name('settings.catalogs.payment-methods.store');
    Route::put('/settings/catalogs/payment-methods/{paymentMethod}', [CatalogController::class, 'updatePaymentMethod'])->name('settings.catalogs.payment-methods.update');
    
    // Generic Status Toggle & Deletion
    Route::post('/settings/catalogs/toggle-status', [CatalogController::class, 'toggleStatus'])->name('settings.catalogs.toggle-status');
    Route::delete('/settings/catalogs/{type}/{id}', [CatalogController::class, 'destroy'])->name('settings.catalogs.destroy');
});
