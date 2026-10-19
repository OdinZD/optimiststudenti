<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\ClubBillingSettingsForm;
use App\Models\ClubBillingSettings;
use App\Models\EracuniApiLog;
use App\Models\User;
use App\Services\Eracuni\EracuniClient;
use App\Services\Eracuni\EracuniException;
use App\Services\Eracuni\HttpEracuniClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Fakes\FakeEracuniClient;
use Tests\TestCase;

final class HttpEracuniClientTest extends TestCase
{
    use RefreshDatabase;

    private function settings(string $endpoint = 'https://eracuni.example.test/WebServicesHR/API'): void
    {
        ClubBillingSettings::create([
            'currency' => 'EUR',
            'eracuni_username' => 'api-user',
            'eracuni_secret_key' => 'super-secret',
            'eracuni_token' => 'tok-123',
            'eracuni_endpoint' => $endpoint,
        ]);
    }

    public function test_sends_envelope_with_nested_parameters(): void
    {
        $this->settings();
        Http::fake(['*' => Http::response([['id' => '1', 'companyName' => 'A']])]);

        $rows = (new HttpEracuniClient)->partnerList(['partnerType' => 'buyer']);

        $this->assertCount(1, $rows);
        Http::assertSent(function (Request $r): bool {
            $body = json_decode($r->body(), true);

            return $r->url() === 'https://eracuni.example.test/WebServicesHR/API'
                && $body['method'] === 'PartnerList'
                && $body['username'] === 'api-user'
                && $body['secretKey'] === 'super-secret'
                && $body['token'] === 'tok-123'
                && $body['parameters']['partnerType'] === 'buyer';
        });
    }

    public function test_requests_uncompressed_response_and_reads_result_rows(): void
    {
        $this->settings();
        Http::fake(['*' => Http::response(['response' => ['status' => 'ok', 'result' => [['documentID' => '60:1']]]])]);

        $rows = (new HttpEracuniClient)->salesInvoiceList();

        $this->assertSame('60:1', $rows[0]['documentID']);
        Http::assertSent(fn (Request $r): bool => $r->header('Accept-Encoding') === ['identity']);
    }

    public function test_log_contains_no_credentials(): void
    {
        $this->settings();
        Http::fake(['*' => Http::response([])]);

        (new HttpEracuniClient)->partnerList(['partnerType' => 'buyer']);

        $log = EracuniApiLog::query()->firstOrFail();
        $this->assertTrue($log->ok);
        $this->assertSame('PartnerList', $log->method);
        $this->assertStringNotContainsString('super-secret', json_encode($log->request));
        $this->assertStringNotContainsString('tok-123', json_encode($log->request));
        $this->assertArrayNotHasKey('secretKey', $log->request);
    }

    public function test_http_error_is_logged_and_thrown(): void
    {
        $this->settings();
        Http::fake(['*' => Http::response('boom', 500)]);

        try {
            (new HttpEracuniClient)->partnerList();
            $this->fail('Očekivana EracuniException.');
        } catch (EracuniException) {
            // očekivano
        }

        $this->assertFalse(EracuniApiLog::query()->firstOrFail()->ok);
    }

    public function test_api_error_body_on_http_500_is_surfaced_and_logged(): void
    {
        $this->settings();
        Http::fake(['*' => Http::response(['response' => ['status' => 'error', 'description' => 'Invalid web services token supplied']], 500)]);

        try {
            (new HttpEracuniClient)->partnerList();
            $this->fail('Očekivana EracuniException.');
        } catch (EracuniException $e) {
            $this->assertStringContainsString('Invalid web services token supplied', $e->getMessage());
        }

        $log = EracuniApiLog::query()->firstOrFail();
        $this->assertFalse($log->ok);
        $this->assertStringContainsString('Invalid web services token supplied', (string) $log->error);
    }

    public function test_write_methods_are_blocked_unless_allowed(): void
    {
        $this->settings();
        Http::fake();
        $client = new HttpEracuniClient;

        foreach ([
            fn () => $client->salesInvoiceCreate([]),
            fn () => $client->salesInvoiceSendByEmail('60:1'),
            fn () => $client->partnerCreate([]),
        ] as $write) {
            try {
                $write();
                $this->fail('Pisanje je trebalo biti blokirano.');
            } catch (EracuniException) {
                // očekivano
            }
        }

        Http::assertNothingSent();
    }

    public function test_write_is_possible_when_flag_is_on(): void
    {
        $this->settings();
        config(['eracuni.allow_write' => true]);
        Http::fake(['*' => Http::response(['documentID' => '60:1'])]);

        $this->assertSame('60:1', (new HttpEracuniClient)->salesInvoiceCreate(['x' => 1])['documentID']);
    }

    public function test_rejects_non_https_endpoint_and_missing_credentials(): void
    {
        Http::fake();

        try {
            (new HttpEracuniClient)->partnerList(); // bez postavki
            $this->fail('Očekivana EracuniException.');
        } catch (EracuniException) {
        }

        $this->settings('http://eracuni.example.test/API');

        try {
            (new HttpEracuniClient)->partnerList();
            $this->fail('Očekivana EracuniException.');
        } catch (EracuniException) {
        }

        Http::assertNothingSent();
    }

    public function test_test_connection_reports_failure_without_crashing(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $fake = new FakeEracuniClient;
        $fake->fail = true;
        $this->app->instance(EracuniClient::class, $fake);

        Livewire::test(ClubBillingSettingsForm::class)
            ->call('testConnection')
            ->assertDispatched('toast');

        $this->assertSame(['PartnerList'], $fake->calls);
    }
}
