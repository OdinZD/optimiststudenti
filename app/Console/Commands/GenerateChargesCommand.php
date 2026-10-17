<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Auditer;
use App\Support\ChargeGenerator;
use Illuminate\Console\Command;

/**
 * Generates monthly membership charges for a month (default: current).
 * Scheduled on the 1st of each month (routes/console.php).
 */
final class GenerateChargesCommand extends Command
{
    protected $signature = 'clanarine:zaduzi {period? : Mjesec u formatu YYYY-MM (zadano: tekući mjesec)}';

    protected $description = 'Stvara mjesečna zaduženja članarine za zadani mjesec.';

    public function handle(): int
    {
        if (! config('features.payments')) {
            $this->warn('Modul članarina je isključen (FEATURE_PAYMENTS=false) — ništa nije napravljeno.');

            return self::SUCCESS;
        }

        $period = (string) ($this->argument('period') ?? now()->format('Y-m'));

        if (preg_match('/^\d{4}-\d{2}$/', $period) !== 1) {
            $this->error("Neispravan mjesec: {$period}. Očekivani format je YYYY-MM.");

            return self::FAILURE;
        }

        $result = ChargeGenerator::generate($period);

        if (! $result['billed']) {
            $this->info("Mjesec {$period} nije mjesec naplate — ništa nije stvoreno.");

            return self::SUCCESS;
        }

        Auditer::log('charges.generated', null, null, [
            'period' => $period,
            'created' => $result['created'],
            'skipped' => $result['skipped'],
        ]);

        $this->info("Zaduženja za {$period} — stvoreno: {$result['created']}, preskočeno: {$result['skipped']}.");

        return self::SUCCESS;
    }
}
