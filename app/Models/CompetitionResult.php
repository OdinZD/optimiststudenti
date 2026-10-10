<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Discipline;
use App\Enums\Medal;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CompetitionResult extends Model
{
    protected $fillable = [
        'student_id',
        'competition_id',
        'discipline',
        'category',
        'place',
        'wins',
        'losses',
        'bouts',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'discipline' => Discipline::class,
            'place' => 'integer',
            'wins' => 'integer',
            'losses' => 'integer',
            'bouts' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /** @return Attribute<?Medal, never> */
    protected function medal(): Attribute
    {
        return Attribute::get(fn (): ?Medal => Medal::fromPlace($this->place));
    }
}
