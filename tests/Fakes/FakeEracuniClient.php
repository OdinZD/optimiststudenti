<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Services\Eracuni\EracuniClient;
use App\Services\Eracuni\EracuniException;

/** Recording test double for EracuniClient — never touches the network. */
final class FakeEracuniClient implements EracuniClient
{
    /** @var list<string> method names in call order */
    public array $calls = [];

    public bool $fail = false;

    /** @var list<array<string, mixed>> */
    public array $partners = [
        ['id' => '1001', 'companyName' => 'Ivana Horvat', 'eMail' => 'ivana.horvat@example.com', 'personalID' => null],
    ];

    public function partnerList(array $criteria = []): array
    {
        $this->record('PartnerList');

        return $this->partners;
    }

    public function partnerCreate(array $data): array
    {
        $this->record('PartnerCreate');

        return ['id' => 'new-1', 'companyName' => $data['companyName'] ?? '', 'eMail' => $data['eMail'] ?? null];
    }

    public function salesInvoiceCreate(array $data): array
    {
        $this->record('SalesInvoiceCreate');

        return ['documentID' => '60:1'];
    }

    public function salesInvoiceList(array $criteria = []): array
    {
        $this->record('SalesInvoiceList');

        return [];
    }

    public function salesInvoiceGet(string $documentId): array
    {
        $this->record('SalesInvoiceGet');

        return ['documentID' => $documentId];
    }

    public function salesInvoiceSendByEmail(string $documentId, array $to = []): array
    {
        $this->record('SalesInvoiceSendByEmail');

        return ['documentID' => $documentId, 'sent' => true];
    }

    private function record(string $method): void
    {
        $this->calls[] = $method;

        if ($this->fail) {
            throw new EracuniException('Simulirana greška.');
        }
    }
}
