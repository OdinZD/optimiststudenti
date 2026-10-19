<?php

declare(strict_types=1);

use App\Http\Controllers\DeclarationController;
use App\Livewire\Auth\Login;
use App\Livewire\CompetitionRosterShow;
use App\Livewire\CompetitionRostersList;
use App\Livewire\CompetitionsList;
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
    Route::get('/popisi-natjecanja', CompetitionRostersList::class)->name('rosters');
    Route::get('/popisi-natjecanja/{roster}', CompetitionRosterShow::class)->name('rosters.show');
    Route::get('/kata-timovi', KataTeamsList::class)->name('kata-timovi');
    Route::get('/izjava/{student}', DeclarationController::class)->name('izjava');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
