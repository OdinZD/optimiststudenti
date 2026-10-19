<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Belt;
use App\Enums\NextGradeStatus;
use App\Support\AgeCategory;
use App\Support\Diacritics;
use App\Support\MedicalStatus;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

final class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'last_name',
        'first_name',
        'birth_date',
        'oib',
        'location_id',
        'training_group_id',
        'belt_kyu',
        'next_grade_status',
        'next_grade_kyu',
        'medical_valid_until',
        'has_no_medical',
        'parent_name',
        'parent_phone',
        'parent_email',
        'note',
        'flag_t',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'immutable_date',
            'medical_valid_until' => 'immutable_date',
            'belt_kyu' => Belt::class,
            'next_grade_status' => NextGradeStatus::class,
            'next_grade_kyu' => 'integer',
            'has_no_medical' => 'boolean',
            'flag_t' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsToMany<TrainingGroup, $this> */
    public function trainingGroups(): BelongsToMany
    {
        return $this->belongsToMany(TrainingGroup::class, 'student_training_group');
    }

    public function competitionResults(): HasMany
    {
        return $this->hasMany(CompetitionResult::class);
    }

    public function kataTeams(): BelongsToMany
    {
        return $this->belongsToMany(KataTeam::class);
    }

    public function declaration(): HasOne
    {
        return $this->hasOne(Declaration::class);
    }

    /**
     * Training slots derived from the student's groups (never stored). Needs `trainingGroups.slots.location` eager-loaded in lists.
     *
     * @return Attribute<Collection<int, TrainingSlot>, never>
     */
    protected function trainingSlots(): Attribute
    {
        return Attribute::get(
            fn (): Collection => $this->trainingGroups->flatMap(fn (TrainingGroup $g) => $g->slots)->unique('id')->sortBy('sort_order')->values(),
        );
    }

    /**
     * Distinct locations of the derived slots; falls back to the legacy single location for students without groups
     * so existing data is not lost before groups are assigned.
     *
     * @return Attribute<Collection<int, Location>, never>
     */
    protected function trainingLocations(): Attribute
    {
        return Attribute::get(function (): Collection {
            if ($this->hasLegacyLocationOnly()) {
                return collect([$this->location]);
            }

            return $this->training_slots->map(fn (TrainingSlot $slot) => $slot->location)->filter()->unique('id')->values();
        });
    }

    /** True when no slot can be derived (no groups with slots) but the old single location is still set — shown dimmed in the list. */
    public function hasLegacyLocationOnly(): bool
    {
        return $this->training_slots->isEmpty() && $this->location !== null;
    }

    /** @return Attribute<?AgeCategory, never> */
    protected function ageCategory(): Attribute
    {
        return Attribute::get(
            fn (): ?AgeCategory => AgeCategory::for($this->birth_date, CarbonImmutable::now()),
        );
    }

    /**
     * Heads-up when the student crosses into the next age category within 6 months
     * (null otherwise). Drives the ↑ marker + "Prelazi kategoriju" filter on the list.
     *
     * @return Attribute<array{months: int, days: int, nextLabel: string, remaining: string}|null, never>
     */
    protected function crossoverNotice(): Attribute
    {
        return Attribute::get(
            fn (): ?array => $this->age_category?->crossoverWithin(CarbonImmutable::now(), 6),
        );
    }

    /** @return Attribute<MedicalStatus, never> */
    protected function medicalStatus(): Attribute
    {
        return Attribute::get(
            fn (): MedicalStatus => MedicalStatus::for(
                $this->medical_valid_until,
                $this->has_no_medical,
                CarbonImmutable::now(),
            ),
        );
    }

    /** Diacritic-insensitive text searched by the list filter (SPEC §4.3). */
    protected function searchHaystack(): Attribute
    {
        return Attribute::get(fn (): string => Diacritics::normalize(implode(' ', array_filter([
            $this->last_name,
            $this->first_name,
            $this->parent_name,
            $this->parent_email,
            $this->parent_phone,
            $this->oib,
        ]))));
    }

    /** @return Attribute<string, never> */
    protected function formattedPhone(): Attribute
    {
        return Attribute::get(fn (): string => Phone::format($this->parent_phone));
    }
}
