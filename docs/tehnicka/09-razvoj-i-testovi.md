# Razvoj i testovi

**Izvor istine:** `README.md`, `composer.json`, `package.json`, `phpunit.xml`, `tests/`, `.gitignore`, `.editorconfig`, `database/seeders/*`
**Stanje:** `feature/clanarine` @ 87307b0, 2026-10-20

## Lokalno pokretanje

```bash
cp .env.example .env
composer install
npm install && npm run build
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Potrebne PHP ekstenzije: `intl`, `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `ctype`, `fileinfo`, `tokenizer`. Lokalna baza se konfigurira u `.env` (`DB_*`); testovi je ne koriste.
Prijava: seeder `TrainerSeeder` stvara korisnike iz `OPTIMIST_TRAINER_EMAILS` s jednom zajedničkom početnom lozinkom `OPTIMIST_DEFAULT_PASSWORD` (oboje iz `.env`, vidi [08-konfiguracija.md](08-konfiguracija.md)). **Ponovno pokretanje `TrainerSeeder` resetira lozinke** (vidi [11-poznata-ogranicenja.md](11-poznata-ogranicenja.md)).

Za Članarine lokalno: `FEATURE_PAYMENTS=true` u `.env`; `ERACUNI_DRIVER=mock` (zadano) ne zove pravi API.

## Testovi

```bash
php artisan test            # ili: composer test (prvo config:clear)
php artisan test --filter=PaymentRecorderTest
```

`phpunit.xml`: dva paketa (`Unit`, `Feature`); okolina `APP_ENV=testing`, `CACHE_STORE=array`, baza SQLite `:memory:`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`, `FEATURE_PAYMENTS=false`, `BCRYPT_ROUNDS=4`, `MAIL_MAILER=array`.

Obrasci koji se ponavljaju:

- Feature testovi koriste `RefreshDatabase` i Livewire testiranje (`Livewire::test(...)`).
- Testovi Članarina uključuju flag po testu (jer je u `phpunit.xml` isključen).
- eRačuni klijent se u testovima zamjenjuje klasom `tests/Fakes/FakeEracuniClient.php`; HTTP sloj se testira s `Http::fake` (`HttpEracuniClientTest`).
- Pravila domene imaju Unit testove (`AgeCategoryTest`, `MedicalStatusTest`, `MoneyTest`, `SeasonTest`, `OibTest`, …).

Pravilo projekta: **test se ne mijenja da bi prošao**; ako je test pogrešan, to se prijavi.

## Konvencije

- Kod, identifikatori, komentari i commit poruke na engleskom; UI i dokumentacija na hrvatskom.
- `declare(strict_types=1)` u novim datotekama koje ga imaju u okolini; stil prati susjedni kod (Laravel Pint je dostupan kao dev paket, `pint.json` ne postoji).
- Novac se uvijek drži u **centima** (cijeli broj), vidi `app/Support/Money.php` i ADR [0004](../decisions/0004-novac-u-centima-bez-brisanja.md).
- Vrijednosti poput uzrasta, liječničkog statusa, statusa zaduženja i termina **računaju se**, ne spremaju (ADR [0003](../decisions/0003-izracunato-ne-spremljeno.md)).
- Migracije su *add-only*: stari stupci se ne brišu (npr. `location_id`, `training_group_id` na `students`).
- Commit radi vlasnik repozitorija; push također.

## Kako dodati …

| Zadatak | Koraci |
|---|---|
| Novu tablicu / stupac | migracija u `database/migrations` + **idempotentna** SQL skripta u `docs/sql` (produkcija nema `migrate`) + dopuna [02-baza-podataka.md](02-baza-podataka.md) |
| Novi ekran | Livewire klasa u `app/Livewire`, Blade u `resources/views/livewire`, ruta u `routes/web.php` (u `auth` grupi), stavka u navigaciji (`resources/views/components/layouts`), dokument u `04-ekrani/` |
| Novu akciju na ekranu | javna metoda u komponenti; autorizacija i validacija **unutar metode** (Livewire metode su javno pozivljive iz preglednika); `wire:click` u Bladeu; red u tablici akcija ekrana |
| Novi izvoz | klasa u `app/Support` (vidi `CompetitionExport`, `SpreadsheetWriter`); privremena datoteka pa `response()->download($path, $filename)->deleteFileAfterSend()` (vidi `StudentsList.php:138`) |
| Novu ulogu / dozvolu | `app/Enums/Role.php`, metoda na `User`, `Gate::define` u `AppServiceProvider`; provodi se u ruti (`can:`) **i** u metodama komponente |
| Novu env varijablu | uvijek preko `config/*.php` (`env()` samo u config datotekama) + [08-konfiguracija.md](08-konfiguracija.md) |
| Novi događaj | `$this->dispatch('ime')` + `#[On('ime')]`; dopuniti tablicu događaja u [03-http-i-livewire.md](03-http-i-livewire.md) |

## Provjera dokumentacije

```bash
php docs/tehnicka/alati/provjeri.php           # sve provjere (shema, rute, Livewire, env, događaji, linkovi, PII)
php docs/tehnicka/alati/provjeri.php shema     # samo jedna
MERMAID_DIR=/tmp/mermaid-provjera node docs/tehnicka/alati/mermaid.cjs
```

`provjeri.php` zahtijeva lokalnu bazu s migracijama (čita samo `information_schema`) i izlazi s kodom 1 ako dokumentacija odstupa od koda. `mermaid.cjs` traži privremeno instalirane `mermaid` i `puppeteer-core` izvan repozitorija (upute u zaglavlju datoteke).

## Zamke (stvarno nastale u projektu)

| Zamka | Što napraviti |
|---|---|
| Dev `composer install` mijenja `vendor/composer/autoload_*` i `installed.*` koji su u gitu | prije merge-a `git checkout -- vendor/composer/autoload_*.php`; prije commita `composer install --no-dev --optimize-autoloader` i provjeriti diff |
| `php artisan serve` nadjačava env iz `.env` | za pokus s drugačijim env-om pokrenuti `php -S` iz `public/` |
| U Bladeu varijabla `slots` kolidira s komponentama | koristiti druge nazive (u kodu: `trainingSlots`) |
| `@if` zalijepljen uz riječ ruši Blade | razmak ispred direktive |
| `<select>` u Livewireu se ne resetira nakon odbijene promjene | promijeniti `wire:key` (u kodu se koristi `$rev`) |
| `sr-only` apsolutni element proširi stranicu | roditelju `relative` + overflow wrapper |
| Livewire komponenta za upload datoteke: `wire:click` akcija baca „reading 'name'" | spremanje u `updatedFile()` hooku |
| eRačuni odgovara praznim HTTP 500 | uzrok je bio `Content-Encoding: 8-bit`; klijent šalje `Accept-Encoding: identity` i `decode_content=false` ([06-eracuni.md](06-eracuni.md)) |
