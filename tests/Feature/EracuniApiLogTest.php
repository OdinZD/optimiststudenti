<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EracuniApiLog;
use App\Services\Eracuni\EracuniException;
use App\Services\Eracuni\MockEracuniClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EracuniApiLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_is_logged_without_any_credentials(): void
    {
        (new MockEracuniClient)->partnerList(['partnerType' => 'buyer']);

        $log = EracuniApiLog::query()->firstOrFail();

        $this->assertSame('PartnerList', $log->method);
        $this->assertTrue($log->ok);

        // Poslovni parametar ostaje, vjerodajnice su maknute.
        $this->assertSame('buyer', $log->request['partnerType'] ?? null);
        foreach (['username', 'secretKey', 'secret_key', 'token', 'md5pass', 'password'] as $secret) {
            $this->assertArrayNotHasKey($secret, $log->request, "log sadrži tajni ključ: {$secret}");
        }
    }

    public function test_api_error_is_logged_and_rethrown(): void
    {
        $client = new MockEracuniClient;
        $client->failNext();

        try {
            $client->partnerList();
            $this->fail('Očekivana EracuniException nije bačena.');
        } catch (EracuniException) {
            // očekivano
        }

        $log = EracuniApiLog::query()->latest('id')->firstOrFail();
        $this->assertFalse($log->ok);
        $this->assertNotNull($log->error);
    }
}
