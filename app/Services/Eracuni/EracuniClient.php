<?php

declare(strict_types=1);

namespace App\Services\Eracuni;

/**
 * The eRačuni (Eurofaktura) web-service operations Optimist needs. Method names mirror
 * the documented JSON API (PartnerList, SalesInvoiceCreate, …). Two implementations:
 * MockEracuniClient (default, no real calls) and — later — a real HTTP client.
 */
interface EracuniClient
{
    /**
     * @param  array<string, mixed>  $criteria  e.g. ['partnerType' => 'buyer', 'eMail' => '…']
     * @return list<array<string, mixed>> raw partner rows
     */
    public function partnerList(array $criteria = []): array;

    /**
     * @param  array<string, mixed>  $data  PartnerCreate payload (companyName, eMail, personalID, …)
     * @return array<string, mixed> the created partner (must include an id)
     */
    public function partnerCreate(array $data): array;

    /**
     * @param  array<string, mixed>  $data  SalesInvoiceCreate payload
     * @return array<string, mixed> response including documentID
     */
    public function salesInvoiceCreate(array $data): array;

    /**
     * @param  array<string, mixed>  $criteria
     * @return list<array<string, mixed>>
     */
    public function salesInvoiceList(array $criteria = []): array;

    /** @return array<string, mixed> */
    public function salesInvoiceGet(string $documentId): array;

    /**
     * @param  list<string>  $to  recipient e-mail addresses (empty = partner default)
     * @return array<string, mixed>
     */
    public function salesInvoiceSendByEmail(string $documentId, array $to = []): array;
}
