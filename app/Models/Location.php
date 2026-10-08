<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Location extends Model
{
    protected $fillable = ['slug', 'name'];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
