# Autorizacija i sigurnost

**Izvor istine:** `app/Enums/Role.php`, `app/Models/User.php`, `app/Providers/AppServiceProvider.php`, `routes/web.php`, `bootstrap/app.php`, `config/optimist.php`, `config/session.php`, `config/auth.php`, `config/filesystems.php`, `config/livewire.php`, `database/seeders/TrainerSeeder.php`, `app/Livewire/Auth/Login.php`, `app/Livewire/StudentFeePlans.php`, `app/Livewire/StudentCharges.php`, `app/Livewire/FeePlansList.php`, `app/Livewire/ClubBillingSettingsForm.php`, `app/Livewire/EracuniPartnerLinks.php`, `app/Http/Controllers/DeclarationController.php`, `app/Support/DeclarationStorage.php`, `app/Support/Auditer.php`, `app/Models/AuditLog.php`, `app/Models/ClubBillingSettings.php`, `resources/views/livewire/student-form.blade.php`, `resources/views/livewire/student-charges.blade.php`, `resources/views/components/layouts/app.blade.php`, `vendor/livewire/livewire/src/Mechanisms/PersistentMiddleware/PersistentMiddleware.php`, `vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php`, `.gitignore`, `.env.example`

**Stanje:** feature/clanarine @ 87307b0, 2026-10-20

Verzije korištene pri provjeri: `laravel/framework` v13.35.0, `livewire/livewire` v4.4.7 (`vendor/composer/installed.json`). Ovdje se opisuje **što kod stvarno radi**; nalazi u odjeljku „Praznine i rizici" nisu popravljani (vidi [11-poznata-ogranicenja.md](11-poznata-ogranicenja.md)). Mehanizam Livewire zahtjeva opisan je u [03-http-i-livewire.md](03-http-i-livewire.md), ekrani u [04-ekrani/](04-ekrani/).

## 1. Uloge

`App\Enums\Role` (`app/Enums/Role.php:11`) je string-enum s tri vrijednosti, spremljen u `users.role` (`string(20)`, zadano `'trener'`, migracija `2026_10_17_100000_add_role_to_users_table.php`):

| Slučaj | Vrijednost | Oznaka (`label()`) | `canManagePayments()` |
|---|---|---|---|
| `Role::Admin` | `admin` | „Administrator" | `true` |
| `Role::Blagajnik` | `blagajnik` | „Blagajnik" | `true` |
| `Role::Trener` | `trener` | „Trener" | `false` |

`User` (`app/Models/User.php`): cast `'role' => Role::class` (:43); `canManagePayments()` (:22) vraća `$this->role?->canManagePayments() ?? false`; `isAdmin()` (:28) vraća `$this->role === Role::Admin`; `password` ima cast `hashed` (:42); `role` je u `#[Fillable]`.

### Kako korisnik dobiva ulogu

Nema registracije, nema ekrana za upravljanje korisnicima i nema promjene uloge u sučelju. Računi nastaju isključivo preko `Database\Seeders\TrainerSeeder` (pokreće ga i `DatabaseSeeder`):

| Izvor | Config ključ (`config/optimist.php`) | Env ime | Značenje |
|---|---|---|---|
| popis trenera | `trainer_emails` (:13) | `OPTIMIST_TRAINER_EMAILS` | zarezom odvojeni e-mailovi; samo ovi računi postoje |
| zajednička lozinka | `default_password` (:18) | `OPTIMIST_DEFAULT_PASSWORD` | ista početna lozinka za sve |
| administratori | `admin_emails` (:30) | `OPTIMIST_ADMIN_EMAILS` | e-mailovi koji dobivaju `admin` |
| blagajnici | `cashier_emails` (:35) | `OPTIMIST_CASHIER_EMAILS` | e-mailovi koji dobivaju `blagajnik` |

