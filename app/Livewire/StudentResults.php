<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Discipline;
use App\Models\Competition;
use App\Models\CompetitionResult;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Manages one student's competition results. Embedded in the student edit drawer
 * (only when the student already exists). Writes directly to the DB.
 */
final class StudentResults extends Component
{
    public int $studentId;

    public ?int $editingId = null;

    public bool $submitted = false;

    public string $competitionId = '';

    public string $discipline = '';

    public string $category = '';

    public string $place = '';

    public string $wins = '';

    public string $losses = '';

    public string $bouts = '';

    public string $note = '';

    public function mount(int $studentId): void
    {
        $this->studentId = $studentId;
    }

    protected function rules(): array
    {
        return [
            'competitionId' => ['required', 'exists:competitions,id'],
            'discipline' => ['required', Rule::in(['kate', 'kumite'])],
            'category' => ['nullable', 'string', 'max:60'],
            'place' => ['nullable', 'integer', 'min:1', 'max:99'],
            'wins' => ['nullable', 'integer', 'min:0', 'max:127'],
            'losses' => ['nullable', 'integer', 'min:0', 'max:127'],
            'bouts' => ['nullable', 'integer', 'min:0', 'max:127'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'competitionId.required' => 'Odaberi natjecanje',
            'discipline.required' => 'Odaberi disciplinu',
        ];
    }

    public function updated(string $field): void
    {
        if ($this->submitted) {
            $this->validateOnly($field);
        }
    }

    public function editResult(int $id): void
    {
        $result = $this->ownedResult($id);
        $this->editingId = $result->id;
        $this->competitionId = (string) $result->competition_id;
        $this->discipline = $result->discipline->value;
        $this->category = $result->category ?? '';
        $this->place = $result->place !== null ? (string) $result->place : '';
        $this->wins = $result->wins !== null ? (string) $result->wins : '';
        $this->losses = $result->losses !== null ? (string) $result->losses : '';
        $this->bouts = $result->bouts !== null ? (string) $result->bouts : '';
        $this->note = $result->note ?? '';
        $this->submitted = false;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $this->submitted = true;
        $this->validate();

        $data = [
            'student_id' => $this->studentId,
            'competition_id' => (int) $this->competitionId,
            'discipline' => $this->discipline,
            'category' => $this->category ?: null,
            'place' => $this->place !== '' ? (int) $this->place : null,
            'wins' => $this->wins !== '' ? (int) $this->wins : null,
            'losses' => $this->losses !== '' ? (int) $this->losses : null,
            'bouts' => $this->bouts !== '' ? (int) $this->bouts : null,
            'note' => $this->note ?: null,
        ];

        if ($this->editingId !== null) {
            $this->ownedResult($this->editingId)->update($data);
        } else {
            CompetitionResult::create($data);
        }

        $this->resetForm();
        $this->dispatch('toast', message: 'Rezultat spremljen');
    }

    public function deleteResult(int $id): void
    {
        $this->ownedResult($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        $this->dispatch('toast', message: 'Rezultat obrisan');
    }

    private function ownedResult(int $id): CompetitionResult
    {
        return CompetitionResult::where('student_id', $this->studentId)->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'submitted', 'competitionId', 'discipline',
            'category', 'place', 'wins', 'losses', 'bouts', 'note',
        ]);
    }

    public function render()
    {
        return view('livewire.student-results', [
            'results' => CompetitionResult::with('competition')
                ->where('student_id', $this->studentId)
                ->get()
                ->sortByDesc(fn (CompetitionResult $r) => $r->competition->held_on)
                ->values(),
            'competitions' => Competition::orderByDesc('held_on')->get(),
            'disciplines' => Discipline::options(),
        ]);
    }
}
