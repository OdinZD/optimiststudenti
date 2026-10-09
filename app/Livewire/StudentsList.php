<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Belt;
use App\Enums\NextGradeStatus;
use App\Models\Location;
use App\Models\Student;
use App\Models\TrainingGroup;
use App\Support\AgeCategory;
use App\Support\CompetitionExport;
use App\Support\CroatianCollator;
use App\Support\Diacritics;
use App\Support\MedicalStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

#[Layout('components.layouts.app')]
#[Title('Polaznici')]
final class StudentsList extends Component
{
    /** Pseudo age-filter value for students without a birth date. */
    public const NO_BIRTH_DATE = 'bez-datuma';

    /** Age tiers in display order (SPEC §4.1). */
    private const AGE_ORDER = ['ispod', 'u08', 'u10', 'u12', 'u14', 'u16', 'u18', 'u21', 'sen'];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'uzrast', except: '')]
    public string $ageKey = '';

    #[Url(as: 'lokacija', except: '')]
    public string $location = '';

    #[Url(as: 'grupa', except: '')]
    public string $group = '';

    #[Url(as: 'pojas', except: '')]
    public string $belt = '';

    #[Url(as: 'istekao', except: false)]
    public bool $expiredOnly = false;

    #[Url(as: 'upisati', except: false)]
    public bool $toRegisterOnly = false;

    private ?Collection $cache = null;

    public function setAge(string $key): void
    {
        $this->ageKey = $this->ageKey === $key ? '' : $key;
    }

    /** A save or delete in the drawer re-renders the list with fresh data. */
    #[On('students-changed')]
    public function refreshList(): void
    {
        $this->cache = null;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'ageKey', 'location', 'group', 'belt', 'expiredOnly', 'toRegisterOnly']);
    }

    /** Export the currently filtered students to an .xlsx roster (no OIB, no medical). */
    public function export()
    {
        $export = CompetitionExport::build($this->rows(), $this->today());

        $path = storage_path('app/'.Str::uuid()->toString().'.xlsx');

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($export['headers']));
        foreach ($export['rows'] as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return response()->download($path, 'polaznici-'.$this->today()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->ageKey !== ''
            || $this->location !== ''
            || $this->group !== ''
            || $this->belt !== ''
            || $this->expiredOnly
            || $this->toRegisterOnly;
    }

    /** All non-deleted students, loaded once per request. */
    private function allStudents(): Collection
    {
        return $this->cache ??= Student::with(['location', 'trainingGroup'])->get();
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }

    /**
     * Whether a student passes the active filters. $skip lets the age-chip
     * counts exclude the age filter itself (SPEC §4.5).
     */
    private function matches(Student $student, ?string $skip = null): bool
    {
        if ($skip !== 'age' && $this->ageKey !== '') {
            if ($this->ageKey === self::NO_BIRTH_DATE) {
                if ($student->birth_date !== null) {
                    return false;
                }
            } elseif (AgeCategory::for($student->birth_date, $this->today())?->key !== $this->ageKey) {
                return false;
            }
        }

        if ($this->location !== '' && $student->location?->slug !== $this->location) {
            return false;
        }

        if ($this->group !== '' && $student->trainingGroup?->slug !== $this->group) {
            return false;
        }

        if ($this->belt !== '' && (string) ($student->belt_kyu?->value ?? '') !== $this->belt) {
            return false;
        }

        if ($this->expiredOnly
            && MedicalStatus::for($student->medical_valid_until, $student->has_no_medical, $this->today()) !== MedicalStatus::Expired) {
            return false;
        }

        if ($this->toRegisterOnly && $student->next_grade_status !== NextGradeStatus::ToRegister) {
            return false;
        }

        $query = Diacritics::normalize(trim($this->search));

        if ($query !== '' && ! str_contains($student->search_haystack, $query)) {
            return false;
        }

        return true;
    }

    /** Filtered students, sorted by the Croatian alphabet (SPEC §4.4). */
    private function rows(): Collection
    {
        return $this->allStudents()
            ->filter(fn (Student $s): bool => $this->matches($s))
            ->sort(fn (Student $a, Student $b): int => CroatianCollator::compare($a->last_name, $b->last_name)
                ?: CroatianCollator::compare($a->first_name, $b->first_name))
            ->values();
    }

    /** @return list<array{key: string, label: string, count: int}> */
    private function ageChips(): array
    {
        $today = $this->today();
        $pool = $this->allStudents()->filter(fn (Student $s): bool => $this->matches($s, skip: 'age'));

        $labels = [];
        foreach ($this->allStudents() as $student) {
            if ($category = AgeCategory::for($student->birth_date, $today)) {
                $labels[$category->key] = $category->label;
            }
        }

        $chips = [['key' => '', 'label' => 'Svi', 'count' => $pool->count()]];

        foreach (self::AGE_ORDER as $key) {
            if (! isset($labels[$key])) {
                continue;
            }

            $chips[] = [
                'key' => $key,
                'label' => $labels[$key],
                'count' => $pool->filter(fn (Student $s): bool => AgeCategory::for($s->birth_date, $today)?->key === $key)->count(),
            ];
        }

        if ($this->allStudents()->contains(fn (Student $s): bool => $s->birth_date === null)) {
            $chips[] = [
                'key' => self::NO_BIRTH_DATE,
                'label' => 'Bez datuma rođenja',
                'count' => $pool->filter(fn (Student $s): bool => $s->birth_date === null)->count(),
            ];
        }

        return $chips;
    }

    private function subtitle(int $shown): string
    {
        $total = $this->allStudents()->count();

        return $this->hasActiveFilters()
            ? "Prikazano {$shown} od {$total}"
            : "{$total} upisanih polaznika";
    }

    public function render()
    {
        $today = $this->today();
        $rows = $this->rows();

        return view('livewire.students-list', [
            'rows' => $rows,
            'today' => $today,
            'ageChips' => $this->ageChips(),
            'expiredCount' => $this->allStudents()
                ->filter(fn (Student $s): bool => MedicalStatus::for($s->medical_valid_until, $s->has_no_medical, $today) === MedicalStatus::Expired)
                ->count(),
            'toRegisterCount' => $this->allStudents()
                ->filter(fn (Student $s): bool => $s->next_grade_status === NextGradeStatus::ToRegister)
                ->count(),
            'locations' => Location::orderBy('name')->get(),
            'groups' => TrainingGroup::orderBy('name')->get(),
            'belts' => Belt::options(),
            'subtitle' => $this->subtitle($rows->count()),
            'filtersActive' => $this->hasActiveFilters(),
        ]);
    }
}
