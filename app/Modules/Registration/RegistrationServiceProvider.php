<?php

namespace App\Modules\Registration;

use Illuminate\Support\ServiceProvider;
use App\Modules\Registration\Contracts\RegistrationServiceInterface;
use App\Modules\Registration\Contracts\EmailVerificationServiceInterface;
use App\Modules\Registration\Services\RegistrationService;
use App\Modules\Registration\Services\EmailVerificationService;

class RegistrationServiceProvider extends ServiceProvider
{
    /**
     * Register module bindings into the service container.
     */
    public function register(): void
    {
        $this->app->bind(
            RegistrationServiceInterface::class,
            RegistrationService::class
        );

        $this->app->bind(
            EmailVerificationServiceInterface::class,
            EmailVerificationService::class
        );
    }

    /**
     * Bootstrap module services: views and routes.
     */
    public function boot(): void
    {
        // Load Blade views with the "registration" namespace
        $this->loadViewsFrom(
            base_path('resources/views/registration'),
            'registration'
        );

        // Load module routes if the file exists
        $registrationRoutes = base_path('routes/registration.php');

        if (file_exists($registrationRoutes)) {
            $this->loadRoutesFrom($registrationRoutes);
        }
    }
}
