<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class KataTeam extends Model
{
    protected $fillable = ['name', 'color'];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }
}
