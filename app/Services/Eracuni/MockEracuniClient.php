<?php

declare(strict_types=1);

namespace App\Services\Eracuni;

use App\Models\ClubBillingSettings;

/**
 * Deterministic stand-in for the eRačuni API — makes NO network calls. Returns a fixed
 * sample so the whole partner-linking flow can be exercised locally. Every operation goes
 * through EracuniApiLogger exactly like the real client will. Set failNext() to simulate an error.
 */
final class MockEracuniClient implements EracuniClient
{
    private bool $failNext = false;

    /** Force the next call to throw (for testing the error path). */
    public function failNext(bool $fail = true): void
    {
        $this->failNext = $fail;
    }

    public function partnerList(array $criteria = []): array
    {
        return $this->call('PartnerList', $criteria, fn (): array => $this->samplePartners());
    }

    public function partnerCreate(array $data): array
    {
        return $this->call('PartnerCreate', $data, fn (): array => [
            'id' => 'mock-'.substr(md5(($data['eMail'] ?? '').($data['companyName'] ?? '').microtime()), 0, 10),
            'companyName' => $data['companyName'] ?? '',
            'eMail' => $data['eMail'] ?? null,
            'personalID' => $data['personalID'] ?? null,
        ]);
    }

    public function salesInvoiceCreate(array $data): array
    {
        return $this->call('SalesInvoiceCreate', $data, fn (): array => [
            'documentID' => '60:'.random_int(1_000_000, 9_999_999),
        ]);
    }

    public function salesInvoiceList(array $criteria = []): array
    {
        return $this->call('SalesInvoiceList', $criteria, fn (): array => []);
    }

    public function salesInvoiceGet(string $documentId): array
    {
        return $this->call('SalesInvoiceGet', ['documentID' => $documentId], fn (): array => [
            'documentID' => $documentId,
            'status' => 'issued',
        ]);
    }

    public function salesInvoiceSendByEmail(string $documentId, array $to = []): array
    {
        return $this->call('SalesInvoiceSendByEmail', ['documentID' => $documentId, 'to' => $to], fn (): array => [
            'documentID' => $documentId,
            'sent' => true,
        ]);
    }

    /**
     * Runs a mock op through the shared logger with a full (dummy) credential envelope,
     * so credential redaction is exercised exactly as it will be for the real client.
     *
     * @param  array<string, mixed>  $params
     * @param  callable(): mixed  $op
     */
    private function call(string $method, array $params, callable $op): mixed
    {
        $envelope = [...$this->credentials(), 'method' => $method, ...$params];

        return EracuniApiLogger::record($method, $envelope, function () use ($op) {
            if ($this->failNext) {
                $this->failNext = false;

                throw new EracuniException('Mock eRačuni greška (simulacija).');
            }

            return $op();
        });
    }

    /** @return array<string, string> */
    private function credentials(): array
    {
        $s = ClubBillingSettings::current();

        return [
            'username' => $s?->eracuni_username ?? 'mock-user',
            'secretKey' => $s?->eracuni_secret_key ?? 'mock-secret',
            'token' => $s?->eracuni_token ?? 'mock-token',
        ];
    }

    /** @return list<array<string, mixed>> */
    private function samplePartners(): array
    {
        return [
            ['id' => '1001', 'companyName' => 'Ivana Horvat', 'eMail' => 'ivana.horvat@example.com', 'personalID' => null],
            ['id' => '1002', 'companyName' => 'Marko Marić', 'eMail' => 'marko.maric@example.com', 'personalID' => null],
            ['id' => '1003', 'companyName' => 'Petra Novak', 'eMail' => 'petra.novak@example.com', 'personalID' => null],
        ];
    }
}
