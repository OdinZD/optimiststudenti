<?php

// Izmišljeni podaci za slike zaslona u uputama. POKRENUTI SAMO na probnoj bazi:
//   DB_DATABASE=optimist_upute php artisan tinker docs/upute/alati/seed-demo.php
// Sva imena, e-mailovi i brojevi su izmišljeni; stvarni podaci nikad ne ulaze u upute.

if (DB::connection()->getDatabaseName() !== 'optimist_upute') {
    throw new RuntimeException('Odbijam: baza nije optimist_upute ('.DB::connection()->getDatabaseName().').');
}

$today = now();
$groupId = fn (string $slug): int => (int) App\Models\TrainingGroup::where('slug', $slug)->value('id');

$admin = App\Models\User::create([
    'name' => 'Trener Probni',
    'email' => 'trener@primjer.example',
    'password' => 'demo-upute-2026',
    'role' => App\Enums\Role::Admin,
]);

// [prezime, ime, rođen, pojas(kyu), grupe, liječnički (dani od danas | null | 'nema'), upis stupnja, roditelj, oznaka t, tim]
$rows = [
    ['Probni', 'Ana', '2015-03-12', 6, ['stariji-pocetnici', 'kumite-mladi'], 100, ['to_register', null], 'Marija Probni', false, 'Tim A'],
    ['Primjerić', 'Marko', '2012-06-20', 4, ['kata-napredni', 'kumite-stariji'], 20, ['none', null], 'Ivana Primjerić', false, 'Tim A'],
    ['Uzorić', 'Luka', '2017-01-05', 8, ['pocetnici'], -15, ['none', null], 'Petar Uzorić', false, null],
    ['Testić', 'Maja', '2014-09-30', 5, ['stariji-pocetnici'], 200, ['registered', 4], 'Jelena Testić', false, 'Tim B'],
    ['Demić', 'Ivan', '2010-02-14', 2, ['kumite-stariji', 'kata-napredni'], 300, ['none', null], 'Ante Demić', false, null],
    ['Vježbić', 'Petra', '2016-11-02', 7, ['pocetnici'], null, ['none', null], 'Sanja Vježbić', false, null],
    ['Probić', 'Filip', '2013-05-25', 5, ['stariji-pocetnici', 'kumite-stariji'], 60, ['none', null], 'Tomislav Probić', true, null],
    ['Primjer', 'Nika', '2018-04-18', 9, ['pocetnici'], 150, ['none', null], 'Katarina Primjer', false, null],
    ['Uzorak', 'Tomo', '2011-12-09', 3, ['kumite-mladi'], -5, ['none', null], 'Davor Uzorak', false, 'Tim B'],
    ['Demo', 'Lara', '2015-07-07', 6, ['stariji-pocetnici', 'kumite-mladi'], 'nema', ['none', null], 'Martina Demo', false, null],
    ['Testić', 'Karlo', '2009-08-21', 1, ['kata-napredni'], 90, ['none', null], 'Zoran Testić', false, 'Tim A'],
    ['Probić', 'Sara', '2016-02-28', 8, ['pocetnici'], 45, ['none', null], 'Ines Probić', false, null],
];

$teams = [
    'Tim A' => App\Models\KataTeam::create(['name' => 'Tim A', 'color' => '#2D3E7B']),
    'Tim B' => App\Models\KataTeam::create(['name' => 'Tim B', 'color' => '#C0392B']),
];

$students = [];
foreach ($rows as $i => [$last, $first, $birth, $belt, $groups, $medical, [$nextStatus, $nextKyu], $parent, $flagT, $team]) {
    $student = App\Models\Student::create([
        'last_name' => $last,
        'first_name' => $first,
        'birth_date' => $birth,
        'belt_kyu' => $belt,
        'next_grade_status' => $nextStatus,
        'next_grade_kyu' => $nextKyu,
        'medical_valid_until' => is_int($medical) ? $today->copy()->addDays($medical)->toDateString() : null,
        'has_no_medical' => $medical === 'nema',
        'parent_name' => $parent,
        'parent_phone' => '091'.str_pad((string) (1234500 + $i), 7, '0', STR_PAD_LEFT),
        'parent_email' => strtolower(explode(' ', $parent)[0]).'.'.strtolower($last === 'Probić' ? 'probic' : iconv('UTF-8', 'ASCII//TRANSLIT', $last)).$i.'@primjer.example',
        'flag_t' => $flagT,
    ]);
    $student->trainingGroups()->attach(array_map($groupId, $groups));
    if ($team !== null) {
        $student->kataTeams()->attach($teams[$team]->id);
    }
    $students["$last $first"] = $student;
}

