<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\EracuniPartnerLink;
use App\Services\Eracuni\EracuniClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Creates a NEW partner in eRačuni for a payer (only ever dispatched after an admin
 * explicitly chooses "create new partner") and records the resulting link. Rate-limited
 * to 1/s and non-overlapping.
 */
final class CreateEracuniPartnerJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /**
     * @param  array<string, mixed>  $partnerData  PartnerCreate payload (companyName, eMail, …)
     */
    public function __construct(
        public string $payerEmail,
        public string $payerName,
        public array $partnerData,
    ) {}

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
        $partner = $client->partnerCreate($this->partnerData);

        EracuniPartnerLink::updateOrCreate(
            ['payer_email' => $this->payerEmail],
            [
                'payer_name' => $this->payerName,
                'eracuni_partner_id' => (string) ($partner['id'] ?? $partner['partnerCode'] ?? ''),
                'eracuni_partner_name' => (string) ($partner['companyName'] ?? $partner['name'] ?? $this->payerName),
                'confirmed_at' => now(),
            ],
        );
    }
}
