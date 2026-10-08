<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * "Upis sljedećeg stupnja" — stored in students.next_grade_status.
 * When Registered, students.next_grade_kyu holds the target kyu (1–9).
 */
enum NextGradeStatus: string
{
    case None = 'none';
    case ToRegister = 'to_register';
    case Registered = 'registered';

    /** Segmented-control label in the form (SPEC §3.3). */
    public function formLabel(): string
    {
        return match ($this) {
            self::None => 'Nije potrebno',
            self::ToRegister => 'Treba upisati',
            self::Registered => 'Već upisan',
        };
    }
}
