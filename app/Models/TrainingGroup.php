<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class TrainingGroup extends Model
{
    protected $fillable = ['slug', 'name', 'description'];

    /** @return BelongsToMany<Student, $this> */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_training_group');
    }

    /** @return BelongsToMany<TrainingSlot, $this> */
    public function slots(): BelongsToMany
    {
        return $this->belongsToMany(TrainingSlot::class, 'group_training_slot');
    }
}
