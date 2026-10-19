<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Discipline;
use App\Models\CompetitionRoster;
use App\Models\CompetitionRosterEntry;
use App\Models\Student;
use App\Models\TrainingGroup;
use App\Support\AgeCategory;
use App\Support\CompetitionRosterExport;
use App\Support\CroatianCollator;
use App\Support\Diacritics;
use App\Support\SpreadsheetWriter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Popis za natjecanje')]
final class CompetitionRosterShow extends Component
{
    private const SEARCH_LIMIT = 15;

    public CompetitionRoster $roster;

    public string $search = '';

    /** "g:<training group id>" or "a:<age category key>"; empty = nothing chosen. */
    public string $quick = '';

    public bool $onlyConfirmed = false;

    /** Raste kad se promjena discipline odbije, da se <select> ponovno stvori (vidi setDiscipline). */
    public int $rev = 0;

    // Edit-roster drawer
    public bool $open = false;

    public bool $submitted = false;

    public string $name = '';

    public string $eventDate = '';

    public bool $confirmingDelete = false;

    /** @var Collection<int, Student>|null per-request cache */
    private ?Collection $studentCache = null;

    public function mount(CompetitionRoster $roster): void
    {
        $this->roster = $roster;
    }

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

    // ---- roster header ---------------------------------------------------

    public function openEdit(): void
    {
        $this->name = $this->roster->name;
        $this->eventDate = $this->roster->event_date?->format('Y-m-d') ?? '';
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
        if ($this->submitted && in_array($field, ['name', 'eventDate'], true)) {
            $this->validateOnly($field);
        }
    }

    public function save(): void
    {
        $this->submitted = true;
        $this->validate();

        $this->roster->update([
            'name' => trim($this->name),
            'event_date' => $this->eventDate !== '' ? $this->eventDate : null,
        ]);

        $this->dispatch('toast', message: "Spremljeno: {$this->roster->name}");
        $this->close();
    }

