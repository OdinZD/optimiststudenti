<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A recurring training slot: location + days + time (e.g. "OŠ Stanovi · Uto/Čet 20–21 h").
 * A student's slots are derived from his groups, never stored on the student.
 */
final class TrainingSlot extends Model
{
    protected $fillable = ['slug', 'location_id', 'days', 'starts_at', 'ends_at', 'sort_order'];

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsToMany<TrainingGroup, $this> */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(TrainingGroup::class, 'group_training_slot');
    }

    /** "OŠ Stanovi · Uto/Čet 20–21 h" — needs the location relation. */
    public function label(): string
    {
        return ($this->location?->name ?? '—').' · '.$this->days.' '.$this->timeRange();
    }

    /** "20–21 h", or "17:30–18:30 h" when the times are not whole hours. */
    public function timeRange(): string
    {
        return self::clock($this->starts_at).'–'.self::clock($this->ends_at).' h';
    }

    private static function clock(string $time): string
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $minutes === 0 ? (string) $hours : sprintf('%d:%02d', $hours, $minutes);
    }
}