// Natjecanja i rezultati (sezona 2025/2026)
$zadar = App\Models\Competition::create(['name' => 'Open Zadar 2026', 'held_on' => '2026-03-14', 'city' => 'Zadar']);
$split = App\Models\Competition::create(['name' => 'Kup Splita', 'held_on' => '2025-11-23', 'city' => 'Split']);
$result = fn ($s, $c, $d, $cat, $place, $w, $l) => App\Models\CompetitionResult::create([
    'student_id' => $students[$s]->id, 'competition_id' => $c->id, 'discipline' => $d,
    'category' => $cat, 'place' => $place, 'wins' => $w, 'losses' => $l, 'bouts' => $w + $l,
]);
$result('Probni Ana', $zadar, 'kate', 'U12 kate', 2, 3, 1);
$result('Primjerić Marko', $zadar, 'kumite', 'U14 -52 kg', 1, 4, 0);
$result('Primjerić Marko', $split, 'kata_team', 'U14 kata team', 3, 1, 1);
$result('Demić Ivan', $split, 'kumite', 'U16 -61 kg', 5, 2, 2);

// Popis za natjecanje
$roster = App\Models\CompetitionRoster::create(['name' => 'Državno prvenstvo 2026', 'event_date' => '2026-11-21']);
$entry = fn ($s, $d, $cat, $conf, $note = null) => $roster->entries()->create([
    'student_id' => $students[$s]->id, 'discipline' => $d, 'category' => $cat, 'confirmed' => $conf, 'note' => $note,
]);
$entry('Probni Ana', 'kate', 'U12', true);
$entry('Probni Ana', 'kumite', 'U12 -40 kg', false);
$entry('Primjerić Marko', 'kate', 'U14', true);
$entry('Primjerić Marko', 'kumite', 'U14 -52 kg', true);
$entry('Demić Ivan', 'kumite', 'U16 -61 kg', true);
$entry('Testić Karlo', 'kate', 'U18', false, 'čeka liječnički');

// Članarine (primjeri; iznosi izmišljeni)
App\Models\ClubBillingSettings::create([
    'club_name' => 'Karate klub Optimist', 'currency' => 'EUR', 'due_day' => 10,
    'billing_months' => [9, 10, 11, 12, 1, 2, 3, 4, 5, 6], 'eracuni_environment' => 'test',
]);
$one = App\Models\FeePlan::create(['name' => '1× tjedno', 'amount_cents' => 2000, 'type' => 'monthly', 'active' => true]);
$two = App\Models\FeePlan::create(['name' => '2× tjedno', 'amount_cents' => 3000, 'type' => 'monthly', 'active' => true]);
App\Models\FeePlan::create(['name' => 'Upisnina', 'amount_cents' => 1500, 'type' => 'one_time', 'active' => true]);

$assign = fn ($s, $plan, $method, $payer = null, $email = null, $discount = null) => App\Models\StudentFeePlan::create([
    'student_id' => $students[$s]->id, 'fee_plan_id' => $plan->id, 'valid_from' => '2026-09-01',
    'nacin_placanja' => $method, 'payer_name' => $payer, 'payer_email' => $email,
    'discount_percent' => $discount, 'created_by' => $admin->id,
]);
$assign('Probni Ana', $two, 'racun_email', 'Marija Probni', 'marija.probni@primjer.example', 10);
$assign('Primjerić Marko', $two, 'gotovina', 'Ivana Primjerić');
$assign('Uzorić Luka', $one, 'racun_email', 'Petar Uzorić', 'petar.uzoric@primjer.example');
$assign('Probić Filip', $one, 'gotovina', 'Tomislav Probić');
$assign('Vježbić Petra', $one, null, 'Sanja Vježbić'); // namjerno bez načina plaćanja

App\Support\ChargeGenerator::generate('2026-09');
App\Support\ChargeGenerator::generate('2026-10');

$charge = fn ($s, $period) => App\Models\Charge::where('student_id', $students[$s]->id)->where('period', $period)->first();
App\Support\PaymentRecorder::record($students['Probić Filip'], 2000, 'cash', 'manual', '2026-09-12', null, null, $admin->id, [$charge('Probić Filip', '2026-09')->id], 'Trener Probni');
App\Support\PaymentRecorder::record($students['Primjerić Marko'], 1500, 'cash', 'manual', '2026-09-14', null, null, $admin->id, [$charge('Primjerić Marko', '2026-09')->id], 'Trener Probni');
App\Support\PaymentRecorder::writeOff($charge('Uzorić Luka', '2026-09'), 'Dogovor s roditeljem', $admin->id);

echo 'Demo podaci spremljeni: ', App\Models\Student::count(), " polaznika\n";
exit;
