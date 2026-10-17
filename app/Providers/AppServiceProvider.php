<?php

namespace App\Providers;

use App\Enums\Role;
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

        // Članarine: upravljanje tarifama (referentni podaci) smiju samo administratori.
        Gate::define('manage-fee-plans', static fn (User $user): bool => $user->isAdmin());

        // Članarine: dodjelu tarife djetetu smiju admin i treneri (ne samo-blagajnik).
        Gate::define('assign-fee-plan', static fn (User $user): bool => $user->isAdmin() || $user->role === Role::Trener);
    }
}
