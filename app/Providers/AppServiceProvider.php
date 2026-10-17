<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Članarine: evidentiranje uplata / otpis / storno smiju samo admin i blagajnik.
        Gate::define('manage-payments', static fn (User $user): bool => $user->canManagePayments());
    }
}
