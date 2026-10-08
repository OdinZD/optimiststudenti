<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The 3 trainers who may log in. No self-registration (SPEC §1): accounts exist
 * only via this seeder. Emails and the shared initial password come from the
 * environment so no secret lives in version control. Idempotent, keyed on email.
 */
final class TrainerSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) config('optimist.default_password', '');
        /** @var list<string> $emails */
        $emails = array_values((array) config('optimist.trainer_emails', []));

        if ($emails === [] || $password === '') {
            $this->command?->warn('OPTIMIST_TRAINER_EMAILS / OPTIMIST_DEFAULT_PASSWORD not set — no trainers seeded.');

            return;
        }

        foreach ($emails as $email) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    // password cast is "hashed" — the plain value is hashed on save.
                    'password' => $password,
                    'name' => (string) Str::of($email)->before('@')->headline(),
                ],
            );
        }
    }
}
