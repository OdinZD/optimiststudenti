<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Trainer account role. Default is Trener (unchanged behaviour); Admin and
 * Blagajnik additionally may record payments / write-offs in the Članarine module.
 */
enum Role: string
{
    case Admin = 'admin';
    case Blagajnik = 'blagajnik';
    case Trener = 'trener';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Blagajnik => 'Blagajnik',
            self::Trener => 'Trener',
        };
    }

    /** Only admin and blagajnik may manage payments (record / write-off / reverse). */
    public function canManagePayments(): bool
    {
        return $this === self::Admin || $this === self::Blagajnik;
    }
}
