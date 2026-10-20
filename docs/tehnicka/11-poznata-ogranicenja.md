# Poznata ograničenja i nalazi

**Izvor istine:** `routes/console.php`, `database/seeders/*`, `app/Livewire/*`, `app/Support/DeclarationStorage.php`, `app/Http/Controllers/DeclarationController.php`, `app/Models/Charge.php`, `app/Models/EracuniApiLog.php`, `docs/deploy-cpanel.md`, `docs/funkcionalnosti.md`
**Stanje:** `feature/clanarine` @ 87307b0, 2026-10-20

Ovo su nalazi pronađeni čitanjem koda tijekom izrade dokumentacije. **Ništa od ovoga nije popravljano.** Ozbiljnost je procjena: V = visoka, S = srednja, N = niska. Svaki nalaz treba po potrebi ponovno provjeriti prije popravka (stanje na dan zaglavlja).

## Sigurnost i podaci

| # | Nalaz | Ozbiljnost | Gdje |
|---|---|---|---|
| 1 | Izjave polaznika može preuzeti svaki prijavljeni korisnik (nema provjere uloge ni vlasništva) | V | `DeclarationController`, ruta `izjava` |
| 2 | Izvan Članarina nema provjere uloga: svi prijavljeni treneri smiju sve (uređivanje, brisanje, izvozi) | S | `app/Livewire/*` osim Članarina |
| 3 | Nijedno javno svojstvo Livewire komponenti nije `#[Locked]`; klijent ih teoretski može mijenjati u zahtjevu, pa se autorizacija mora provoditi u svakoj akciji | S | `app/Livewire/*` |
| 4 | Ponovno pokretanje `TrainerSeeder` resetira lozinke trenera na zadanu | S | `database/seeders/TrainerSeeder.php` |
| 5 | Stvarne adrese e-pošte trenera nalaze se u `docs/deploy-cpanel.md` u javnom repozitoriju (samo prijava, nije mijenjano) | S | `docs/deploy-cpanel.md` |

## Funkcionalnost i konzistentnost

| # | Nalaz | Ozbiljnost | Gdje |
|---|---|---|---|
| 6 | Na produkciji nema crona (`schedule:run`) ni queue workera: mjesečna zaduženja i eRačuni poslovi se ne izvršavaju sami | S (kad se Članarine uključe) | `routes/console.php`, [10-deploy-i-operacije.md](10-deploy-i-operacije.md) |
| 7 | `StudentSeeder` upisuje samo stari stupac `training_group_id`, ne pivot grupa | N | `database/seeders/StudentSeeder.php` |
| 8 | `FeePlansSeeder` bi ponovno stvorio tarifu „1× tjedno" ako je obrisana | N | `database/seeders/FeePlansSeeder.php` |
| 9 | Stupac `charges.invoice_status` se ne koristi | N | `app/Models/Charge.php` |
| 10 | Stupac `eracuni_api_log.http_status` se nikad ne postavlja | N | `app/Services/Eracuni/EracuniApiLogger.php` |
| 12 | `StudentFeePlans` i `StudentCharges` ne šalju događaj `students-changed`, pa popis polaznika ne zna da se nešto promijenilo | N | `app/Livewire/StudentFeePlans.php`, `StudentCharges.php` |
| 13 | Svojstvo `payNote` nema polje za unos u pogledu; greške za `mcPeriod` i `mcAmount` ne prikazuju tekst | N | `resources/views/livewire/student-charges.blade.php` |
| 14 | Brisanja bez potvrde: rezultati, izjava, dodjela tarife, stavka popisa, storno | S | pripadne komponente |
| 15 | Nema prijevoda (`lang/`): poruke validacije koje nisu ručno napisane izlaze kao sirovi ključ (`validation.required`) uz `APP_LOCALE=hr`, odnosno engleski uz `en` | S | validacije u komponentama |
| 16 | Ugniježđene Livewire komponente unutar `<form wire:submit="save">` obrasca polaznika: Enter u njima možda šalje obrazac | N | `student-form.blade.php` — [NEJASNO: ponašanje u pregledniku nije ručno provjereno] |

## Baza i deploy

| # | Nalaz | Ozbiljnost | Gdje |
|---|---|---|---|
| 17 | Nazivi ključeva tablice `kata_team_student` u produkcijskoj SQL skripti razlikuju se od onih iz migracije | N | `docs/sql/2026-10-11-kata-timovi.sql` ↔ migracija |
| 18 | Stanje tablice `migrations` na produkciji nije poznato; `migrate` se ondje ne smije pokretati bez provjere | S | [10-deploy-i-operacije.md](10-deploy-i-operacije.md) |
| 19 | Sigurnosne kopije baze i `storage/app/private` nisu automatizirane | S | operativno |

## Dodatni nalazi iz pregleda baze, domene i eRačuni sloja

