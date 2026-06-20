<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\EnsureEmailVerified;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Observers\PartnerObserver;
use App\Observers\PaymentObserver;
use App\Observers\ProductObserver;
use App\Observers\PurchaseObserver;
use App\Observers\SaleObserver;
use Illuminate\Support\Facades\Blade;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind services as singletons
        $this->app->singleton(\App\Services\InventoryService::class);
        $this->app->singleton(\App\Services\SaleService::class);
        $this->app->singleton(\App\Services\PurchaseService::class);
        $this->app->singleton(\App\Services\PaymentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register route middleware alias for email verification enforcement.
        if ($this->app->bound('router')) {
            $router = $this->app->make('router');
            if (method_exists($router, 'aliasMiddleware')) {
                $router->aliasMiddleware('ensure.email_verified', EnsureEmailVerified::class);
            }
        }

        Blade::component('layouts.guest','guest-layout');
    }
}
