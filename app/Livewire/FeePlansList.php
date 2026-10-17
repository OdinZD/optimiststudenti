<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\FeePlanType;
use App\Models\FeePlan;
use App\Support\Auditer;
use App\Support\Money;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Admin screen to manage membership tariffs (Članarine). Behind the payments flag
 * (routing) and the manage-fee-plans gate (admin only).
 */
#[Layout('components.layouts.app')]
#[Title('Članarine — tarife')]
final class FeePlansList extends Component
{
    public bool $open = false;

    public ?int $editId = null;

    public bool $submitted = false;

    public string $name = '';

    public string $amount = ''; // unos u EUR; sprema se u cente

    public string $type = 'monthly';

    public bool $active = true;

    public bool $confirmingDelete = false;

    public ?int $deleteId = null;

    public string $deleteName = '';

    public int $deleteAssignments = 0;

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-fee-plans'), 403);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'string', 'regex:/^[0-9.,\s€]+$/'],
            'type' => ['required', Rule::enum(FeePlanType::class)],
            'active' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Obavezno polje',
            'amount.required' => 'Obavezno polje',
            'amount.regex' => 'Unesi iznos u eurima, npr. 25,00',
            'type.required' => 'Odaberi vrstu',
        ];
    }

    public function updated(string $field): void
    {
        if ($this->submitted) {
            $this->validateOnly($field);
        }
    }

    public function openCreate(): void
    {
        $this->reset(['editId', 'name', 'amount', 'submitted']);
        $this->type = 'monthly';
        $this->active = true;
        $this->open = true;
    }

    public function openEdit(int $id): void
    {
        $plan = FeePlan::findOrFail($id);
        $this->editId = $plan->id;
        $this->name = $plan->name;
        $this->amount = number_format($plan->amount_cents / 100, 2, ',', '');
        $this->type = $plan->type->value;
        $this->active = $plan->active;
        $this->submitted = false;
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function save(): void
    {
        $this->submitted = true;
        $this->validate();

        $data = [
            'name' => $this->name,
            'amount_cents' => Money::parse($this->amount),
            'type' => $this->type,
            'active' => $this->active,
        ];

        $plan = $this->editId !== null
            ? tap(FeePlan::findOrFail($this->editId))->update($data)
            : FeePlan::create($data);

        Auditer::log($this->editId !== null ? 'fee_plan.updated' : 'fee_plan.created', $plan, null, $data);

        $this->dispatch('toast', message: "Spremljeno: {$plan->name}");
        $this->close();
    }

    public function confirmDelete(int $id): void
    {
        $plan = FeePlan::withCount('studentFeePlans')->findOrFail($id);
        $this->deleteId = $plan->id;
        $this->deleteName = $plan->name;
        $this->deleteAssignments = $plan->student_fee_plans_count;
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    public function delete(): void
    {
        if ($this->deleteId === null) {
            return;
        }

        $plan = FeePlan::withCount('studentFeePlans')->findOrFail($this->deleteId);

        if ($plan->student_fee_plans_count > 0) {
            $this->confirmingDelete = false;
            $this->dispatch('toast', message: 'Tarifa je dodijeljena djeci — deaktiviraj je umjesto brisanja.');

            return;
        }

        Auditer::log('fee_plan.deleted', $plan, $plan->only($plan->getFillable()), null);
        $plan->delete();

        $this->confirmingDelete = false;
        $this->dispatch('toast', message: 'Tarifa obrisana');
    }

    public function render()
    {
        return view('livewire.fee-plans-list', [
            'plans' => FeePlan::withCount('studentFeePlans')->orderBy('name')->get(),
            'types' => FeePlanType::options(),
        ]);
    }
}
