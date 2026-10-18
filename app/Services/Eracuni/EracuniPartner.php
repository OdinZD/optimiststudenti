<?php

declare(strict_types=1);

namespace App\Services\Eracuni;

/** A partner record as returned by the eRačuni PartnerList API (normalized field names). */
final readonly class EracuniPartner
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email = null,
        public ?string $oib = null,
    ) {}

    /**
     * Builds from a raw PartnerList row, bridging eRačuni field names
     * (companyName / eMail / personalID) to our normalized shape.
     *
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (string) ($row['id'] ?? $row['partnerCode'] ?? ''),
            name: (string) ($row['name'] ?? $row['companyName'] ?? ''),
            email: isset($row['eMail']) ? (string) $row['eMail'] : (isset($row['email']) ? (string) $row['email'] : null),
            oib: isset($row['personalID']) ? (string) $row['personalID'] : (isset($row['oib']) ? (string) $row['oib'] : null),
        );
    }
}
