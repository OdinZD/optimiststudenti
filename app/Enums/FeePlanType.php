<?php

declare(strict_types=1);

namespace App\Enums;

enum FeePlanType: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case OneTime = 'one_time';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Mjesečno',
            self::Yearly => 'Godišnje',
            self::OneTime => 'Jednokratno',
        };
    }

    /** @return list<self> */
    public static function options(): array
    {
        return [self::Monthly, self::Yearly, self::OneTime];
    }
}