Logika (`database/seeders/TrainerSeeder.php:19-53`): ako je popis trenera ili lozinka prazna, seeder ispiše upozorenje i ne radi ništa. Za svaki e-mail iz `OPTIMIST_TRAINER_EMAILS` uloga je `admin` ako je (bez obzira na velika/mala slova) u popisu administratora, inače `blagajnik` ako je u popisu blagajnika, inače `trener`; zatim `User::updateOrCreate(['email' => $email], ['password' => $password, 'name' => <dio prije @, headline>, 'role' => $role])`. Posljedice: (a) adresa u `OPTIMIST_ADMIN_EMAILS`/`OPTIMIST_CASHIER_EMAILS` koja nije i u `OPTIMIST_TRAINER_EMAILS` nikad ne dobije račun; (b) dok su oba popisa uloga prazna, svi su `trener` i nitko ne može upravljati uplatama ni tarifama (komentar u `config/optimist.php:26` to bilježi kao TODO); (c) ponovno pokretanje seedera resetira lozinku, ime i ulogu (rizik 5). `.env.example` navodi samo `OPTIMIST_DEFAULT_PASSWORD` i `OPTIMIST_TRAINER_EMAILS`; imena `OPTIMIST_ADMIN_EMAILS`, `OPTIMIST_CASHIER_EMAILS` (kao i `FEATURE_PAYMENTS`) u njemu ne postoje, vidi [08-konfiguracija.md](08-konfiguracija.md). Vrijednosti env varijabli nisu ovdje navedene namjerno.

## 2. Gateovi

Definirani u `AppServiceProvider::boot()`:

| Gate | Definicija | Tko prolazi | Izvor |
|---|---|---|---|
| `manage-payments` | `$user->canManagePayments()` | admin, blagajnik | `app/Providers/AppServiceProvider.php:39` |
| `manage-fee-plans` | `$user->isAdmin()` | samo admin | `app/Providers/AppServiceProvider.php:42` |
| `assign-fee-plan` | `$user->isAdmin() \|\| $user->role === Role::Trener` | admin, trener (ne blagajnik) | `app/Providers/AppServiceProvider.php:45` |

Ne postoje Policy klase, nema `$this->authorize()` ni `#[Authorize]`/`#[Locked]` atributa u `app/` (provjereno pretragom). Svi gateovi se provjeravaju kroz `Gate::allows`, `Gate::any`, `can:` middleware ili `@can`/`can()` u Bladeu.

## 3. Matrica „radnja × uloga"

DA = dopušteno, NE = zabranjeno (403 ili skriveno), `*` = vidi bilješku ispod tablice. Stupac „Provedba" upućuje na tablicu u odjeljku 4. Podrazumijeva se prijava (`auth`); neprijavljeni vide samo `/login` i `/up`.

