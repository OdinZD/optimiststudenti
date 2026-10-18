<?php

declare(strict_types=1);

namespace App\Services\Eracuni;

use App\Models\ClubBillingSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Real eRačuni (Eurofaktura) JSON client. Envelope per their docs: username, secretKey,
 * token and method at the top level, method arguments nested under "parameters".
 *
 * Read-only by default: invoice creation fiscalizes automatically and irreversibly and
 * there is no test organization, so every write method is blocked unless
 * config('eracuni.allow_write') is on. All traffic goes through EracuniApiLogger
 * (credentials redacted, response truncated).
 */
final class HttpEracuniClient implements EracuniClient
{
    public function partnerList(array $criteria = []): array
    {
        return $this->rows($this->call('PartnerList', $criteria));
    }

    public function partnerCreate(array $data): array
    {
        $this->assertWriteAllowed('PartnerCreate');

        return $this->call('PartnerCreate', $data);
    }

    public function salesInvoiceCreate(array $data): array
    {
        $this->assertWriteAllowed('SalesInvoiceCreate');

        return $this->call('SalesInvoiceCreate', $data);
    }

    public function salesInvoiceList(array $criteria = []): array
    {
        return $this->rows($this->call('SalesInvoiceList', $criteria));
    }

    public function salesInvoiceGet(string $documentId): array
    {
        return $this->call('SalesInvoiceGet', ['documentID' => $documentId]);
    }

    public function salesInvoiceSendByEmail(string $documentId, array $to = []): array
    {
        $this->assertWriteAllowed('SalesInvoiceSendByEmail');

        return $this->call('SalesInvoiceSendByEmail', ['documentID' => $documentId, 'to' => $to]);
    }

    private function assertWriteAllowed(string $method): void
    {
        if (! config('eracuni.allow_write', false)) {
            throw new EracuniException("{$method} je onemogućen: pisanje u eRačune nije uključeno (ERACUNI_ALLOW_WRITE).");
        }
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function call(string $method, array $parameters): array
    {
        $settings = ClubBillingSettings::current();

        if ($settings === null || ! $settings->hasEracuniCredentials() || $settings->eracuni_token === null || $settings->eracuni_endpoint === null) {
            throw new EracuniException('eRačuni vjerodajnice nisu potpune (korisničko ime, secret key, token i endpoint).');
        }

        $endpoint = $settings->eracuni_endpoint;

        // Vjerodajnice putuju u tijelu zahtjeva — nikad preko nešifriranog kanala.
        if (! str_starts_with(strtolower($endpoint), 'https://')) {
            throw new EracuniException('eRačuni endpoint mora počinjati s https://.');
        }

        $envelope = [
            'username' => $settings->eracuni_username,
            'secretKey' => $settings->eracuni_secret_key,
            'token' => $settings->eracuni_token,
            'method' => $method,
            'parameters' => (object) $parameters,
        ];

        return EracuniApiLogger::record($method, $envelope, function () use ($endpoint, $envelope): array {
            try {
                $response = Http::timeout((int) config('eracuni.timeout', 20))
                    ->acceptJson()
                    ->asJson()
                    ->post($endpoint, $envelope);
            } catch (ConnectionException $e) {
                throw new EracuniException('eRačuni nije dostupan: '.$e->getMessage(), 0, $e);
            }

            if (! $response->successful()) {
                throw new EracuniException('eRačuni HTTP '.$response->status().'.');
            }

            $body = $response->json();

            if (! is_array($body)) {
                throw new EracuniException('eRačuni je vratio odgovor koji nije JSON.');
            }

            // Oblik poruke o grešci provjeriti u API konzoli; dok nije potvrđen hvata se uobičajeni ključ.
            if (! empty($body['error']) || ! empty($body['errorMessage'])) {
                throw new EracuniException('eRačuni greška: '.(string) ($body['errorMessage'] ?? $body['error']));
            }

            return $body;
        });
    }

    /**
     * Pulls the row list out of a list-style response. The exact response shape is
     * confirmed against the API console; the raw body is kept in eracuni_api_log.
     *
     * @param  array<string, mixed>  $body
     * @return list<array<string, mixed>>
     */
    private function rows(array $body): array
    {
        if (array_is_list($body)) {
            return $body;
        }

        foreach (['result', 'partners', 'invoices', 'items'] as $key) {
            if (isset($body[$key]) && is_array($body[$key])) {
                return array_values($body[$key]);
            }
        }

        return [];
    }
}
