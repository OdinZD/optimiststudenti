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
use Carbon\CarbonInterface;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function trainingGroup(): BelongsTo
    {
        return $this->belongsTo(TrainingGroup::class);
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

    /** @return HasMany<StudentFeePlan, $this> */
    public function studentFeePlans(): HasMany
    {
        return $this->hasMany(StudentFeePlan::class);
    }

    /** True if the student has an active fee-plan assignment without a payment method (Članarine). */
    public function missingPaymentMethod(?CarbonInterface $today = null): bool
    {
        $date = ($today ?? CarbonImmutable::now())->toDateString();

        $plans = $this->relationLoaded('studentFeePlans') ? $this->studentFeePlans : $this->studentFeePlans()->get();

        return $plans->contains(fn (StudentFeePlan $p): bool => $p->nacin_placanja === null
            && $p->valid_from->toDateString() <= $date
            && ($p->valid_until === null || $p->valid_until->toDateString() >= $date));
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