| Radnja | Trener | Blagajnik | Admin | Provedba |
|---|---|---|---|---|
| Popis polaznika, filteri, Excel izvozi, PDF izvješće | DA | DA | DA | samo `auth` |
| Novi polaznik (ladica bez `studentId`) | DA | DA | DA | samo `auth` |
| Uređivanje, brisanje i poništavanje brisanja polaznika | DA | `*1` | DA | samo `auth` + `*1` |
| Rezultati natjecanja djeteta, učitavanje/uklanjanje izjave (u ladici) | DA | `*1` | DA | samo `auth` |
| Pregled izjave (`GET /izjava/{student}`) | DA | DA | DA | samo `auth` |
| Natjecanja, Popisi za natjecanje, Kata timovi (CRUD i izvozi) | DA | DA | DA | samo `auth` |
| Tarife članarine (pregled, nova, uredi, obriši) | NE | NE | DA | ruta `can:manage-fee-plans` + `mount()` |
| Postavke naplate (spremi, „Provjeri vezu") | NE | NE | DA | ruta + `mount()` + `save()`/`testConnection()` |
| Partneri (osvježi, poveži, razveži, odbij, stvori) | NE | NE | DA | ruta + `mount()` + svaka metoda |
| Dodjela tarife djetetu (dodaj, uredi, ukloni) | DA | NE `*2` | DA | `mount()` i svaka metoda, gate `assign-fee-plan` |
| Ručno zaduženje | DA | NE | DA | `addCharge()` + `@can('assign-fee-plan')` |
| Pregled zaduženja, uplata i salda djeteta | DA | `*2` | DA | `Gate::any` u `StudentCharges::mount()` |
| Evidentiraj uplatu, Plaćeno gotovinom, Storniraj, Otpiši | NE | DA `*2` | DA | metode + `@can('manage-payments')` |
| Navigacijske stavke „Članarine", „Partneri", „Postavke" | skrivene | skrivene | vidljive | `resources/views/components/layouts/app.blade.php:22` |

- `*1` Blagajnik uz uključen `FEATURE_PAYMENTS` ne može otvoriti ladicu postojećeg polaznika (vidi rizik 3). Bez flaga ladica radi kao za trenera.
- `*2` Ulaz u odjeljak „Članarina" je vidljiv za `assign-fee-plan` ILI `manage-payments`, ali `StudentFeePlans::mount()` zahtijeva `assign-fee-plan`. Zato blagajnik **na papiru** smije evidentirati uplate, a u sučelju do njih ne može doći (rizik 3).
- Gate `assign-fee-plan` namjerno isključuje blagajnika („dodjelu tarife djetetu smiju admin i treneri (ne samo-blagajnik)", `app/Providers/AppServiceProvider.php:44`).

## 4. Gdje se provjera provodi

### 4.1 Rute (middleware)

| Rute | Middleware | Izvor |
|---|---|---|
| `GET /login` | `web`, `guest` | `routes/web.php:20-22` |
| `GET /polaznici`, `/natjecanja`, `/popisi-natjecanja`, `/popisi-natjecanja/{roster}`, `/kata-timovi`, `/izjava/{student}`, `POST /logout` | `web`, `auth` | `routes/web.php:24-30,39` |
| `GET /clanarine/tarife`, `/clanarine/postavke`, `/clanarine/partneri` | `web`, `auth`, `can:manage-fee-plans`; ruta postoji samo uz `config('features.payments')` | `routes/web.php:33-37` |
| `GET /up` | bez auth (health check) | `bootstrap/app.php:12` |

### 4.2 Livewire komponente (`abort_unless`)

| Komponenta | Provjere | Izvor (linije) |
|---|---|---|
| `FeePlansList` | samo u `mount()` (`manage-fee-plans`); akcije se oslanjaju na middleware koji Livewire ponovno primjenjuje | `app/Livewire/FeePlansList.php:49` |
| `ClubBillingSettingsForm` | `mount()`, `save()`, `testConnection()` (`manage-fee-plans`) | `app/Livewire/ClubBillingSettingsForm.php:71,125,170` |
| `EracuniPartnerLinks` | `mount()`, `refreshPartners()`, `link()`, `unlink()`, `reject()`, `createPartner()` (`manage-fee-plans`) | `app/Livewire/EracuniPartnerLinks.php:40,46,57,83,97,107` |
| `StudentFeePlans` | privatna `authorizeAssign()` (:55-58, gate `assign-fee-plan`) u `mount()`, `editAssignment()`, `save()`, `deleteAssignment()` | `app/Livewire/StudentFeePlans.php:48,94,119,161` |
| `StudentCharges` | `mount()`: `Gate::any(['assign-fee-plan','manage-payments'])` (:71); `addCharge()` `assign-fee-plan` (:90); `openPayment()` (:124), `recordPayment()` (:149), `reversePayment()` (:181), `startWriteOff()` (:199), `confirmWriteOff()` (:211): `manage-payments`; `openCash()` prolazi kroz `openPayment()` | `StudentCharges.php` |
| ostale komponente (`StudentsList`, `StudentForm`, `StudentDelete`, `StudentResults`, `StudentDeclaration`, `CompetitionsList`, `CompetitionRostersList`, `CompetitionRosterShow`, `KataTeamsList`, `Login`) | nema provjera uloga | n/a |

### 4.3 Blade (`@can` / `can()`)

| Mjesto | Izraz | Učinak |
|---|---|---|
| `components/layouts/app.blade.php:22` | `config('features.payments') && auth()->user()?->can('manage-fee-plans')` | prikaz tri navigacijske stavke |
| `livewire/student-form.blade.php:226` | `config('features.payments') && (can('assign-fee-plan') \|\| can('manage-payments'))` | prikaz odjeljka „Članarina" (montira `StudentFeePlans` i `StudentCharges`) |
| `livewire/student-charges.blade.php:17,32,113,146` | `@can('manage-payments')` | gumbi „Evidentiraj uplatu", „Plaćeno gotovinom", forma uplate, „Otpiši", „Storniraj" |
| `livewire/student-charges.blade.php:157` | `@can('assign-fee-plan')` | forma „Dodaj zaduženje (ručno)" |

Sakrivanje u Bladeu nije zaštita: stvarna provjera je u metodama (`abort_unless`).

### 4.4 Kako se auth ponovno primjenjuje na Livewire pozive

Svaka interakcija je `POST /livewire-<hash>/update` (ruta ima samo `web` + `RequireLivewireHeaders`, bez `auth`). Livewire pri svakom ažuriranju (a) provjerava HMAC checksum snapshota ključem aplikacije i (b) kroz `PersistentMiddleware` (`PersistentMiddleware.php`) ponovno izvršava dio middlewarea izvorne stranice. Snapshot sadrži `memo.path` i `memo.method` (npr. `polaznici` i `GET`); na događaj `snapshot-verified` Livewire napravi lažni zahtjev prema toj putanji, nađe rutu i propusti kroz pipeline **samo** middleware s popisa: `Illuminate\Auth\Middleware\Authenticate`, `Illuminate\Auth\Middleware\Authorize`, `Illuminate\Routing\Middleware\SubstituteBindings` (uz `AuthenticateWithBasicAuth`, Sanctum i Jetstream klase koje ovdje ne postoje). Argumenti se uspoređuju bez dijela iza `:` (`Str::before($value, ':')`). Zato `can:manage-fee-plans` (`Authorize:manage-fee-plans`) ostaje na snazi za sve pozive komponenti tarifa/postavki/partnera, a `auth` za sve ostale. `guest` (`Illuminate\Auth\Middleware\RedirectIfAuthenticated`) **nije** na popisu (na popisu je klasa `App\Http\Middleware\RedirectIfAuthenticated` koja u projektu ne postoji). Djeca u ladici (npr. `StudentCharges`) nasljeđuju `memo.path = polaznici`, dakle samo `auth`; njihove uloge provode metode, ne middleware.

Dodatno, iz `HandleComponents::callMethods` (`vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:570-580`): klijent smije pozvati **svaku javnu metodu koju definira podklasa** (osim `render`) te `__dispatch`; i iz `updateProperties`: klijent smije postaviti **svako javno svojstvo** koje nije `#[Locked]`. To uključuje `mount`, `updated*` i pomoćne javne metode (npr. `StudentsList::hasActiveFilters`). Zaštita se stoga mora nalaziti unutar svake metode koja mijenja stanje, što je u Članarinama napravljeno, a u ostatku aplikacije nije potrebno jer ondje uloge ne postoje.

## 5. Prijava, sesija, CSRF

### 5.1 Prijava i ograničenje pokušaja

`App\Livewire\Auth\Login` (detaljno u [04-ekrani/prijava.md](04-ekrani/prijava.md)): `Auth::attempt(['email','password'], $remember)`; nakon uspjeha `RateLimiter::clear`, `Session::regenerate()` i `redirect()->intended(route('polaznici'))`.

- **Ograničenje:** `RateLimiter::tooManyAttempts($key, maxAttempts: 5)` (`app/Livewire/Auth/Login.php:48`); pogrešna lozinka poziva `RateLimiter::hit($key)` (zadano 60 s prozor).
- **Ključ:** `Str::transliterate(Str::lower($email).'|'.request()->ip())` (`app/Livewire/Auth/Login.php:61`), dakle po paru e-mail + IP; pohranjeno u cache store (`CACHE_STORE`, zadano `database`, `config/cache.php:18`).
- **Poruka:** „Previše pokušaja prijave. Pokušajte ponovno za N s." (na polju `email`).
- Nema resetiranja lozinke, nema potvrde e-mail adrese, nema dvofaktorske provjere, nema odvojenog ograničenja po IP-u neovisno o e-mailu.
- Prijave i odjave se ne zapisuju u `audit_logs`.

### 5.2 Odjava

`POST /logout` (`routes/web.php:39-45`): `Auth::logout()`, `session()->invalidate()`, `session()->regenerateToken()`, preusmjerenje na `login`. Gumbi su običan HTML obrazac s `@csrf` u `resources/views/components/layouts/app.blade.php:60-67` (desktop) i `:98-104` (mobilni izbornik).

### 5.3 Sesija i kolačići (`config/session.php`, samo imena i zadane vrijednosti iz koda)

| Postavka | Env ime | Zadano u configu | Napomena |
|---|---|---|---|
| driver | `SESSION_DRIVER` | `database` (:21) | tablica `sessions` (`SESSION_TABLE`) |
| trajanje | `SESSION_LIFETIME` | `120` min (:35) | |
| istek zatvaranjem preglednika | `SESSION_EXPIRE_ON_CLOSE` | `false` (:37) | |
| šifriranje sesije | `SESSION_ENCRYPT` | `false` (:50) | |
| ime kolačića | `SESSION_COOKIE` | `<slug APP_NAME>-session` (:130) | |
| putanja / domena | `SESSION_PATH`, `SESSION_DOMAIN` | `/`, `null` | |
| `Secure` | `SESSION_SECURE_COOKIE` | `null` (:172) | nije prisilno uključeno; produkcijska vrijednost nije u repozitoriju |
| `HttpOnly` | `SESSION_HTTP_ONLY` | `true` (:185) | |
| `SameSite` | `SESSION_SAME_SITE` | `lax` (:202) | |

Hashiranje lozinki: cast `hashed` na `User`, broj rundi iz `BCRYPT_ROUNDS` (ime je u `.env.example`).

### 5.4 CSRF i Livewire zaglavlja

- Grupa `web` u Laravelu 13 sadrži `Illuminate\Foundation\Http\Middleware\PreventRequestForgery` (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/Middleware.php:490`). Zahtjev prolazi ako je čitanje (`GET`/`HEAD`/`OPTIONS`), ili ima zaglavlje `Sec-Fetch-Site: same-origin`, ili se `_token` / `X-CSRF-TOKEN` / `X-XSRF-TOKEN` poklapa sa sjednicom (`vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php:95-181`). `bootstrap/app.php` ne dodaje iznimke.
- Livewire klijent šalje `_token` u tijelu JSON-a (`vendor/livewire/livewire/dist/livewire.js:6361`) i zaglavlja `X-Livewire: 1` i `Content-type: application/json`. `RequireLivewireHeaders` odbija (404) svaki zahtjev bez `X-Livewire` ili bez JSON sadržaja. `ConvertEmptyStringsToNull` i `TrimStrings` se za Livewire zahtjeve preskaču (`vendor/livewire/livewire/src/Mechanisms/HandleRequests/HandleRequests.php:82-91`), pa se prazni stringovi ne pretvaraju u `null`.
- Neispravan checksum snapshota baca `CorruptComponentPayloadException`; nakon 10 neuspjeha u 10 minuta IP se privremeno blokira (`Checksum::$maxFailures = 10`, `$decaySeconds = 600`). `TypeError` pri hidrataciji postaje 419 (`vendor/livewire/livewire/src/Mechanisms/HandleRequests/HandleRequests.php:221-226`).
- Ograničenja tereta (`config/livewire.php`): `payload.max_size` 1 MB, `max_calls` 50, `max_components` 200, `max_nesting_depth` 10.
- 419 u pregledniku daje `confirm("This page has expired…")` i osvježavanje; svaki drugi neuspjeh (npr. 403) otvara HTML modal s tijelom odgovora (`vendor/livewire/livewire/dist/livewire.js:6428-6446`).
- U kodu aplikacije nema zaglavlja kao `Content-Security-Policy`, `X-Frame-Options` ni `Strict-Transport-Security` (pretraga `app/`, `bootstrap/`, `routes/`; `public/.htaccess` nije analiziran).

## 6. Zaštita podataka

### 6.1 Šifrirani stupci

`ClubBillingSettings::casts()` (`app/Models/ClubBillingSettings.php:51-61`): `eracuni_username`, `eracuni_secret_key`, `eracuni_token`, `eracuni_endpoint` imaju cast `encrypted` (AES uz `APP_KEY`). U sučelju se nikad ne prikazuju: forma pokazuje samo „postavljeno ••••", novi unos zamjenjuje stari, a prazan unos znači „bez promjene" (zato se vjerodajnica kroz sučelje ne može obrisati). Pri unosu vrijednost putuje kao obični string u Livewire zahtjevu (HTTPS-om) i kratko je u `updates`/snapshotu komponente; nakon spremanja se brišu iz svojstava. Audit ih namjerno izostavlja (`app/Livewire/ClubBillingSettingsForm.php:155`). Ostala polja postavki (npr. `iban`, `address`, `club_name`) nisu šifrirana.

### 6.2 Izjave roditelja

- Spremanje: `DeclarationStorage::store()` (`app/Support/DeclarationStorage.php:26-37`): `Crypt::encryptString($file->get())` na disk `local` (`storage/app/private`, `config/filesystems.php:33-36`) pod `izjave/<uuid>`; metapodaci u tablici `declarations` (`path`, `original_name`, `mime`, `size`, jedinstven `student_id`).
- Čitanje: samo `GET /izjava/{student}` (`DeclarationController`), koji dešifrira i vraća sadržaj s `Content-Type` iz `mime` i `Content-Disposition: inline`. Jedina zaštita je `auth`.
- `GET /storage/{path}` postoji jer disk `local` ima `'serve' => true`, ali Laravel ga poslužuje samo uz valjan potpisan URL (`ServeFile::hasValidSignature`, bez potpisa 403, u produkciji 404); aplikacija ne generira takve URL-ove, pa to nije put do izjava.
- Privremeni upload: Livewire prvo sprema **nešifriranu** datoteku u `livewire-tmp` na zadanom disku (`FILESYSTEM_DISK`, zadano `local`, tj. `storage/app/private/livewire-tmp`), a nakon `store()` taj privremeni primjerak se ne briše izravno; Livewire ga čisti tek pri sljedećem `_finishUpload` bilo koje komponente i to datoteke starije od 24 h (`WithFileUploads::cleanupOldUploads`, `config/livewire.php` `temporary_file_upload.cleanup = true`). Dakle nešifrirana kopija izjave može ostati na disku do ~24 h i dulje (do sljedećeg uploada).
- Endpoint `POST /livewire-<hash>/upload-file` nema `auth` middleware; zaštićen je potpisanim relativnim URL-om s ograničenim trajanjem (5 min, `max_upload_time`) i `throttle:60,1`; potpisani URL se dobiva samo pozivom `_startUpload` na komponenti, koja zahtijeva prijavu.
- Ni izjave ni privremene datoteke nisu u gitu (`storage/` nije verzioniran).

### 6.3 `audit_logs` (tablica `audit_logs`, model `AuditLog`)

Append-only (`UPDATED_AT = null`). Stupci: `user_id`, `action`, `auditable_type`, `auditable_id`, `old_values` (JSON), `new_values` (JSON), `ip`, `created_at`. Upisuje `Auditer::log($action, $auditable, $old, $new)` s `Auth::id()` i `Request::ip()`. Nema koda koji ih briše ili mijenja.

Popis akcija (sve pronađene pozive):

| `action` | Gdje | `old_values` / `new_values` |
|---|---|---|
| `fee_plan.created`, `fee_plan.updated` | `FeePlansList::save` (:120) | `new`: name, amount_cents, type, active |
| `fee_plan.deleted` | `FeePlansList::delete` (:155) | `old`: sva fillable polja tarife |
| `student_fee_plan.assigned`, `student_fee_plan.updated` | `StudentFeePlans::save` (:149,152) | `new`: student_id, fee_plan_id, popust, valid_from/until, **payer_name, payer_email**, nacin_placanja, created_by |
| `student_fee_plan.removed` | `StudentFeePlans::deleteAssignment` (:163) | `old`: fillable polja dodjele (uključuje platitelja) |
| `charge.created_manually` | `StudentCharges::addCharge` (:114) | `new`: fillable polja zaduženja |
| `payment.recorded` | `PaymentRecorder::record` (:75) | `new`: amount_cents, allocated, unallocated |
| `charge.written_off` | `PaymentRecorder::writeOff` (:101) | `old`/`new`: written_off_at, written_off_by, written_off_reason |
| `payment.reversed` | `PaymentRecorder::reverse` (:137) | `new`: reversed_payment_id, amount_cents |
| `charges.generated` | `GenerateChargesCommand` (:45) | `new`: period, created, skipped (korisnik `null` jer ide iz konzole) |
| `billing_settings.updated` | `ClubBillingSettingsForm::save` (:155) | `new`: sva polja osim četiri vjerodajnice |
| `eracuni.partners_fetch_queued` | `EracuniPartnerLinks::refreshPartners` (:49) | bez vrijednosti |
| `eracuni.partner_linked` | `EracuniPartnerLinks::link` (:77) | `new`: payer_email, eracuni_partner_id |
| `eracuni.partner_unlinked` | `EracuniPartnerLinks::unlink` (:88) | `old`: payer_email, eracuni_partner_id |
| `eracuni.partner_create_queued` | `EracuniPartnerLinks::createPartner` (:120) | `new`: payer_email |

**Što se NE bilježi:** prijava/odjava i neuspjele prijave; stvaranje, izmjena, brisanje i poništavanje brisanja polaznika; rezultati; natjecanja; popisi za natjecanje i njihove prijave; kata timovi; učitavanje/pregled/uklanjanje izjava; izvozi i PDF izvješća; promjene korisnika i uloga; `ChargeGenerator` izvan naredbe. Jedine „tragove" tih radnji čine `created_at`/`updated_at` i soft-delete `deleted_at`, bez podatka tko je radnju izvršio. Osobni podaci u audit zapisima: `payer_name` i `payer_email` (dodjele tarifa) i `payer_email` (partneri).

Dnevnik API poziva prema eRačunima (`eracuni_api_log`, redakcija) opisan je u [06-eracuni.md](06-eracuni.md).

### 6.4 Pravila o osobnim podacima u repozitoriju

- `.gitignore`: `.env`, `database/seeders/data/polaznici.json` („children's OIBs and birth dates … GDPR"), `docs/design/screens/` (mockupi sa stvarnim imenima i telefonima). `.env.example` nema stvarnih vrijednosti osim praznih i primjera (npr. `@example.com`).
- Testovi koriste tvornice i izmišljene podatke; dokumentacija u `docs/tehnicka/` koristi isključivo adrese `@primjer.example` i izmišljena imena.
- Excel „Popis polaznika" i popis za natjecanje ne sadrže OIB ni liječničke podatke (komentar u `app/Livewire/StudentsList.php:102`); „Podsjetnici (Excel)" sadrže datum važenja liječničkog, status te ime i telefon roditelja. PDF izvješće sadrži samo ime djeteta i natjecateljske rezultate.
- Pretraga polaznika koristi OIB (`Student::search_haystack`), ali se OIB nigdje ne ispisuje u popisu.
- Izuzetak: `docs/deploy-cpanel.md:94` sadrži stvarne e-mail adrese trenera (adrese se ovdje namjerno ne ponavljaju). Prijavljeno, ne popravlja se ovdje.

## 7. Praznine i rizici

Provjereno čitanjem koda; ništa nije izvršavano u pregledniku ni na bazi osim gdje je izričito rečeno. Ozbiljnost je procjena autora dokumenta, ne mjerenje.

| # | Nalaz | Dokaz | Posljedica |
|---|---|---|---|
| 1 | Izvan Članarina nema nikakvih provjera uloga: svi prijavljeni korisnici mogu sve što trener može, uključujući brisanje polaznika, natjecanja, popisa i timova te čitanje izjava | `routes/web.php:25-30` samo `auth`; komponente u 4.2 bez `Gate`; nema Policy klasa | uloga `blagajnik` i `admin` ne daju ni manje ni više prava na polaznike |
| 2 | Nijedno svojstvo nije `#[Locked]`; id-evi (`StudentForm::studentId`, `StudentResults::studentId`, `StudentDeclaration::studentId`, `StudentFeePlans::studentId`, `StudentCharges::studentId`, `StudentDelete::studentId/deletedId`, `CompetitionsList::editId/deleteId` i sl.) mogu se prepisati krivotvorenim `updates` | `grep Locked app/` bez pogodaka; `HandleComponents::updateProperties` | prijavljeni korisnik može djelovati nad bilo kojim id-em; za Članarine gateovi su po radnji pa se uloge ne zaobilaze, ali se `studentId` može zamijeniti |
| 3 | Blagajnik i ladica postojećeg polaznika: odjeljak „Članarina" vidljiv je za `assign-fee-plan` ILI `manage-payments` (`resources/views/livewire/student-form.blade.php:226`), a `StudentFeePlans::mount()` zahtijeva `assign-fee-plan` (`app/Livewire/StudentFeePlans.php:48,57`). Montiranje djeteta događa se tijekom renderiranja `StudentForm`, pa `abort(403)` prekida cijeli Livewire zahtjev za `edit-student` | kod; testovi (`StudentChargesPaymentTest`, `CashPaymentTest`) pozivaju `StudentCharges` izravno, ne preko ladice | Izvedeno iz koda: zahtjev `POST /livewire-<hash>/update` dobiva HTTP 403, a preglednik prikazuje HTML modal s tijelom odgovora (`livewire.js:6446`); ladica se ne otvara. Blagajnik tako ne može doći do „Evidentiraj uplatu" niti uređivati postojećeg polaznika (novog može dodati). [NEJASNO: ručno potvrditi prijavom kao blagajnik uz `FEATURE_PAYMENTS=true`; ovdje nije pokretano] |
| 4 | Izjave su čitljive svakom prijavljenom korisniku (`DeclarationController` bez provjere); URL je predvidljiv (`/izjava/{id}` s rednim brojem polaznika) | `app/Http/Controllers/DeclarationController.php:18-28` | trener s računom može preuzeti izjavu bilo kojeg djeteta; nema zapisa o pristupu |
| 5 | Ponovno pokretanje `TrainerSeeder` (ili `db:seed`) postavlja lozinke svih trenera na zajedničku zadanu, resetira ime i ulogu prema env popisima | `database/seeders/TrainerSeeder.php:43-51` (`updateOrCreate` s lozinkom) | promijenjene lozinke se gube; uloga postavljena ručno u bazi se vraća na env vrijednost |
| 6 | Brisanja bez potvrde: rezultat (`StudentResults::deleteResult`), izjava (`StudentDeclaration::remove`), dodjela tarife (`StudentFeePlans::deleteAssignment`), prijava na popisu (`CompetitionRosterShow::remove`), storno uplate (`StudentCharges::reversePayment`), veza partnera (`EracuniPartnerLinks::unlink`); brisanje natjecanja, popisa, tima i tarife imaju dijalog, ali bez poništavanja | gumbi s izravnim `wire:click` | slučajan klik trajno briše podatak (storno je jedini s pratećim zapisom) |
| 7 | `StudentFeePlans` i `StudentCharges` (kao i `StudentResults`) ne šalju `students-changed` | `grep dispatch` | brojač „Nedostaje način plaćanja" (i općenito `StudentsList`) ostaje star dok se ne dogodi novi zahtjev prema `StudentsList` (filter, osvježavanje) |
| 8 | `StudentCharges::$payNote` se validira i prosljeđuje (`app/Livewire/StudentCharges.php:156,169`), ali nijedan input nije vezan na njega (`grep payNote resources/` bez pogodaka); greške za `mcPeriod` i `mcAmount` nemaju `@error` ispis (samo crveni obrub); iznos uplate 0 prolazi validaciju | `student-charges.blade.php` (`@error` samo na `payAmount`, `payReceivedBy`, `writeOffReason`, `mcFeePlanId`) | polje napomene uplate je uvijek prazno; korisnik ne vidi zašto ručno zaduženje nije spremljeno |
| 9 | Ugniježđene komponente u obrascu polaznika: `<form wire:submit="save">` (`resources/views/livewire/student-form.blade.php:15`) sadrži `<livewire:student-results>`, `<livewire:student-declaration>`, `<livewire:student-fee-plans>`, `<livewire:student-charges>`; njihovi pogledi nemaju `<form>` ni `type="submit"`, a imaju tekstualna polja (npr. „Kategorija", „Napomena", „Platitelj (ime)", „Razlog otpisa *") i vanjski obrazac ima `type="submit"` gumb (:257) | struktura pogleda | Po standardnom ponašanju HTML-a (implicitno slanje obrasca) Enter u takvom polju šalje vanjski obrazac i poziva `StudentForm::save()`; dijete bi bilo spremljeno i ladica zatvorena. [NEJASNO: ponašanje preglednika nije isprobano; provjeriti Enter u polju „Kategorija"] |
| 10 | `docs/deploy-cpanel.md:94` sadrži stvarne e-mail adrese trenera (u gitu) | `git ls-files docs/deploy-cpanel.md` | osobni podaci u repozitoriju; adrese nisu ponovljene u ovoj dokumentaciji |
| 11 | Svaka javna metoda komponente poziva se iz klijenta (osim `render`), uključujući `mount`, `updated*`, `hasActiveFilters`, `refreshList` | `vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:570-580` | zaštita mora biti unutar metode; `FeePlansList` osim `mount()` nema provjere u akcijama pa se oslanja na Livewireovo ponovno izvršavanje `can:` middlewarea (4.4) |
| 12 | `SESSION_SECURE_COOKIE` nije prisilno uključen u kodu (`null`); nema sigurnosnih HTTP zaglavlja iz aplikacije | `config/session.php:172` | ovisi o produkcijskom `.env` i `.htaccess`. [NEJASNO: provjeriti produkcijske vrijednosti] |
| 13 | Nešifrirana privremena kopija izjave u `livewire-tmp` do ~24 h | 6.2 | nešifrirani osobni dokument na disku dulje nego što `DeclarationStorage` sugerira |
| 14 | `request()->ip()` za ograničenje prijave: aplikacija ne konfigurira trusted proxies | `bootstrap/app.php` | iza proxyja ključ može biti IP proxyja. [NEJASNO: provjeriti na produkciji] |
| 15 | `EracuniPartnerLinks` ugrađuje e-mail u JavaScript izraz (`wire:click="link('…','…')"`) | `resources/views/livewire/eracuni-partner-links.blade.php:61,63,70` | e-mail s jednostrukim navodnikom (valjan prema RFC-u) prekida izraz; nije isprobano |
