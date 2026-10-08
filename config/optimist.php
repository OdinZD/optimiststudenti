<?php

declare(strict_types=1);

return [

    /*
    | The trainers allowed to log in (SPEC §1 — no self-registration).
    | Comma-separated emails + one shared initial password, both from the
    | environment so no secret is committed. Consumed by TrainerSeeder.
    */

    'trainer_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('OPTIMIST_TRAINER_EMAILS', '')),
    ))),

    'default_password' => env('OPTIMIST_DEFAULT_PASSWORD'),

];
