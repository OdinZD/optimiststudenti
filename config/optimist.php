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

    // Ime kluba u zaglavlju PDF izvješća.
    'club_name' => env('OPTIMIST_CLUB_NAME', 'Karate klub Optimist'),

    /*
    | Članarine: trenerski e-mailovi koji dobivaju ulogu admin / blagajnik (smiju
    | evidentirati uplate i otpise). Zarezom odvojeno, iz okoline. Konzumira TrainerSeeder.
    | TODO: postaviti OPTIMIST_ADMIN_EMAILS / OPTIMIST_CASHIER_EMAILS za klub
    | (dok su prazni, svi su 'trener' i nitko ne može upravljati uplatama).
    */

    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('OPTIMIST_ADMIN_EMAILS', '')),
    ))),

    'cashier_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('OPTIMIST_CASHIER_EMAILS', '')),
    ))),

];
