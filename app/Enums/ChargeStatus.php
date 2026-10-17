<?php

declare(strict_types=1);

namespace App\Enums;

/** Computed status of a charge (never stored). */
enum ChargeStatus: string
{
    case Paid = 'paid';
    case Partial = 'partial';
    case Unpaid = 'unpaid';
    case Overdue = 'overdue';
    case WrittenOff = 'written_off';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Plaćeno',
            self::Partial => 'Djelomično',
            self::Unpaid => 'Neplaćeno',
            self::Overdue => 'Kasni',
            self::WrittenOff => 'Otpisano',
        };
    }
}
