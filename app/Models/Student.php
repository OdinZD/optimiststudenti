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
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /** @return Attribute<?AgeCategory, never> */
    protected function ageCategory(): Attribute
    {
        return Attribute::get(
            fn (): ?AgeCategory => AgeCategory::for($this->birth_date, CarbonImmutable::now()),
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
