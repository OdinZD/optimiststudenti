<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Medal is derived from the placement (plasman), not stored separately:
 * 1 → zlato, 2 → srebro, 3 → bronca, anything else → none.
 */
enum Medal: string
{
    case Gold = 'zlato';
    case Silver = 'srebro';
    case Bronze = 'bronca';

    public function label(): string
    {
        return match ($this) {
            self::Gold => 'Zlato',
            self::Silver => 'Srebro',
            self::Bronze => 'Bronca',
        };
    }

    public static function fromPlace(?int $place): ?self
    {
        return match ($place) {
            1 => self::Gold,
            2 => self::Silver,
            3 => self::Bronze,
            default => null,
        };
    }
}
