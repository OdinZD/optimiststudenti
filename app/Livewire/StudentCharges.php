<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Charge;
use App\Models\ClubBillingSettings;
use App\Models\FeePlan;
use App\Support\Auditer;
use App\Support\BillingReference;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * One student's charges (Članarina section): lists them and lets an admin/trainer add
 * a manual one-time charge (e.g. upisnina). Generated monthly charges come from the
 * clanarine:zaduzi command; these are only the manual additions.
 */
final class StudentCharges extends Component
{
    public int $studentId;

    public bool $submitted = false;

    public string $feePlanId = '';

    public string $period = '';

    public string $amount = '';

    public string $dueDate = '';

    public function mount(int $studentId): void
    {
        $this->authorizeAssign();
        $this->studentId = $studentId;
        $this->period = now()->format('Y-m');
    }

    protected function rules(): array
    {
        return [
            'feePlanId' => ['required', 'exists:fee_plans,id'],
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'amount' => ['required', 'string', 'regex:/^[0-9.,\s€]+$/'],
            'dueDate' => ['nullable', 'date'],
        ];
    }

    protected function messages(): array
    {
        return [
            'feePlanId.required' => 'Odaberi tarifu',
            'period.required' => 'Obavezno polje',
            'period.regex' => 'Mjesec u formatu YYYY-MM',
            'amount.required' => 'Obavezno polje',
            'amount.regex' => 'Unesi iznos u eurima, npr. 25,00',
        ];
    }

    public function updatedFeePlanId(string $value): void
    {
        $plan = FeePlan::find((int) $value);

        if ($plan !== null) {
            $this->amount = number_format($plan->amount_cents / 100, 2, ',', '');
        }
    }

    public function save(): void
    {
        $this->authorizeAssign();
        $this->submitted = true;
        $this->validate();

        $settings = ClubBillingSettings::current();

        try {
            $charge = Charge::create([
                'student_id' => $this->studentId,
                'fee_plan_id' => (int) $this->feePlanId,
                'student_fee_plan_id' => null,
                'period' => $this->period,
                'amount_cents' => Money::parse($this->amount),
                'due_date' => $this->dueDate ?: null,
                'reference' => BillingReference::for($this->studentId, $this->period, $settings?->model),
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->addError('feePlanId', 'Za tu tarifu i mjesec zaduženje već postoji.');

            return;
        }

        Auditer::log('charge.created_manually', $charge, null, $charge->only($charge->getFillable()));

        $this->reset(['submitted', 'feePlanId', 'amount', 'dueDate']);
        $this->period = now()->format('Y-m');
        $this->dispatch('toast', message: 'Zaduženje dodano');
    }

    private function authorizeAssign(): void
    {
        abort_unless(Gate::allows('assign-fee-plan'), 403);
    }

    public function render()
    {
        return view('livewire.student-charges', [
            'charges' => Charge::with('feePlan')
                ->where('student_id', $this->studentId)
                ->orderByDesc('period')
                ->get(),
            'plans' => FeePlan::where('active', true)->orderBy('name')->get(),
        ]);
    }
}
