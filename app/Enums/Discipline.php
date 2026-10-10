<?php

declare(strict_types=1);

namespace App\Enums;

enum Discipline: string
{
    case Kate = 'kate';
    case Kumite = 'kumite';

    public function label(): string
    {
        return match ($this) {
            self::Kate => 'Kate',
            self::Kumite => 'Kumite',
        };
    }

    /** @return list<self> */
    public static function options(): array
    {
        return [self::Kate, self::Kumite];
    }
}
