<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use App\Services\Eracuni\EracuniClient;
use App\Services\Eracuni\MockEracuniClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // eRačuni klijent. Zasad uvijek mock (bez ijednog pravog poziva). Kad stigne API
        // pristup → dodati HttpEracuniClient i prebaciti ERACUNI_DRIVER=http (bez dirania ostatka).
        $this->app->singleton(EracuniClient::class, static fn (): EracuniClient => match (config('eracuni.driver', 'mock')) {
            'http' => throw new RuntimeException('eRačuni HTTP klijent još nije konfiguriran (vidi plan: Faza 4b).'),
            default => new MockEracuniClient,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Svi eRačuni queue pozivi: najviše 1 u sekundi.
        RateLimiter::for('eracuni', static fn (): Limit => Limit::perSecond(1));

        // Članarine: evidentiranje uplata / otpis / storno smiju samo admin i blagajnik.
        Gate::define('manage-payments', static fn (User $user): bool => $user->canManagePayments());

        // Članarine: upravljanje tarifama (referentni podaci) smiju samo administratori.
        Gate::define('manage-fee-plans', static fn (User $user): bool => $user->isAdmin());

        // Članarine: dodjelu tarife djetetu smiju admin i treneri (ne samo-blagajnik).
        Gate::define('assign-fee-plan', static fn (User $user): bool => $user->isAdmin() || $user->role === Role::Trener);
    }
}
