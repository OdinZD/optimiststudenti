<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Članarine: mjesečna zaduženja 1. u mjesecu (komanda sama preskače ako je modul isključen).
Schedule::command('clanarine:zaduzi')->monthlyOn(1, '06:00');
