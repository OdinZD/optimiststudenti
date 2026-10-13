<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Declaration extends Model
{
    protected $fillable = ['student_id', 'path', 'original_name', 'mime', 'size'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
