<?php

declare(strict_types=1);

namespace App\Enums;

enum Discipline: string
{
    case Kate = 'kate';
    case Kumite = 'kumite';
    case KataTeam = 'kata_team';

    public function label(): string
    {
        return match ($this) {
            self::Kate => 'Kate',
            self::Kumite => 'Kumite',
            self::KataTeam => 'Kata team',
        };
    }

    /** @return list<self> */
    public static function options(): array
    {
        return [self::Kate, self::Kumite, self::KataTeam];
    }
}
