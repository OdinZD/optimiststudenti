<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Jobs\CreateEracuniPartnerJob;
use App\Jobs\FetchEracuniPartnersJob;
use App\Livewire\EracuniPartnerLinks;
use App\Models\EracuniPartnerLink;
use App\Models\FeePlan;
use App\Models\Student;
use App\Models\StudentFeePlan;
use App\Models\User;
use App\Services\Eracuni\EracuniException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Fakes\FakeEracuniClient;
use Tests\TestCase;

final class EracuniPartnerLinksTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    /** A child paying by e-račun, with payer e-mail $email. */
    private function payer(string $email, string $name = 'Ivana Horvat'): void
    {
        $plan = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B', 'parent_name' => $name, 'parent_email' => $email]);
        StudentFeePlan::create([
            'student_id' => $student->id,
            'fee_plan_id' => $plan->id,
            'valid_from' => '2020-01-01',
            'nacin_placanja' => 'racun_email',
            'payer_email' => $email,
            'payer_name' => $name,
        ]);
    }

    private function cachePartners(): void
    {
        Cache::put(FetchEracuniPartnersJob::CACHE_KEY, [
            'partners' => [
                ['id' => '1001', 'companyName' => 'Ivana Horvat', 'eMail' => 'ivana.horvat@example.com'],
            ],
            'fetched_at' => now()->toIso8601String(),
        ], now()->addDay());
    }

    public function test_proposes_match_by_email_and_links_on_confirm(): void
    {
        $this->actingAs($this->admin());
        $this->payer('ivana.horvat@example.com');
        $this->cachePartners();

        Livewire::test(EracuniPartnerLinks::class)
            ->assertSee('po e-mailu') // prijedlog prikazan
            ->call('link', 'ivana.horvat@example.com', '1001');

        $this->assertDatabaseHas('eracuni_partner_links', [
            'payer_email' => 'ivana.horvat@example.com',
            'eracuni_partner_id' => '1001',
        ]);
    }

    public function test_unlink_removes_the_link(): void
    {
        $this->actingAs($this->admin());
        $this->payer('ivana.horvat@example.com');
        $link = EracuniPartnerLink::create([
            'payer_email' => 'ivana.horvat@example.com',
            'eracuni_partner_id' => '1001',
            'eracuni_partner_name' => 'Ivana Horvat',
        ]);

        Livewire::test(EracuniPartnerLinks::class)->call('unlink', $link->id);

        $this->assertDatabaseMissing('eracuni_partner_links', ['id' => $link->id]);
    }

    public function test_create_new_partner_dispatches_a_queued_job_never_automatic(): void
    {
        Queue::fake();
        $this->actingAs($this->admin());
        $this->payer('nema@example.com', 'Marko Marić');
        $this->cachePartners(); // snapshot bez para za ovaj e-mail

        Livewire::test(EracuniPartnerLinks::class)
            ->assertSee('nema para')
            ->call('createPartner', 'nema@example.com');

        Queue::assertPushed(CreateEracuniPartnerJob::class);
        $this->assertDatabaseCount('eracuni_partner_links', 0); // ništa dok job ne prođe
    }

    public function test_api_error_during_create_leaves_no_link(): void
    {
        $fake = new FakeEracuniClient;
        $fake->fail = true;

        try {
            (new CreateEracuniPartnerJob('x@y.z', 'X', ['companyName' => 'X']))->handle($fake);
            $this->fail('Očekivana EracuniException nije bačena.');
        } catch (EracuniException) {
            // očekivano
        }

        $this->assertDatabaseCount('eracuni_partner_links', 0);
    }

    public function test_screen_is_forbidden_for_a_trainer(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Trener]));

        Livewire::test(EracuniPartnerLinks::class)->assertForbidden();
    }
}
