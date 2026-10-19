<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A free-form competition roster ("Open Zadar 2026"); separate from Competition/CompetitionResult. */
final class CompetitionRoster extends Model
{
    protected $fillable = ['name', 'event_date'];

    protected function casts(): array
    {
        return ['event_date' => 'immutable_date'];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CompetitionRosterEntry::class);
    }
}
