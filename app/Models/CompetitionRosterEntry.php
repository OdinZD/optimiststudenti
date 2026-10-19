<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Discipline;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CompetitionRosterEntry extends Model
{
    protected $fillable = ['competition_roster_id', 'student_id', 'discipline', 'category', 'note', 'confirmed'];

    protected function casts(): array
    {
        return [
            'discipline' => Discipline::class,
            'confirmed' => 'boolean',
        ];
    }

    public function roster(): BelongsTo
    {
        return $this->belongsTo(CompetitionRoster::class, 'competition_roster_id');
    }

    /** Soft-deleted students resolve to null here; callers must filter those entries out. */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