| # | Nalaz | Ozbiljnost | Gdje |
|---|---|---|---|
| 21 | Brisanje tarife provjerava samo dodjele; tarifa sa zaduženjima a bez dodjela pada na FK `charges_fee_plan_id_foreign` | N | `FeePlansList`, [02](02-baza-podataka.md) |
| 22 | `PaymentRecorder::writeOff` ne provjerava je li zaduženje već otpisano; dopuštena je uplata od 0 centi | N | `app/Support/PaymentRecorder.php` |
| 23 | `clanarine:zaduzi 2026-13` prolazi regex, a baca neuhvaćeni `InvalidFormatException` | N | `GenerateChargesCommand` |
| 24 | `BillingReference` se poziva s `model = null`, pa je HR01 grana neaktivna; nema poziva `salesInvoice*` u `app/`; `invoice_line_template` i `reminder_days_after_due` se ne koriste | N | [06-eracuni.md](06-eracuni.md) |
| 25 | `database` queue driver broji oslobađanja zbog limitera/preklapanja kao pokušaje, pa `tries=5` može isteći bez stvarnog poziva | S | `vendor/laravel/framework/src/Illuminate/Queue/DatabaseQueue.php:612`  |
| 26 | Prazan rezultat `PartnerList` može se vratiti kao greška `#noPartnersFound`: „Provjeri vezu" bi javila neuspjeh i uz ispravne podatke [NEJASNO: potvrditi na pravom API-ju] | S | `HttpEracuniClient`, [06-eracuni.md](06-eracuni.md) |
| 27 | `.env.example` nema `FEATURE_PAYMENTS`, `ERACUNI_*`, `OPTIMIST_ADMIN_EMAILS`, `OPTIMIST_CASHIER_EMAILS`, `OPTIMIST_CLUB_NAME`; ima `APP_ENV=local`, `APP_DEBUG=true` (nesigurno ako se kopira na produkciju); `VITE_APP_NAME` se ne koristi | N | `.env.example` |
| 28 | `phpunit.xml` ne fiksira `ERACUNI_*` i `OPTIMIST_*`, pa na testove utječu stvarne vrijednosti iz razvojnog `.env` | N | `phpunit.xml` |
| 29 | `ReferenceSeeder` briše lokacije, grupe i termine koji nisu na njegovom popisu; `TrainerSeeder` pri svakom pokretanju resetira i uloge | S | `database/seeders` |
| 30 | `MedicalReminders` isključuje status `nema` (`has_no_medical`); `payments.source` je uvijek `manual` | N | `app/Support/MedicalReminders.php` |
| 31 | Ponovno pokretanje `nove-grupe.sql` nakon `kata-timovi.sql` ponovno stvara grupu `kata-timovi` | N | `docs/sql` |

## Nalazi iz pregleda ekrana i HTTP sloja

| # | Nalaz | Ozbiljnost | Gdje |
|---|---|---|---|
| 32 | Uloga blagajnik dobiva 403 na dodjeli tarifa (`assign-fee-plan` je za admina i trenera); posebno, ladica postojećeg polaznika zakazuje za blagajnika: `StudentFeePlans::mount()` traži `assign-fee-plan`, `abort(403)` ruši cijeli `edit-student` zahtjev (zaključak iz koda, nije pokrenuto) | S | `StudentFeePlans.php:48,57`, [07](07-autorizacija-i-sigurnost.md) |
| 33 | Nešifrirana privremena kopija izjave ostaje u `livewire-tmp` do ~24 h (Livewire čisti pri sljedećem uploadu) | S | upload izjave |
| 34 | `audit_logs` sadrži `payer_name` i `payer_email` | N | [02](02-baza-podataka.md) |
| 35 | `Money::parse('1.234')` daje 1,23 EUR | N | `app/Support/Money.php` |
| 36 | Odznačivanje svih kvačica „Rasporedi na" raspoređuje uplatu FIFO na sva otvorena zaduženja, ne na nijedno | N | `StudentCharges` |
| 37 | `EracuniPartnerLinks::$dismissed` se gubi pri osvježavanju iako komentar kaže „session"; `sw.js` komentar spominje `/livewire/*`, a stvarni prefiks je `/livewire-<hash>/` | N | — |
| 38 | `request()->ip()` iza cPanel proxyja: trusted proxies nisu konfigurirani [NEJASNO: utjecaj na ograničenje pokušaja prijave] | N | `bootstrap/app.php`, [prijava](04-ekrani/prijava.md) |
| 39 | E-mail platitelja s jednostrukim navodnikom prekida JS izraz u `wire:click="link('…')"` | N | [clanarine](04-ekrani/clanarine.md) |

Ostale oznake `[NEJASNO]` nalaze se u pojedinim dokumentima (pretraga: `grep -rn NEJASNO docs/tehnicka`).


## Dokumentacija

| # | Nalaz | Ozbiljnost | Gdje |
|---|---|---|---|
| 20 | `docs/funkcionalnosti.md` (i PDF) su zastarjeli; zamjenjuje ga ova dokumentacija | N | `docs/funkcionalnosti.md` |

## Stanje integracije

Članarine i eRačuni su na čekanju dok eRačuni Premium API ne proradi; pojedinosti u [06-eracuni.md](06-eracuni.md).
