<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Competition extends Model
{
    protected $fillable = ['name', 'held_on', 'city'];

    protected function casts(): array
    {
        return ['held_on' => 'immutable_date'];
    }

    public function results(): HasMany
    {
        return $this->hasMany(CompetitionResult::class);
    }
}
