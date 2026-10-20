<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\CompetitionRoster;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Popisi za natjecanje')]
final class CompetitionRostersList extends Component
{
    public bool $open = false;

    public ?int $editId = null;

    public bool $submitted = false;

    public string $name = '';

    public string $eventDate = '';

    public bool $confirmingDelete = false;

    public ?int $deleteId = null;

    public string $deleteName = '';

    public int $deleteEntries = 0;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'eventDate' => ['nullable', 'date'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Obavezno polje',
            'name.max' => 'Najviše 120 znakova',
            'eventDate.date' => 'Neispravan datum',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editId', 'name', 'eventDate', 'submitted']);
        $this->resetValidation();
        $this->open = true;
    }

    public function openEdit(int $id): void
    {
        $roster = CompetitionRoster::findOrFail($id);
        $this->editId = $roster->id;
        $this->name = $roster->name;
        $this->eventDate = $roster->event_date?->format('Y-m-d') ?? '';
        $this->submitted = false;
        $this->resetValidation();
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

        $data = ['name' => trim($this->name), 'event_date' => $this->eventDate !== '' ? $this->eventDate : null];

        $roster = $this->editId !== null
            ? tap(CompetitionRoster::findOrFail($this->editId))->update($data)
            : CompetitionRoster::create($data);

        $this->dispatch('toast', message: "Spremljeno: {$roster->name}");
        $this->close();
    }

    public function confirmDelete(int $id): void
    {
        $roster = CompetitionRoster::findOrFail($id);
        $this->deleteId = $roster->id;
        $this->deleteName = $roster->name;
        $this->deleteEntries = $roster->entries()->whereHas('student')->count();
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    public function delete(): void
    {
        if ($this->deleteId !== null) {
            CompetitionRoster::findOrFail($this->deleteId)->delete(); // entries cascade
        }

        $this->confirmingDelete = false;
        $this->dispatch('toast', message: 'Popis obrisan');
    }

    public function render()
    {
        // Entries of soft-deleted students are neither shown nor counted.
        $rosters = CompetitionRoster::query()
            ->withCount([
                'entries as entries_count' => fn ($q) => $q->whereHas('student'),
                'entries as confirmed_count' => fn ($q) => $q->whereHas('student')->where('confirmed', true),
            ])
            ->orderByRaw('event_date IS NULL')
            ->orderByDesc('event_date')
            ->orderBy('name')
            ->get();

        return view('livewire.competition-rosters-list', ['rosters' => $rosters]);
    }
}
