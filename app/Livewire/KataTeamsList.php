<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\KataTeam;
use App\Models\Student;
use App\Support\CroatianCollator;
use App\Support\KataTeamExport;
use App\Support\SpreadsheetWriter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kata timovi')]
final class KataTeamsList extends Component
{
    public bool $open = false;

    public ?int $editId = null;

    public bool $submitted = false;

    public string $name = '';

    public string $color = '#2D3E7B';

    public bool $confirmingDelete = false;

    public ?int $deleteId = null;

    public string $deleteName = '';

    public int $deleteMembers = 0;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Obavezno polje',
            'color.regex' => 'Neispravna boja',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editId', 'name', 'color', 'submitted']);
        $this->open = true;
    }

    public function openEdit(int $id): void
    {
        $team = KataTeam::findOrFail($id);
        $this->editId = $team->id;
        $this->name = $team->name;
        $this->color = $team->color;
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

        $data = ['name' => $this->name, 'color' => $this->color];

        $team = $this->editId !== null
            ? tap(KataTeam::findOrFail($this->editId))->update($data)
            : KataTeam::create($data);

        $this->dispatch('toast', message: "Spremljeno: {$team->name}");
        $this->close();
    }

    public function confirmDelete(int $id): void
    {
        $team = KataTeam::withCount('students')->findOrFail($id);
        $this->deleteId = $team->id;
        $this->deleteName = $team->name;
        $this->deleteMembers = $team->students_count;
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    public function delete(): void
    {
        if ($this->deleteId !== null) {
            KataTeam::findOrFail($this->deleteId)->delete(); // cascades the pivot; students are kept
        }

        $this->confirmingDelete = false;
        $this->dispatch('toast', message: 'Kata tim obrisan');
    }

    /** Export competition-ready teams (≥3 members) to an .xlsx, one coloured block per team. */
    public function export()
    {
        $export = KataTeamExport::build(KataTeam::with('students')->orderBy('name')->get(), CarbonImmutable::now());

        if ($export['sections'] === []) {
            $this->dispatch('toast', message: 'Nema timova s 3+ člana za izvoz.');

            return null;
        }

        $path = storage_path('app/'.Str::uuid()->toString().'.xlsx');
        $banner = config('optimist.club_name').' — Kata timovi · '.CarbonImmutable::now()->format('d.m.Y.');
        SpreadsheetWriter::writeSections($path, $banner, $export['headers'], $export['sections']);

        return response()->download($path, 'kata-timovi-'.CarbonImmutable::now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    public function render()
    {
        $teams = KataTeam::with('students')->orderBy('name')->get();

        foreach ($teams as $team) {
            $team->setRelation('students', $team->students->sort(
                fn (Student $a, Student $b): int => CroatianCollator::compare($a->last_name, $b->last_name)
                    ?: CroatianCollator::compare($a->first_name, $b->first_name)
            )->values());
        }

        return view('livewire.kata-teams-list', ['teams' => $teams]);
    }
}