    public function confirmDelete(): void
    {
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    public function delete()
    {
        $this->roster->delete(); // entries cascade; students are kept

        return $this->redirect(route('rosters'));
    }

    // ---- adding children -------------------------------------------------

    public function addStudent(int $studentId): void
    {
        $student = Student::findOrFail($studentId);

        if ($this->roster->entries()->where('student_id', $student->id)->exists()) {
            $this->dispatch('toast', message: "{$student->last_name} {$student->first_name} je već na popisu");

            return;
        }

        $this->roster->entries()->create(['student_id' => $student->id]);
        $this->dispatch('toast', message: "Dodano: {$student->last_name} {$student->first_name}");
    }

    public function addQuick(): void
    {
        $missing = $this->quickMissing();

        if ($missing->isEmpty()) {
            $this->dispatch('toast', message: 'Nema novih polaznika za dodavanje.');

            return;
        }

        foreach ($missing as $student) {
            $this->roster->entries()->create(['student_id' => $student->id]);
        }

        $this->dispatch('toast', message: "Dodano: {$missing->count()}");
    }

    /** Adds a second row for the same child with a discipline it is not yet registered for. */
    public function addDiscipline(int $entryId): void
    {
        $entry = $this->entry($entryId);
        $name = $this->displayName($entry);

        $used = $this->roster->entries()->where('student_id', $entry->student_id)->get()
            ->map(fn (CompetitionRosterEntry $e): ?Discipline => $e->discipline);

        // A lone undecided row: the coach must pick its discipline first, otherwise we'd create a second undecided row.
        if ($used->count() === 1 && $used->first() === null) {
            $this->dispatch('toast', message: "Prvo odaberi disciplinu za: {$name}");

            return;
        }

        $free = collect(Discipline::options())->first(fn (Discipline $d): bool => ! $used->contains($d));

        if ($free === null) {
            $this->dispatch('toast', message: "{$name} je već na popisu za sve discipline");

            return;
        }

        $this->roster->entries()->create(['student_id' => $entry->student_id, 'discipline' => $free->value]);
        $this->dispatch('toast', message: "Dodana disciplina {$free->label()}: {$name}");
    }

    // ---- inline edits ----------------------------------------------------

    public function setDiscipline(int $entryId, string $value): void
    {
        $entry = $this->entry($entryId);
        $value = trim($value);

        $validator = validator(['discipline' => $value === '' ? null : $value], [
            'discipline' => ['nullable', Rule::in(array_map(fn (Discipline $d): string => $d->value, Discipline::options()))],
        ], ['discipline.in' => 'Neispravna disciplina']);

        if ($validator->fails()) {
            $this->dispatch('toast', message: $validator->errors()->first('discipline'));

            return;
        }

        $discipline = $value === '' ? null : Discipline::from($value);

        $duplicate = $this->roster->entries()
            ->where('student_id', $entry->student_id)
            ->where('id', '!=', $entry->id)
            ->when(
                $discipline === null,
                fn ($q) => $q->whereNull('discipline'),
                fn ($q) => $q->where('discipline', $discipline->value),
            )
            ->exists();

        if ($duplicate) {
            // Morph ne vraća vrijednost <select>a koju je korisnik promijenio; novi $rev mijenja wire:key pa se select ponovno stvori.
            $this->rev++;
            $this->dispatch('toast', message: 'Dijete je već na popisu za tu disciplinu');

            return;
        }

        $entry->update(['discipline' => $discipline]);
    }

    public function setCategory(int $entryId, string $value): void
    {
        $this->setText($entryId, 'category', $value, 60);
    }

    public function setNote(int $entryId, string $value): void
    {
        $this->setText($entryId, 'note', $value, 255);
    }

    public function setConfirmed(int $entryId, bool $value): void
    {
        $this->entry($entryId)->update(['confirmed' => $value]);
    }

    public function remove(int $entryId): void
    {
        $entry = $this->entry($entryId);
        $name = $this->displayName($entry);
        $entry->delete();

        $this->dispatch('toast', message: "Uklonjeno: {$name}");
    }

    // ---- export ----------------------------------------------------------

    public function export()
    {
        $export = CompetitionRosterExport::build($this->entries(), $this->asOf(), $this->onlyConfirmed);

        if ($export['rows'] === []) {
            $this->dispatch('toast', message: 'Nema prijava za izvoz.');

            return null;
        }

        $path = storage_path('app/'.Str::uuid()->toString().'.xlsx');
        $banner = config('optimist.club_name').' — '.$this->roster->name.' · '.$this->asOf()->format('d.m.Y.');
        SpreadsheetWriter::write($path, $banner, $export);

        return response()->download($path, 'popis-'.Str::slug($this->roster->name).'-'.CarbonImmutable::now()->format('Y-m-d').'.xlsx')
            ->deleteFileAfterSend();
    }

    // ---- internals -------------------------------------------------------

    /** Scoped to this roster so a forged id from another roster 404s. */
    private function entry(int $id): CompetitionRosterEntry
    {
        return $this->roster->entries()->with('student')->findOrFail($id);
    }

    private function displayName(CompetitionRosterEntry $entry): string
    {
        $s = $entry->student ?? Student::withTrashed()->find($entry->student_id);

        return $s === null ? '' : "{$s->last_name} {$s->first_name}";
    }

    private function setText(int $entryId, string $field, string $value, int $max): void
    {
        $entry = $this->entry($entryId);
        $value = trim($value);

        $validator = validator([$field => $value], [$field => ['nullable', 'string', "max:{$max}"]], [
            "{$field}.max" => "Najviše {$max} znakova",
        ]);

        if ($validator->fails()) {
            $this->dispatch('toast', message: $validator->errors()->first($field));

            return;
        }

        $entry->update([$field => $value === '' ? null : $value]);
    }

    private function asOf(): CarbonImmutable
    {
        return $this->roster->event_date !== null
            ? CarbonImmutable::instance($this->roster->event_date)
            : CarbonImmutable::now();
    }

    /** @return Collection<int, CompetitionRosterEntry> entries whose student is not soft-deleted */
    private function entries(): Collection
    {
        return $this->roster->entries()->whereHas('student')->with('student')->get();
    }

    /** @return Collection<int, Student> */
    private function allStudents(): Collection
    {
        return $this->studentCache ??= Student::query()->with('trainingGroups')->get();
    }

    private function bySurname(Collection $students): Collection
    {
        return $students->sort(fn (Student $a, Student $b): int => CroatianCollator::compare($a->last_name, $b->last_name)
            ?: CroatianCollator::compare($a->first_name, $b->first_name))->values();
    }

    /** @return Collection<int, Student> students of the chosen group/age category who have no entry yet */
    private function quickMissing(): Collection
    {
        if ($this->quick === '') {
            return collect();
        }

        [$kind, $value] = array_pad(explode(':', $this->quick, 2), 2, '');
        $onRoster = $this->roster->entries()->pluck('student_id')->all();
        $today = $this->asOf(); // isti datum kao uzrast u tablici i Excelu

        return $this->bySurname($this->allStudents()->filter(function (Student $s) use ($kind, $value, $onRoster, $today): bool {
            if (in_array($s->id, $onRoster, true)) {
                return false;
            }

            return match ($kind) {
                'g' => $s->trainingGroups->contains('id', (int) $value),
                'a' => AgeCategory::for($s->birth_date, $today)?->key === $value,
                default => false,
            };
        }));
    }

    public function render()
    {
        $entries = $this->entries()->sort(fn (CompetitionRosterEntry $a, CompetitionRosterEntry $b): int => CompetitionRosterExport::compare($a, $b))->values();
        $onRoster = $entries->pluck('student_id')->unique()->all();

        $query = Diacritics::normalize(trim($this->search));
        $matches = $query === ''
            ? collect()
            : $this->bySurname($this->allStudents()->filter(fn (Student $s): bool => str_contains($s->search_haystack, $query)))
                ->take(self::SEARCH_LIMIT);

        $asOf = $this->asOf();

        return view('livewire.competition-roster-show', [
            'entries' => $entries,
            'onRoster' => $onRoster,
            'matches' => $matches,
            'quickMissing' => $this->quickMissing()->count(),
            'confirmedCount' => $entries->where('confirmed', true)->count(),
            'groups' => TrainingGroup::orderBy('name')->get(),
            'ageOptions' => AgeCategory::options(),
            'disciplines' => Discipline::options(),
            'ageLabels' => $entries->mapWithKeys(fn (CompetitionRosterEntry $e): array => [
                $e->id => AgeCategory::for($e->student->birth_date, $asOf)?->label ?? 'Bez datuma rođenja',
            ]),
        ]);
    }
}
