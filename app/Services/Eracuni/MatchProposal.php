<?php

declare(strict_types=1);

namespace App\Services\Eracuni;

/** A proposed pairing of a payer to an eRačuni partner, with the reason it matched. */
final readonly class MatchProposal
{
    /** @param  'email'|'name'  $reason */
    public function __construct(
        public EracuniPartner $partner,
        public string $reason,
    ) {}

    public function reasonLabel(): string
    {
        return match ($this->reason) {
            'email' => 'po e-mailu',
            'name' => 'po imenu',
            default => $this->reason,
        };
    }
}
