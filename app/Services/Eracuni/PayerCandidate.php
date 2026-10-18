<?php

declare(strict_types=1);

namespace App\Services\Eracuni;

/** A payer (parent) in Optimist that may need linking to an eRačuni partner. */
final readonly class PayerCandidate
{
    public function __construct(
        public string $email,
        public string $name,
        public ?string $oib = null, // OIB roditelja se zasad ne pohranjuje
    ) {}
}
