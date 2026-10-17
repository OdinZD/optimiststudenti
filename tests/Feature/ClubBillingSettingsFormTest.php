<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\ClubBillingSettingsForm;
use App\Models\ClubBillingSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

final class ClubBillingSettingsFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    }

    public function test_saves_settings_and_stores_credentials_encrypted(): void
    {
        Livewire::test(ClubBillingSettingsForm::class)
            ->set('clubName', 'KK Optimist')
            ->set('invoiceLineTemplate', 'Članarina {mjesec} {godina} – {ime djeteta}')
            ->set('eracuniEnvironment', 'test')
            ->set('eracuniSecretKey', 'tajna123')
            ->set('billingMonths', '9,10,11')
            ->call('save')
            ->assertHasNoErrors();

        $settings = ClubBillingSettings::current();
        $this->assertSame('tajna123', $settings->eracuni_secret_key);        // dešifrira se
        $this->assertSame([9, 10, 11], $settings->billing_months);
        $this->assertSame('KK Optimist', $settings->club_name);

        // u bazi je šifrirano (sirova vrijednost ≠ plaintext)
        $raw = DB::table('club_billing_settings')->value('eracuni_secret_key');
        $this->assertNotSame('tajna123', $raw);
        $this->assertNotNull($raw);
    }

    public function test_credential_kept_when_left_blank_on_next_save(): void
    {
        Livewire::test(ClubBillingSettingsForm::class)->set('eracuniEnvironment', 'test')->set('eracuniSecretKey', 'tajna123')->call('save');

        // ponovno spremi bez ponovnog upisa secreta → ostaje
        Livewire::test(ClubBillingSettingsForm::class)->set('clubName', 'Novi naziv')->call('save')->assertHasNoErrors();

        $this->assertSame('tajna123', ClubBillingSettings::current()->eracuni_secret_key);
    }

    public function test_hub_fields_are_not_required(): void
    {
        Livewire::test(ClubBillingSettingsForm::class)
            ->set('eracuniEnvironment', 'test')
            ->call('save')
            ->assertHasNoErrors(); // iban/model/reference_rule/purpose_code prazni → bez greške
    }
}
