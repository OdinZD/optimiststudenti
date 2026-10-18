<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Jobs\CreateEracuniPartnerJob;
use App\Jobs\FetchEracuniPartnersJob;
use App\Livewire\EracuniPartnerLinks;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Fakes\FakeEracuniClient;
use Tests\TestCase;

final class EracuniQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_partners_queues_the_fetch_job(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));

        Livewire::test(EracuniPartnerLinks::class)->call('refreshPartners');

        Queue::assertPushed(FetchEracuniPartnersJob::class);
    }

    public function test_jobs_are_rate_limited_and_non_overlapping(): void
    {
        foreach ([new FetchEracuniPartnersJob, new CreateEracuniPartnerJob('a@b.c', 'A', [])] as $job) {
            $middleware = collect($job->middleware());

            $this->assertTrue($middleware->contains(fn ($m): bool => $m instanceof RateLimited), $job::class.' nema RateLimited');
            $this->assertTrue($middleware->contains(fn ($m): bool => $m instanceof WithoutOverlapping), $job::class.' nema WithoutOverlapping');
        }
    }

    public function test_fetch_job_calls_the_client_once_and_caches_the_snapshot(): void
    {
        $fake = new FakeEracuniClient;

        (new FetchEracuniPartnersJob)->handle($fake);

        $this->assertSame(['PartnerList'], $fake->calls); // točno jedan poziv
        $this->assertTrue(Cache::has(FetchEracuniPartnersJob::CACHE_KEY));
    }
}
