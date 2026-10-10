<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Competition;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Natjecanja')]
final class CompetitionsList extends Component
{
    public bool $open = false;

    public ?int $editId = null;

    public bool $submitted = false;

    public string $name = '';

    public string $heldOn = '';

    public string $city = '';

    public bool $confirmingDelete = false;

    public ?int $deleteId = null;

    public string $deleteName = '';

    public int $deleteResults = 0;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'heldOn' => ['required', 'date'],
            'city' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Obavezno polje',
            'heldOn.required' => 'Obavezno polje',
            'heldOn.date' => 'Neispravan datum',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editId', 'name', 'heldOn', 'city', 'submitted']);
        $this->open = true;
    }

    public function openEdit(int $id): void
    {
        $competition = Competition::findOrFail($id);
        $this->editId = $competition->id;
        $this->name = $competition->name;
        $this->heldOn = $competition->held_on->format('Y-m-d');
        $this->city = $competition->city ?? '';
        $this->submitted = false;
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function updated(string $field): void
    {
        if ($this->submitted) {
            $this->validateOnly($field);
        }
    }

    public function save(): void
    {
        $this->submitted = true;
        $this->validate();

        $data = ['name' => $this->name, 'held_on' => $this->heldOn, 'city' => $this->city ?: null];

        $competition = $this->editId !== null
            ? tap(Competition::findOrFail($this->editId))->update($data)
            : Competition::create($data);

        $this->dispatch('toast', message: "Spremljeno: {$competition->name}");
        $this->close();
    }

    public function confirmDelete(int $id): void
    {
        $competition = Competition::withCount('results')->findOrFail($id);
        $this->deleteId = $competition->id;
        $this->deleteName = $competition->name;
        $this->deleteResults = $competition->results_count;
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    public function delete(): void
    {
        if ($this->deleteId !== null) {
            Competition::findOrFail($this->deleteId)->delete(); // cascades results
        }

        $this->confirmingDelete = false;
        $this->dispatch('toast', message: 'Natjecanje obrisano');
    }

    public function render()
    {
        return view('livewire.competitions-list', [
            'competitions' => Competition::withCount('results')->orderByDesc('held_on')->get(),
        ]);
    }
}
