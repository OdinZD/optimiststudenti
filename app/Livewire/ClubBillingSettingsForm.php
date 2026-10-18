<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\ClubBillingSettings;
use App\Services\Eracuni\EracuniClient;
use App\Services\Eracuni\EracuniException;
use App\Support\Auditer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Admin screen "Postavke naplate" (Članarine). eRačuni credentials are stored encrypted
 * and never shown in full — the inputs set a NEW value (empty = keep). HUB fields live
 * under a "Nekorišteno" section (kept for data, not used in the eRačuni direction).
 */
#[Layout('components.layouts.app')]
#[Title('Postavke naplate')]
final class ClubBillingSettingsForm extends Component
{
    public bool $submitted = false;

    public string $clubName = '';

    public string $address = '';

    public string $invoiceLineTemplate = '';

    public string $reminderDaysAfterDue = '';

    public string $dueDay = '';

    public string $billingMonths = '';

    public string $eracuniEnvironment = 'test';

    // HUB (nekorišteno)
    public string $iban = '';

    public string $model = '';

    public string $referenceRule = '';

    public string $purposeCode = '';

    // eRačuni vjerodajnice — unos NOVE vrijednosti (prazno = bez promjene)
    public string $eracuniUsername = '';

    public string $eracuniSecretKey = '';

    public string $eracuniToken = '';

    public string $eracuniEndpoint = '';

    // je li već postavljeno (za maskiranje; same vrijednosti se ne šalju u preglednik)
    public bool $usernameSet = false;

    public bool $secretKeySet = false;

    public bool $tokenSet = false;

    public bool $endpointSet = false;

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-fee-plans'), 403);

        $s = ClubBillingSettings::current();
        $this->clubName = $s?->club_name ?? '';
        $this->address = $s?->address ?? '';
        $this->invoiceLineTemplate = $s?->invoice_line_template ?? '';
        $this->reminderDaysAfterDue = $s?->reminder_days_after_due !== null ? (string) $s->reminder_days_after_due : '';
        $this->dueDay = $s?->due_day !== null ? (string) $s->due_day : '';
        $this->billingMonths = is_array($s?->billing_months) ? implode(',', $s->billing_months) : '';
        $this->eracuniEnvironment = $s?->eracuni_environment ?? 'test';
        $this->iban = $s?->iban ?? '';
        $this->model = $s?->model ?? '';
        $this->referenceRule = $s?->reference_rule ?? '';
        $this->purposeCode = $s?->purpose_code ?? '';
        $this->usernameSet = $s?->eracuni_username !== null;
        $this->secretKeySet = $s?->eracuni_secret_key !== null;
        $this->tokenSet = $s?->eracuni_token !== null;
        $this->endpointSet = $s?->eracuni_endpoint !== null;
    }

    public function updated(string $field): void
    {
        if ($this->submitted) {
            $this->validateOnly($field);
        }
    }

    protected function rules(): array
    {
        return [
            'clubName' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'invoiceLineTemplate' => ['nullable', 'string'], // bez ograničenja duljine
            'reminderDaysAfterDue' => ['nullable', 'integer', 'min:0', 'max:365'],
            'dueDay' => ['nullable', 'integer', 'min:1', 'max:31'],
            'billingMonths' => ['nullable', 'string', 'regex:/^\s*\d{1,2}(\s*,\s*\d{1,2})*\s*$/'],
            'eracuniEnvironment' => ['required', Rule::in(['test', 'production'])],
            'eracuniEndpoint' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:34'],
            'model' => ['nullable', 'string', 'max:7'],
            'referenceRule' => ['nullable', 'string', 'max:100'],
            'purposeCode' => ['nullable', 'string', 'max:4'],
        ];
    }

    protected function messages(): array
    {
        return [
            'billingMonths.regex' => 'Mjeseci kao brojevi odvojeni zarezom, npr. 9,10,11,12,1,2,3,4,5,6',
        ];
    }

    public function save(): void
    {
        abort_unless(Gate::allows('manage-fee-plans'), 403);
        $this->submitted = true;
        $this->validate();

        $settings = ClubBillingSettings::current() ?? new ClubBillingSettings;

        $data = [
            'club_name' => $this->clubName ?: null,
            'address' => $this->address ?: null,
            'invoice_line_template' => $this->invoiceLineTemplate ?: null,
            'reminder_days_after_due' => $this->reminderDaysAfterDue !== '' ? (int) $this->reminderDaysAfterDue : null,
            'due_day' => $this->dueDay !== '' ? (int) $this->dueDay : null,
            'billing_months' => $this->parseBillingMonths(),
            'eracuni_environment' => $this->eracuniEnvironment,
            'iban' => $this->iban ?: null,
            'model' => $this->model ?: null,
            'reference_rule' => $this->referenceRule ?: null,
            'purpose_code' => $this->purposeCode ?: null,
        ];

        // Vjerodajnice: ažuriraj samo ako je upisana nova vrijednost.
        foreach (['eracuniUsername' => 'eracuni_username', 'eracuniSecretKey' => 'eracuni_secret_key', 'eracuniToken' => 'eracuni_token', 'eracuniEndpoint' => 'eracuni_endpoint'] as $prop => $column) {
            if ($this->{$prop} !== '') {
                $data[$column] = $this->{$prop};
            }
        }

        $settings->fill($data)->save();

        // Ne logiraj vjerodajnice u audit.
        Auditer::log('billing_settings.updated', $settings, null, array_diff_key($data, array_flip(['eracuni_username', 'eracuni_secret_key', 'eracuni_token', 'eracuni_endpoint'])));

        $this->reset(['eracuniUsername', 'eracuniSecretKey', 'eracuniToken', 'eracuniEndpoint']);
        $fresh = $settings->fresh();
        $this->usernameSet = $fresh->eracuni_username !== null;
        $this->secretKeySet = $fresh->eracuni_secret_key !== null;
        $this->tokenSet = $fresh->eracuni_token !== null;
        $this->endpointSet = $fresh->eracuni_endpoint !== null;

        $this->dispatch('toast', message: 'Postavke spremljene');
    }

    /** Read-only connectivity check: one PartnerList that matches nothing (validates credentials + endpoint). */
    public function testConnection(EracuniClient $client): void
    {
        abort_unless(Gate::allows('manage-fee-plans'), 403);

        $driver = (string) config('eracuni.driver', 'mock');

        try {
            $client->partnerList(['partnerType' => 'buyer', 'searchKey' => '__optimist_ping__']);
        } catch (EracuniException $e) {
            $this->dispatch('toast', message: 'Veza nije uspjela: '.$e->getMessage());

            return;
        }

        $this->dispatch('toast', message: $driver === 'http' ? 'Veza s eRačunima radi.' : 'Veza OK (MOCK klijent — nije pravi poziv).');
    }

    /** @return list<int>|null */
    private function parseBillingMonths(): ?array
    {
        $trimmed = trim($this->billingMonths);

        if ($trimmed === '') {
            return null;
        }

        return array_values(array_filter(
            array_map('intval', explode(',', $trimmed)),
            fn (int $m): bool => $m >= 1 && $m <= 12,
        ));
    }

    public function render()
    {
        return view('livewire.club-billing-settings-form');
    }
}
