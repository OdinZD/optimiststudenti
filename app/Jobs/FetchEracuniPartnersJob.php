<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Eracuni\EracuniClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;

/**
 * Fetches the buyer partners from eRačuni and caches the snapshot for the linking screen.
 * Rate-limited to 1/s and non-overlapping like every eRačuni call.
 */
final class FetchEracuniPartnersJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public const CACHE_KEY = 'eracuni.partners';

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60, 300];
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('eracuni'))->releaseAfter(5),
            new RateLimited('eracuni'),
        ];
    }

    public function handle(EracuniClient $client): void
    {
        $partners = $client->partnerList(['partnerType' => 'buyer']);

        Cache::put(self::CACHE_KEY, [
            'partners' => $partners,
            'fetched_at' => now()->toIso8601String(),
        ], now()->addDay());
    }
}
