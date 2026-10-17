<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\FeePlanType;
use App\Models\FeePlan;
use App\Models\Student;
use App\Models\StudentFeePlan;
use App\Support\Auditer;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Manages one student's membership-tariff assignments (Članarina section of the
 * student drawer). Behind the payments flag (the section is only rendered when on).
 */
final class StudentFeePlans extends Component
{
    public int $studentId;

    public ?int $editingId = null;

    public bool $submitted = false;

    public string $feePlanId = '';

    public string $discountType = 'none'; // none | percent | fixed

    public string $discountValue = '';

    public string $payerName = '';

    public string $validFrom = '';

    public string $validUntil = '';

    public function mount(int $studentId): void
    {
        $this->authorizeAssign();
        $this->studentId = $studentId;
        $this->payerName = $this->defaultPayer();
    }

    private function authorizeAssign(): void
    {
        abort_unless(Gate::allows('assign-fee-plan'), 403);
    }

    protected function rules(): array
    {
        return [
            'feePlanId' => ['required', 'exists:fee_plans,id'],
            'discountType' => ['required', Rule::in(['none', 'percent', 'fixed'])],
            'discountValue' => ['nullable', 'string', 'max:20'],
            'payerName' => ['nullable', 'string', 'max:150'],
            'validFrom' => ['required', 'date'],
            'validUntil' => ['nullable', 'date', 'after_or_equal:validFrom'],
        ];
    }

    protected function messages(): array
    {
        return [
            'feePlanId.required' => 'Odaberi tarifu',
            'validFrom.required' => 'Obavezno polje',
            'validUntil.after_or_equal' => '„Vrijedi do” ne može biti prije „Vrijedi od”',
        ];
    }

    public function updated(string $field): void
    {
        if ($this->submitted) {
            $this->validateOnly($field);
        }
    }

    public function editAssignment(int $id): void
    {
        $this->authorizeAssign();
        $a = $this->owned($id);
        $this->editingId = $a->id;
        $this->feePlanId = (string) $a->fee_plan_id;
        $this->discountType = $a->discount_percent !== null ? 'percent' : ($a->discount_cents !== null ? 'fixed' : 'none');
        $this->discountValue = match ($this->discountType) {
            'percent' => (string) $a->discount_percent,
            'fixed' => number_format($a->discount_cents / 100, 2, ',', ''),
            default => '',
        };
        $this->payerName = $a->payer_name ?? '';
        $this->validFrom = $a->valid_from->format('Y-m-d');
        $this->validUntil = $a->valid_until?->format('Y-m-d') ?? '';
        $this->submitted = false;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $this->authorizeAssign();
        $this->submitted = true;
        $this->validate();

        $plan = FeePlan::findOrFail((int) $this->feePlanId);
        [$percent, $cents] = $this->resolveDiscount();

        // Pravilo: najviše jedna aktivna mjesečna tarifa u istom razdoblju.
        if ($plan->type === FeePlanType::Monthly
            && StudentFeePlan::monthlyOverlapExists($this->studentId, $this->validFrom, $this->validUntil ?: null, $this->editingId)) {
            $this->addError('feePlanId', 'Dijete već ima aktivnu mjesečnu tarifu u tom razdoblju.');

            return;
        }

        $data = [
            'student_id' => $this->studentId,
            'fee_plan_id' => $plan->id,
            'discount_percent' => $percent,
            'discount_cents' => $cents,
            'valid_from' => $this->validFrom,
            'valid_until' => $this->validUntil ?: null,
            'payer_name' => $this->payerName !== '' ? $this->payerName : ($this->defaultPayer() ?: null),
            'created_by' => Auth::id(),
        ];

        if ($this->editingId !== null) {
            $a = tap($this->owned($this->editingId))->update($data);
            Auditer::log('student_fee_plan.updated', $a, null, $data);
        } else {
            $a = StudentFeePlan::create($data);
            Auditer::log('student_fee_plan.assigned', $a, null, $data);
        }

        $this->resetForm();
        $this->dispatch('toast', message: 'Članarina spremljena');
    }

    public function deleteAssignment(int $id): void
    {
        $this->authorizeAssign();
        $a = $this->owned($id);
        Auditer::log('student_fee_plan.removed', $a, $a->only($a->getFillable()), null);
        $a->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        $this->dispatch('toast', message: 'Članarina uklonjena');
    }

    /** @return array{0: int|null, 1: int|null} [discount_percent, discount_cents] */
    private function resolveDiscount(): array
    {
        return match ($this->discountType) {
            'percent' => [max(0, min(100, (int) $this->discountValue)), null],
            'fixed' => [null, Money::parse($this->discountValue)],
            default => [null, null],
        };
    }

    private function defaultPayer(): string
    {
        return (string) (Student::find($this->studentId)?->parent_name ?? '');
    }

    private function owned(int $id): StudentFeePlan
    {
        return StudentFeePlan::where('student_id', $this->studentId)->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'submitted', 'feePlanId', 'discountType', 'discountValue', 'validFrom', 'validUntil']);
        $this->discountType = 'none';
        $this->payerName = $this->defaultPayer();
    }

    public function render()
    {
        return view('livewire.student-fee-plans', [
            'assignments' => StudentFeePlan::with('feePlan')
                ->where('student_id', $this->studentId)
                ->orderByDesc('valid_from')
                ->get(),
            'plans' => FeePlan::where('active', true)->orderBy('name')->get(),
        ]);
    }
}
