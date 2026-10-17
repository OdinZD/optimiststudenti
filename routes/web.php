<?php

declare(strict_types=1);

use App\Http\Controllers\DeclarationController;
use App\Livewire\Auth\Login;
use App\Livewire\ClubBillingSettingsForm;
use App\Livewire\CompetitionsList;
use App\Livewire\FeePlansList;
use App\Livewire\KataTeamsList;
use App\Livewire\StudentsList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/polaznici');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/polaznici', StudentsList::class)->name('polaznici');
    Route::get('/natjecanja', CompetitionsList::class)->name('natjecanja');
    Route::get('/kata-timovi', KataTeamsList::class)->name('kata-timovi');
    Route::get('/izjava/{student}', DeclarationController::class)->name('izjava');

    // Članarine (modul iza feature flaga). Tarife: samo administratori.
    if (config('features.payments')) {
        Route::get('/clanarine/tarife', FeePlansList::class)->middleware('can:manage-fee-plans')->name('fee-plans');
        Route::get('/clanarine/postavke', ClubBillingSettingsForm::class)->middleware('can:manage-fee-plans')->name('fee-settings');
    }

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
