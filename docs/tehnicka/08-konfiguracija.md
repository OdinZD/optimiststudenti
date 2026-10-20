# Konfiguracija

**Izvor istine:** `config/*.php` (14 datoteka), `.env.example`, `phpunit.xml`, `composer.json`, `vite.config.js`, `package.json`, `resources/css/app.css`, `bootstrap/app.php`, `bootstrap/providers.php`, `app/Providers/AppServiceProvider.php`, `vendor/laravel/framework/config/*.php` (zadane vrijednosti okvira)
**Stanje:** feature/clanarine @ 87307b0, 2026-10-20

Dokument navodi imena i značenje svih konfiguracijskih postavki i varijabli okoline. **Vrijednosti iz `.env` ovdje se namjerno ne navode**; „Zadano” je vrijednost iz koda (drugi argument `env()`), a gdje `.env.example` predlaže drugu vrijednost (npr. `APP_LOCALE=hr`), to je navedeno u značenju jer je `.env.example` dio repozitorija. Dodatne napomene o postupku objave su u [10-deploy-i-operacije.md](10-deploy-i-operacije.md), a o integraciji eRačuna u [06-eracuni.md](06-eracuni.md).

## Kako se konfiguracija učitava

- Aplikacijske datoteke u `config/` čitaju okolinu pozivom `env('IME', zadano)`. Izvan `config/` aplikacija ne poziva `env()` (kod koristi `config('…')`), pa `php artisan config:cache` ne mijenja ponašanje.
- Laravel 13 prvo učitava zadane datoteke okvira (`vendor/laravel/framework/config/*.php`), a zatim ih preko njih spaja s aplikacijskim datotekama (`Illuminate\Foundation\Bootstrap\LoadConfiguration`, spajanje `array_merge` po ključevima prve razine, `LoadConfiguration.php:91-100` i `:118`). Zato konfiguracije koje aplikacija nema u `config/` (hashing, broadcasting, concurrency, images, view, cors) i dalje postoje; vidi odlomak [Varijable okvira bez datoteke u config/](#varijable-okvira-bez-datoteke-u-config).
- Vrijednost iz okoline procesa ima prednost pred `.env` (Dotenv je nepromjenjiv); zato `phpunit.xml` može nadjačati `.env` za testove.
- Konfiguracijske datoteke po vrsti:

| Datoteka | Vrsta | Napomena |
| --- | --- | --- |
| `config/features.php` | projektna | zastavica `payments` |
| `config/eracuni.php` | projektna | `driver`, `allow_write`, `timeout` |
| `config/optimist.php` | projektna | trener/uloge/ime kluba |
| `config/livewire.php` | prilagođena | vidi [Livewire](#livewire) |
| `config/app.php`, `auth.php`, `cache.php`, `database.php`, `filesystems.php`, `logging.php`, `mail.php`, `queue.php`, `services.php`, `session.php` | zadani Laravel skelet | većina drivera nije u upotrebi (S3, Redis, Slack, …) |

## Projektne postavke

### `config/features.php`

`'payments' => (bool) env('FEATURE_PAYMENTS', false)` (`config/features.php:13`). Zastavica modula Članarine. Čita se na ovim mjestima: `routes/web.php:33` (rute `/clanarine/*` postoje samo kad je uključena), `resources/views/components/layouts/app.blade.php:22` (stavka izbornika), `resources/views/livewire/students-list.blade.php:140`, `resources/views/livewire/student-form.blade.php:226`, `app/Livewire/StudentsList.php:188` i `:344`, `app/Console/Commands/GenerateChargesCommand.php:23`. Budući da se ruta registrira pri podizanju aplikacije, promjena zastavice traži novi zahtjev (i čišćenje cachea rute ako je napravljen `route:cache`). U `phpunit.xml` je `false` (redak 35); testovi članarina uključuju je u runtimeu.

### `config/eracuni.php`

| Ključ | Izvor | Zadano | Značenje |
| --- | --- | --- | --- |
| `driver` | `ERACUNI_DRIVER` | `mock` | `http` veže `HttpEracuniClient`, inače `MockEracuniClient` (`AppServiceProvider.php:24`) |
| `allow_write` | `ERACUNI_ALLOW_WRITE` | `false` | dopušta `PartnerCreate`, `SalesInvoiceCreate`, `SalesInvoiceSendByEmail`; fiskalizacija je nepovratna, test organizacije nema |
| `timeout` | `ERACUNI_TIMEOUT` | `60` | sekunde za HTTP poziv |

Vjerodajnice (korisničko ime, secret key, token) i endpoint **nisu** u okolini nego u bazi, šifrirani, u `club_billing_settings` ([06-eracuni.md](06-eracuni.md)).

### `config/optimist.php`

| Ključ | Izvor | Zadano | Značenje |
| --- | --- | --- | --- |
| `trainer_emails` | `OPTIMIST_TRAINER_EMAILS` | `[]` | zarezom odvojen popis, `trim` + uklanjanje praznih (`:13-16`) |
| `default_password` | `OPTIMIST_DEFAULT_PASSWORD` | `null` | početna lozinka koju `TrainerSeeder` postavlja svima |
| `club_name` | `OPTIMIST_CLUB_NAME` | `Karate klub Optimist` | naziv u naslovu PDF/Excel izvoza (`ChildReport`, `StudentsList`, `CompetitionRosterShow`, `KataTeamsList`) |
| `admin_emails` | `OPTIMIST_ADMIN_EMAILS` | `[]` | e-mailovi koji pri seedanju dobivaju ulogu `admin` |
| `cashier_emails` | `OPTIMIST_CASHIER_EMAILS` | `[]` | e-mailovi koji pri seedanju dobivaju ulogu `blagajnik` |

Popisi uloga čitaju se samo u `TrainerSeeder` (`database/seeders/TrainerSeeder.php:21-34`); promjena popisa nema učinka dok se seeder ne pokrene, a pokretanje ponovno postavlja lozinku i ulogu svima ([02-baza-podataka.md](02-baza-podataka.md)).

## Varijable okoline

Ovo je popis **svih** varijabli koje čita `env()` u `config/*.php` (142). Oznaka `[E]` kraj imena znači da varijabla ima redak u `.env.example`. „Tajna?” označava varijable čija vrijednost nikad ne smije u repozitorij, zapisnik ni dokumentaciju. Većina zadanih drivera nije u upotrebi (redak ima napomenu „nije u upotrebi”); ostavljeni su jer dolaze sa skeletom i svaka bi se mogla uključiti postavljanjem varijable.

Projektne varijable `FEATURE_PAYMENTS`, `ERACUNI_DRIVER`, `ERACUNI_ALLOW_WRITE`, `ERACUNI_TIMEOUT`, `OPTIMIST_ADMIN_EMAILS`, `OPTIMIST_CASHIER_EMAILS` i `OPTIMIST_CLUB_NAME` **nisu** u `.env.example`; u njemu su samo `OPTIMIST_DEFAULT_PASSWORD` i `OPTIMIST_TRAINER_EMAILS` (`.env.example:71-72`). Novi programer koji kopira `.env.example` zato ne vidi da te postavke postoje.

### Tablice varijabli

Naslovi ispod grupiraju iste varijable po području; svaka je navedena jednom, a stupac „Gdje se čita” navodi sva mjesta u `config/` koja je koriste.

### Projektne varijable (Optimist, Članarine, eRačuni)

| Varijabla | Zadano | Značenje | Tajna? | Gdje se čita |
| --- | --- | --- | --- | --- |
| `FEATURE_PAYMENTS` | `false` | Uključuje modul Članarine: rute `/clanarine/*`, izbornik, filteri na popisu polaznika, naredba `clanarine:zaduzi`. U `phpunit.xml` je `false`; testovi koji trebaju modul to postavljaju u runtimeu (`config(['features.payments' => true])`). | ne | `config/features.php:13` |
| `ERACUNI_DRIVER` | `mock` | Koji `EracuniClient` se veže: `http` = pravi, sve ostalo = `MockEracuniClient` ([06-eracuni.md](06-eracuni.md)). | ne | `config/eracuni.php:13` |
| `ERACUNI_ALLOW_WRITE` | `false` | Dopušta pisanje u eRačune (`PartnerCreate`, `SalesInvoiceCreate`, `SalesInvoiceSendByEmail`). Fiskalizacija je automatska i nepovratna, a nema test organizacije, pa ostaje isključeno. | ne | `config/eracuni.php:21` |
| `ERACUNI_TIMEOUT` | `60` | Vremensko ograničenje HTTP poziva prema eRačunima u sekundama. | ne | `config/eracuni.php:23` |
| `OPTIMIST_TRAINER_EMAILS` [E] | prazan niz | Zarezom odvojeni e-mailovi trenera koje `TrainerSeeder` stvara (nema samoregistracije). | ne (osobni podaci) | `config/optimist.php:15` |
| `OPTIMIST_DEFAULT_PASSWORD` [E] | — | Zajednička početna lozinka koju `TrainerSeeder` postavlja svim trenerima. | da | `config/optimist.php:18` |
| `OPTIMIST_CLUB_NAME` | `Karate klub Optimist` | Naziv kluba u naslovu PDF izvješća i Excel izvoza (`config('optimist.club_name')`). | ne | `config/optimist.php:21` |
| `OPTIMIST_ADMIN_EMAILS` | prazan niz | Zarezom odvojeni e-mailovi koji dobivaju ulogu `admin` pri seedanju; ostali su `trener`. | ne (osobni podaci) | `config/optimist.php:32` |
| `OPTIMIST_CASHIER_EMAILS` | prazan niz | Zarezom odvojeni e-mailovi koji dobivaju ulogu `blagajnik` pri seedanju. | ne (osobni podaci) | `config/optimist.php:37` |

### Aplikacija i okolina

| Varijabla | Zadano | Značenje | Tajna? | Gdje se čita |
| --- | --- | --- | --- | --- |
| `APP_NAME` [E] | `Laravel` | Naziv aplikacije; koristi se i za zadani prefiks cachea, naziv kolačića sesije i prefiks Redisa. | ne | `config/app.php:16`, `config/cache.php:121`, `config/database.php:152`, `config/session.php:132` |
| `APP_ENV` [E] | `production` | Okolina (`local`, `testing`, `production`). | ne | `config/app.php:29` |
| `APP_DEBUG` [E] | `false` | Ispis detalja grešaka; na produkciji mora biti `false`. | ne | `config/app.php:42` |
| `APP_URL` [E] | `http://localhost` | Osnovni URL; koristi `artisan` i generiranje poveznica (u `filesystems.php` za URL diska `public`, u `mail.php` za EHLO domenu). | ne | `config/app.php:55`, `config/filesystems.php:44`, `config/mail.php:49` |
| `APP_TIMEZONE` [E] | `UTC` | Vremenska zona aplikacije i rasporeda. `.env.example` postavlja `Europe/Zagreb`. Pogledi koji ispisuju vrijeme pretvaraju ga u tu zonu (npr. „Zadnje dohvaćeno” na ekranu partnera). | ne | `config/app.php:68` |
| `APP_LOCALE` [E] | `en` | Jezik aplikacije; `.env.example` postavlja `hr`. | ne | `config/app.php:81` |
| `APP_FALLBACK_LOCALE` [E] | `en` | Rezervni jezik; `.env.example` postavlja `hr`. | ne | `config/app.php:83` |
| `APP_FAKER_LOCALE` [E] | `en_US` | Jezik Fakera u tvornicama; `.env.example` postavlja `hr_HR`. | ne | `config/app.php:85` |
| `APP_KEY` [E] | — | Ključ aplikacije (AES-256-CBC): šifrira izjave (`Crypt`), vjerodajnice u `club_billing_settings`, kolačiće i sesije. Promjena ključa čini te podatke nečitljivima. | da | `config/app.php:100` |
| `APP_PREVIOUS_KEYS` | prazno | Zarezom odvojeni stari ključevi za dešifriranje nakon rotacije `APP_KEY`. | da | `config/app.php:104` |
| `APP_MAINTENANCE_DRIVER` [E] | `file` | Gdje se pamti način održavanja (`down`). | ne | `config/app.php:122` |
| `APP_MAINTENANCE_STORE` | `database` | Cache store za način održavanja kad je driver `cache`. | ne | `config/app.php:123` |
| `AUTH_GUARD` | `web` | Zadani guard. | ne | `config/auth.php:19` |
| `AUTH_PASSWORD_BROKER` | `users` | Zadani broker za reset lozinke (aplikacija nema tok za reset). | ne | `config/auth.php:20` |
| `AUTH_MODEL` | `App\Models\User` | Eloquent model korisnika. | ne | `config/auth.php:67` |
| `AUTH_PASSWORD_RESET_TOKEN_TABLE` | `password_reset_tokens` | Tablica tokena za reset lozinke (neiskorištena). | ne | `config/auth.php:98` |
| `AUTH_PASSWORD_TIMEOUT` | `10800` | Sekunde nakon kojih se traži ponovna potvrda lozinke (3 sata). | ne | `config/auth.php:115` |

### Baza podataka

| Varijabla | Zadano | Značenje | Tajna? | Gdje se čita |
| --- | --- | --- | --- | --- |
| `DB_CONNECTION` [E] | `sqlite` | Zadana veza. Lokalno i na produkciji `mariadb` (`.env.example`), u testovima `sqlite`. Isti ključ ulazi u `queue.batching` i `queue.failed`. | ne | `config/database.php:20`, `config/queue.php:106`, `config/queue.php:125` |
| `DB_URL` | — | Cijeli DSN umjesto pojedinačnih polja. | da | `config/database.php:37`, `config/database.php:49`, `config/database.php:69`, `config/database.php:89`, `config/database.php:104` |
| `DB_HOST` [E] | `127.0.0.1` (`localhost` za sqlsrv) | Poslužitelj baze. | ne | `config/database.php:50`, `config/database.php:70`, `config/database.php:90`, `config/database.php:105` |
| `DB_PORT` [E] | `3306` (`5432` pgsql, `1433` sqlsrv) | Port baze. | ne | `config/database.php:51`, `config/database.php:71`, `config/database.php:91`, `config/database.php:106` |
| `DB_DATABASE` [E] | `database/database.sqlite` (sqlite), `laravel` (ostale veze) | Naziv baze; u testovima `:memory:`. | ne | `config/database.php:38`, `config/database.php:52`, `config/database.php:72`, `config/database.php:92`, `config/database.php:107` |
| `DB_USERNAME` [E] | `root` | Korisnik baze. | da | `config/database.php:53`, `config/database.php:73`, `config/database.php:93`, `config/database.php:108` |
| `DB_PASSWORD` [E] | prazno | Lozinka baze. | da | `config/database.php:54`, `config/database.php:74`, `config/database.php:94`, `config/database.php:109` |
| `DB_SOCKET` | prazno | Unix socket (MySQL/MariaDB). | ne | `config/database.php:55`, `config/database.php:75` |
| `DB_CHARSET` [E] | `utf8mb4` (`utf8` pgsql/sqlsrv) | Skup znakova. | ne | `config/database.php:56`, `config/database.php:76`, `config/database.php:95`, `config/database.php:110` |
| `DB_COLLATION` [E] | `utf8mb4_unicode_ci` | Collation (MySQL/MariaDB). | ne | `config/database.php:57`, `config/database.php:77` |
| `DB_FOREIGN_KEYS` | `true` | Uključuje FK ograničenja na `sqlite` vezi. | ne | `config/database.php:40` |
| `MYSQL_ATTR_SSL_CA` | — | Putanja do CA certifikata za SSL vezu na MySQL/MariaDB. | ne | `config/database.php:63`, `config/database.php:83` |
| `DB_SSLMODE` | `prefer` | PostgreSQL (nije u upotrebi). | ne | `config/database.php:99` |
| `DB_ENCRYPT` | `yes` | SQL Server (nije u upotrebi). | ne | `config/database.php:113` |
| `DB_TRUST_SERVER_CERTIFICATE` | `false` | SQL Server (nije u upotrebi). | ne | `config/database.php:114` |

### Sesija

| Varijabla | Zadano | Značenje | Tajna? | Gdje se čita |
| --- | --- | --- | --- | --- |
| `SESSION_DRIVER` [E] | `database` | Pohrana sesija (tablica `sessions`); u `phpunit.xml` `array`. | ne | `config/session.php:21` |
| `SESSION_LIFETIME` [E] | `120` | Trajanje sesije u minutama. | ne | `config/session.php:35` |
| `SESSION_EXPIRE_ON_CLOSE` | `false` | Sesija istječe zatvaranjem preglednika. | ne | `config/session.php:37` |
| `SESSION_ENCRYPT` [E] | `false` | Šifriranje sadržaja sesije. | ne | `config/session.php:50` |
| `SESSION_CONNECTION` | — | Veza baze za sesije. | ne | `config/session.php:76` |
| `SESSION_TABLE` | `sessions` | Tablica sesija. | ne | `config/session.php:89` |
| `SESSION_STORE` | — | Cache store za cache-bazirane drivere sesija. | ne | `config/session.php:104` |
| `SESSION_COOKIE` | `{slug(APP_NAME)}-session` | Naziv kolačića sesije (vrijednost `env()` poziva je na idućem retku). | ne | `config/session.php:130` |
| `SESSION_PATH` [E] | `/` | Putanja kolačića. | ne | `config/session.php:146` |
| `SESSION_DOMAIN` [E] | — (`null`) | Domena kolačića. | ne | `config/session.php:159` |
| `SESSION_SECURE_COOKIE` | — (`null`) | Kolačić sesije samo preko HTTPS-a. Zadano nije postavljeno (`null`); vrijednost u produkcijskom `.env` nije vidljiva iz repozitorija. | ne | `config/session.php:172` |
| `SESSION_HTTP_ONLY` | `true` | Kolačić nedostupan JavaScriptu. | ne | `config/session.php:185` |
| `SESSION_SAME_SITE` | `lax` | SameSite politika kolačića. | ne | `config/session.php:202` |
| `SESSION_PARTITIONED_COOKIE` | `false` | Particionirani kolačić. | ne | `config/session.php:215` |

### Cache

| Varijabla | Zadano | Značenje | Tajna? | Gdje se čita |
| --- | --- | --- | --- | --- |
| `CACHE_STORE` [E] | `database` | Zadani cache store; u `phpunit.xml` je `array`. | ne | `config/cache.php:18` |
| `CACHE_PREFIX` | `{slug(APP_NAME)}-cache-` | Prefiks svih cache ključeva. | ne | `config/cache.php:121` |
| `DB_CACHE_CONNECTION` | — | Veza baze za `database` cache (zadana veza ako je prazno). | ne | `config/cache.php:44` |
| `DB_CACHE_TABLE` | `cache` | Tablica `database` cachea. | ne | `config/cache.php:45` |
| `DB_CACHE_LOCK_CONNECTION` | — | Veza za zaključavanja `database` cachea. | ne | `config/cache.php:46` |
| `DB_CACHE_LOCK_TABLE` | — (`cache_locks` po okviru) | Tablica zaključavanja. | ne | `config/cache.php:47` |
| `CACHE_STORAGE_DISK` | — | Disk za `storage` cache driver (nije u upotrebi). | ne | `config/cache.php:58` |
| `CACHE_STORAGE_PATH` | `framework/cache/data` | Putanja za `storage` cache driver. | ne | `config/cache.php:59` |
| `MEMCACHED_PERSISTENT_ID` | — | Memcached (nije u upotrebi). | ne | `config/cache.php:64` |
| `MEMCACHED_USERNAME` | — | Memcached (nije u upotrebi). | da | `config/cache.php:66` |
| `MEMCACHED_PASSWORD` | — | Memcached (nije u upotrebi). | da | `config/cache.php:67` |
| `MEMCACHED_HOST` [E] | `127.0.0.1` | Memcached (nije u upotrebi). | ne | `config/cache.php:74` |
| `MEMCACHED_PORT` | `11211` | Memcached (nije u upotrebi). | ne | `config/cache.php:75` |
| `REDIS_CACHE_CONNECTION` | `cache` | Redis (nije u upotrebi). | ne | `config/cache.php:83` |
| `REDIS_CACHE_LOCK_CONNECTION` | `default` | Redis (nije u upotrebi). | ne | `config/cache.php:84` |
| `DYNAMODB_CACHE_TABLE` | `cache` | DynamoDB cache (nije u upotrebi). | ne | `config/cache.php:92` |
| `DYNAMODB_ENDPOINT` | — | DynamoDB cache (nije u upotrebi). | ne | `config/cache.php:93` |

### Red poslova (queue)

| Varijabla | Zadano | Značenje | Tajna? | Gdje se čita |
| --- | --- | --- | --- | --- |
| `QUEUE_CONNECTION` [E] | `database` | Zadana veza reda; u `phpunit.xml` je `sync`. Posao se obrađuje tek kad radi `queue:work` ([06-eracuni.md](06-eracuni.md)). | ne | `config/queue.php:16` |
| `DB_QUEUE_CONNECTION` | — | Veza baze za red (zadana ako je prazno). | ne | `config/queue.php:40` |
| `DB_QUEUE_TABLE` | `jobs` | Tablica reda. | ne | `config/queue.php:41` |
| `DB_QUEUE` | `default` | Naziv reda. | ne | `config/queue.php:42` |
| `DB_QUEUE_RETRY_AFTER` | `90` | Nakon koliko sekundi se rezervirani posao smatra zaglavljenim i ponovno daje. | ne | `config/queue.php:43` |
| `QUEUE_FAILED_DRIVER` | `database-uuids` | Gdje se bilježe neuspjeli poslovi (tablica `failed_jobs` s UUID-om). | ne | `config/queue.php:124` |
| `BEANSTALKD_QUEUE_HOST` | `localhost` | Beanstalkd (nije u upotrebi). | ne | `config/queue.php:49` |
| `BEANSTALKD_QUEUE` | `default` | Beanstalkd (nije u upotrebi). | ne | `config/queue.php:50` |
| `BEANSTALKD_QUEUE_RETRY_AFTER` | `90` | Beanstalkd (nije u upotrebi). | ne | `config/queue.php:51` |
| `SQS_PREFIX` | `https://sqs.us-east-1.amazonaws.com/your-account-id` | SQS (nije u upotrebi). | ne | `config/queue.php:60` |
| `SQS_QUEUE` | `default` | SQS (nije u upotrebi). | ne | `config/queue.php:61` |
| `SQS_SUFFIX` | — | SQS (nije u upotrebi). | ne | `config/queue.php:62` |
| `REDIS_QUEUE_CONNECTION` | `default` | Redis red (nije u upotrebi). | ne | `config/queue.php:69` |
| `REDIS_QUEUE` | `default` | Redis red (nije u upotrebi). | ne | `config/queue.php:70` |
| `REDIS_QUEUE_RETRY_AFTER` | `90` | Redis red (nije u upotrebi). | ne | `config/queue.php:71` |

### Datotečni sustav i Livewire

| Varijabla | Zadano | Značenje | Tajna? | Gdje se čita |
| --- | --- | --- | --- | --- |
| `FILESYSTEM_DISK` [E] | `local` | Zadani disk. Disk `local` ima korijen `storage/app/private` i `serve = true` (`config/filesystems.php:33-37`). | ne | `config/filesystems.php:16` |
| `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK` | — (zadani disk) | Disk za privremena učitavanja Livewirea; prazno = `FILESYSTEM_DISK`, pa su privremene datoteke u `storage/app/private/livewire-tmp`. | ne | `config/livewire.php:132` |
| `AWS_ACCESS_KEY_ID` [E] | — | AWS pristupni ključ (S3, SQS, SES, DynamoDB); nije u upotrebi. | da | `config/cache.php:89`, `config/filesystems.php:52`, `config/queue.php:58`, `config/services.php:26` |
| `AWS_SECRET_ACCESS_KEY` [E] | — | AWS tajni ključ; nije u upotrebi. | da | `config/cache.php:90`, `config/filesystems.php:53`, `config/queue.php:59`, `config/services.php:27` |
| `AWS_DEFAULT_REGION` [E] | `us-east-1` | AWS regija; nije u upotrebi. | ne | `config/cache.php:91`, `config/filesystems.php:54`, `config/queue.php:63`, `config/services.php:28` |
| `AWS_BUCKET` [E] | — | S3 bucket; nije u upotrebi. | ne | `config/filesystems.php:55` |
| `AWS_URL` | — | S3 URL; nije u upotrebi. | ne | `config/filesystems.php:56` |
| `AWS_ENDPOINT` | — | S3 endpoint; nije u upotrebi. | ne | `config/filesystems.php:57` |
| `AWS_USE_PATH_STYLE_ENDPOINT` [E] | `false` | S3 path-style; nije u upotrebi. | ne | `config/filesystems.php:58` |

### Zapisnici (logging)

| Varijabla | Zadano | Značenje | Tajna? | Gdje se čita |
| --- | --- | --- | --- | --- |
| `LOG_CHANNEL` [E] | `stack` | Zadani kanal zapisnika. | ne | `config/logging.php:21` |
| `LOG_STACK` [E] | `single` | Kanali unutar `stack` kanala (zapisnik `storage/logs/laravel.log`). | ne | `config/logging.php:57` |
| `LOG_LEVEL` [E] | `debug` (`critical` za slack) | Najniža razina zapisa po kanalu. | ne | `config/logging.php:64`, `config/logging.php:71`, `config/logging.php:79`, `config/logging.php:89`, `config/logging.php:95`, `config/logging.php:107`, `config/logging.php:118`, `config/logging.php:125` |
| `LOG_DAILY_DAYS` | `14` | Koliko dana čuva `daily` kanal. | ne | `config/logging.php:72` |
| `LOG_DEPRECATIONS_CHANNEL` [E] | `null` | Kanal za upozorenja o zastarjelosti. | ne | `config/logging.php:35` |
| `LOG_DEPRECATIONS_TRACE` | `false` | Uključuje stack trace u zapise o zastarjelosti. | ne | `config/logging.php:36` |
| `LOG_SLACK_WEBHOOK_URL` | — | Slack webhook (nije u upotrebi). | da | `config/logging.php:86` |
| `LOG_SLACK_USERNAME` | `APP_NAME` | Slack (nije u upotrebi). | ne | `config/logging.php:87` |
| `LOG_SLACK_EMOJI` | `:boom:` | Slack (nije u upotrebi). | ne | `config/logging.php:88` |
| `LOG_PAPERTRAIL_HANDLER` | `SyslogUdpHandler` | Papertrail (nije u upotrebi). | ne | `config/logging.php:96` |
| `PAPERTRAIL_URL` | — | Papertrail (nije u upotrebi). | ne | `config/logging.php:98`, `config/logging.php:100` |
| `PAPERTRAIL_PORT` | — | Papertrail (nije u upotrebi). | ne | `config/logging.php:99`, `config/logging.php:100` |
| `LOG_STDERR_FORMATTER` | — | Formatter za `stderr` kanal (nije u upotrebi). | ne | `config/logging.php:112` |
| `LOG_SYSLOG_FACILITY` | `LOG_USER` | Syslog (nije u upotrebi). | ne | `config/logging.php:119` |

### E-pošta i vanjske usluge (nije u upotrebi)

| Varijabla | Zadano | Značenje | Tajna? | Gdje se čita |
| --- | --- | --- | --- | --- |
| `MAIL_MAILER` [E] | `log` | Zadani mailer. Aplikacija ne šalje e-poštu (u `app/` nema `Mail::`/Mailable), pa je `log` dovoljan; u testovima `array`. | ne | `config/mail.php:17` |
| `MAIL_SCHEME` [E] | — | SMTP shema (nije u upotrebi). | ne | `config/mail.php:42` |
| `MAIL_URL` | — | SMTP DSN (nije u upotrebi). | da | `config/mail.php:43` |
| `MAIL_HOST` [E] | `127.0.0.1` | SMTP poslužitelj (nije u upotrebi). | ne | `config/mail.php:44` |
| `MAIL_PORT` [E] | `2525` | SMTP port (nije u upotrebi). | ne | `config/mail.php:45` |
| `MAIL_USERNAME` [E] | — | SMTP korisnik (nije u upotrebi). | da | `config/mail.php:46` |
| `MAIL_PASSWORD` [E] | — | SMTP lozinka (nije u upotrebi). | da | `config/mail.php:47` |
| `MAIL_EHLO_DOMAIN` | host iz `APP_URL` | SMTP EHLO domena (nije u upotrebi). | ne | `config/mail.php:49` |
| `MAIL_SENDMAIL_PATH` | `/usr/sbin/sendmail -bs -i` | Sendmail (nije u upotrebi). | ne | `config/mail.php:70` |
| `MAIL_LOG_CHANNEL` | — (zadani kanal) | Kanal za `log` mailer. | ne | `config/mail.php:75` |
| `MAIL_FROM_ADDRESS` [E] | `hello@example.com` | Pošiljatelj (nije u upotrebi). | ne | `config/mail.php:114` |
| `MAIL_FROM_NAME` [E] | `APP_NAME` | Naziv pošiljatelja (nije u upotrebi). | ne | `config/mail.php:115` |
| `POSTMARK_MESSAGE_STREAM_ID` | — | Postmark (nije u upotrebi). | ne | `config/mail.php:58` |
| `POSTMARK_API_KEY` | — | Postmark (nije u upotrebi). | da | `config/services.php:18` |
| `RESEND_API_KEY` | — | Resend (nije u upotrebi). | da | `config/services.php:22` |
| `SLACK_BOT_USER_OAUTH_TOKEN` | — | Slack (nije u upotrebi). | da | `config/services.php:33` |
| `SLACK_BOT_USER_DEFAULT_CHANNEL` | — | Slack (nije u upotrebi). | ne | `config/services.php:34` |

### Redis (nije u upotrebi)

| Varijabla | Zadano | Značenje | Tajna? | Gdje se čita |
| --- | --- | --- | --- | --- |
| `REDIS_CLIENT` [E] | `phpredis` | Redis (nije u upotrebi). | ne | `config/database.php:148` |
| `REDIS_CLUSTER` | `redis` | Redis (nije u upotrebi). | ne | `config/database.php:151` |
| `REDIS_PREFIX` | `{slug(APP_NAME)}-database-` | Redis (nije u upotrebi). | ne | `config/database.php:152` |
| `REDIS_PERSISTENT` | `false` | Redis (nije u upotrebi). | ne | `config/database.php:153` |
| `REDIS_URL` | — | Redis (nije u upotrebi). | da | `config/database.php:157`, `config/database.php:170` |
| `REDIS_HOST` [E] | `127.0.0.1` | Redis (nije u upotrebi). | ne | `config/database.php:158`, `config/database.php:171` |
| `REDIS_USERNAME` | — | Redis (nije u upotrebi). | da | `config/database.php:159`, `config/database.php:172` |
| `REDIS_PASSWORD` [E] | — | Redis (nije u upotrebi). | da | `config/database.php:160`, `config/database.php:173` |
| `REDIS_PORT` [E] | `6379` | Redis (nije u upotrebi). | ne | `config/database.php:161`, `config/database.php:174` |
| `REDIS_DB` | `0` | Redis (nije u upotrebi). | ne | `config/database.php:162` |
| `REDIS_CACHE_DB` | `1` | Redis (nije u upotrebi). | ne | `config/database.php:175` |
| `REDIS_MAX_RETRIES` | `3` | Redis (nije u upotrebi). | ne | `config/database.php:163`, `config/database.php:176` |
| `REDIS_BACKOFF_ALGORITHM` | `decorrelated_jitter` | Redis (nije u upotrebi). | ne | `config/database.php:164`, `config/database.php:177` |
| `REDIS_BACKOFF_BASE` | `100` | Redis (nije u upotrebi). | ne | `config/database.php:165`, `config/database.php:178` |
| `REDIS_BACKOFF_CAP` | `1000` | Redis (nije u upotrebi). | ne | `config/database.php:166`, `config/database.php:179` |

### Varijable okvira bez datoteke u config/

Zadane konfiguracije okvira (direktorij `config` unutar `vendor/laravel/framework`) čitaju i varijable koje nijedna datoteka u `config/` ne spominje. Relevantne ovdje:

| Varijabla | Zadano | Značenje | Tajna? | Gdje se čita |
| --- | --- | --- | --- | --- |
| `BCRYPT_ROUNDS` [E] | `12` | broj rundi bcrypta; `.env.example` ga postavlja na 12, `phpunit.xml` na 4 | ne | okvir: `hashing.php`, redak 32 |
| `BROADCAST_CONNECTION` [E] | `null` | zadana veza za broadcasting (aplikacija ga ne koristi); `.env.example` postavlja `log`, `phpunit.xml` `null` | ne | okvir: `broadcasting.php`, redak 18 |
| `HASH_DRIVER`, `HASH_VERIFY`, `BCRYPT_LIMIT`, `ARGON_*` | okvirne zadane | hashing detalji | ne | okvir: `hashing.php` |
| `CONCURRENCY_DRIVER`, `IMAGE_DRIVER` | `process`, `gd` | okvirne zadane | ne | okvir: `concurrency.php` i `images.php`, redak 18 |
| `PHP_CLI_SERVER_WORKERS` (u `.env.example` zakomentirano) | `1` | broj radnika `php artisan serve` | ne | `vendor/laravel/framework/src/Illuminate/Foundation/Console/ServeCommand.php:112` |

Varijabla `VITE_APP_NAME` [E] (`.env.example`: `"${APP_NAME}"`) izlaže naziv aplikacije Viteu; u `resources/` i `vite.config.js` na nju nema poziva ([NEJASNO: potvrditi da ju ne koristi nijedan JS; pretraga `VITE_APP_NAME` u `resources/` i `vite.config.js` nema pogodaka]).

Varijable u `.env.example` koje čitaju samo drivere koji se ne koriste: `MEMCACHED_HOST`, `REDIS_*`, `MAIL_*`, `AWS_*` (vidi gornje tablice).

## Ključne konfiguracije po područjima

### Aplikacija (`config/app.php`)

- `timezone` iz `APP_TIMEZONE` (zadano `UTC`, `config/app.php:68`); `.env.example` postavlja `Europe/Zagreb`. Na tu zonu se oslanjaju raspored (`monthlyOn(1, '06:00')`) i prikaz vremena.
- `locale` iz `APP_LOCALE` (zadano `en`, `:81`) i `fallback_locale` (`:83`); `.env.example` postavlja `hr` za oba, `faker_locale` `hr_HR`. Poruke validacije koje je napisao programer su na hrvatskom izravno u kodu (`messages()`); nema `lang/` direktorija.
- `cipher` = `AES-256-CBC` (`:98`), `key` iz `APP_KEY` (`:100`), `previous_keys` iz `APP_PREVIOUS_KEYS` (`:104`).
- `maintenance` driver `file`, store `database` (`:122-123`).
- `.env.example` postavlja `APP_ENV=local` i `APP_DEBUG=true`: za produkciju ne kopirati te vrijednosti.

### Baza podataka (`config/database.php`)

- `default` iz `DB_CONNECTION`, zadano `sqlite` (`:20`); `.env.example` postavlja `mariadb`, `phpunit.xml` `sqlite`.
- Veza `mariadb` (`:67-85`): `strict => true`, `prefix_indexes => true`, `engine => null` (zadani InnoDB), charset/collation iz `DB_CHARSET`/`DB_COLLATION` (`utf8mb4` / `utf8mb4_unicode_ci`); SSL CA iz `MYSQL_ATTR_SSL_CA`, a opcije se postavljaju samo ako je učitan `pdo_mysql`.
- Veza `sqlite`: `foreign_key_constraints` iz `DB_FOREIGN_KEYS` (zadano `true`, `:40`); testovi koriste `:memory:`.

### Sesija (`config/session.php`)

| Postavka | Vrijednost | Redak |
| --- | --- | --- |
| `driver` | `database` (tablica `sessions`) | `:21`, `:89` |
| `lifetime` | `120` minuta | `:35` |
| `expire_on_close` | `false` | `:37` |
| `encrypt` | `false` | `:50` |
| `cookie` | `{slug(APP_NAME)}-session` | `:130-133` |
| `http_only` | `true` | `:185` |
| `same_site` | `lax` | `:202` |
| `secure` | `null` (iz `SESSION_SECURE_COOKIE`) | `:172` |
| `lottery` | `[2, 100]` (čišćenje starih sesija, 2 %) | `:117` |
| `serialization` | `json` | `:231` |

Prijava, odjava i CSRF koriste standardni `web` guard; odjava poništava sesiju i obnavlja token (`routes/web.php`, ruta `logout`).

### Cache (`config/cache.php`)

`default` iz `CACHE_STORE`, zadano `database` (`:18`); tablice `cache` i `cache_locks` (`:42-48`); `prefix` je `{slug(APP_NAME)}-cache-` (`:121`); `serializable_classes => false` (`:134`). U testovima `array`. Aplikacijski kod sprema samo jedan ključ: `eracuni.partners` ([06-eracuni.md](06-eracuni.md)); `WithoutOverlapping` poslova za eRačune koristi zaključavanja iste pohrane.

### Red poslova (`config/queue.php`)

- `default` iz `QUEUE_CONNECTION`, zadano `database` (`:16`); u testovima `sync`.
- Veza `database` (`:38-45`): tablica `jobs`, red `default`, `retry_after` 90 s (`DB_QUEUE_RETRY_AFTER`), `after_commit => false`.
- `batching` koristi tablicu `job_batches` (`:105-108`); `failed` je `database-uuids` u tablici `failed_jobs` (`:123-127`).
- Za izvršavanje poslova mora raditi radnik (`php artisan queue:work`). To nije konfigurirano nigdje u repozitoriju (vidi [06-eracuni.md](06-eracuni.md#red-čekanja-i-praznina-u-radu)).

### Datotečni sustav (`config/filesystems.php`)

- `default` iz `FILESYSTEM_DISK`, zadano `local` (`:16`).
- Disk `local` (`:33-39`): korijen `storage/app/private`, `serve => true`, `throw => false`, `report => false`. Na njemu su šifrirane izjave (`storage/app/private/izjave`, [05-domena-i-servisi.md](05-domena-i-servisi.md)) i privremeni Livewire upload (`livewire-tmp`).
- `serve => true` registrira rutu `storage/{path}` (`storage.local`, i `PUT` za upload); za privatni disk poslužuje datoteku samo uz valjan potpisan relativni URL (`vendor/laravel/framework/src/Illuminate/Filesystem/ServeFile.php`), pa izjave nisu dostupne izravno.
- Disk `public` (`:41-48`) postoji, ali ne koristi se; PDF izvješća i Excel privremeno idu u `storage/app` (`storage/app/dompdf`, `storage/app/{uuid}.xlsx`) i brišu se nakon slanja.
- Konfiguracija DomPDF-a: aplikacija nema `config/dompdf.php` (vrijede zadane vrijednosti paketa); temp i font mapa se postavljaju u kodu na `storage/app/dompdf` (`StudentsList.php:151-161`).

### Livewire

`config/livewire.php` je zadani skelet Livewirea 4 s ovim bitnim vrijednostima:

| Postavka | Vrijednost | Redak | Napomena |
| --- | --- | --- | --- |
| `class_namespace` / `class_path` / `view_path` | `App\Livewire` / `app/Livewire` / `resources/views/livewire` | `:92`, `:105`, `:118` | class-based komponente (`make_command.type = 'class'`, `:72`) |
| `component_layout` | `layouts::app` | `:47` | komponente stranica ipak navode `#[Layout('components.layouts.app')]` |
| `inject_assets` | `true` | `:182` | Livewire sam ubacuje svoj JS/CSS (nema `@livewireScripts`/`@livewireStyles` obaveze) |
| `payload.max_size` | `1024 * 1024` (1 MB) | `:277` | najveća veličina Livewire zahtjeva |
| `payload.max_nesting_depth` | `10` | `:278` | dubina točkastih putanja svojstava |
| `payload.max_calls` | `50` | `:279` | poziva metoda po zahtjevu |
| `payload.max_components` | `200` | `:280` | komponenata po paketnom zahtjevu |
| `temporary_file_upload.disk` | iz `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK` (prazno = zadani disk) | `:132` | privremene datoteke u `storage/app/private/livewire-tmp` |
| `temporary_file_upload.rules` / `directory` / `middleware` | `null` (okvirne zadane: `required\|file\|max:12288`, `livewire-tmp`, `throttle:60,1`) | `:133-135` | komponenta izjave sama ograničava na `pdf,jpg,jpeg,png` do 10 MB (`StudentDeclaration.php:35`) |
| `temporary_file_upload.max_upload_time` / `cleanup` | `5` min / `true` (briše privremene starije od 24 h) | `:141-142` | |
| `navigate.show_progress_bar` | `true`, boja `#2299dd` | `:196-197` | |
| `smart_wire_keys` | `true` | `:224` | |
| `legacy_model_binding` / `render_on_redirect` / `csp_safe` | `false` | `:169`, `:156`, `:263` | |
| `pagination_theme` | `tailwind` | `:237` | |

### Autorizacija, ograničavač i vezivanje klijenta (`AppServiceProvider`)

`app/Providers/AppServiceProvider.php` je jedini poslužitelj usluga (`bootstrap/providers.php:5-7`). U njemu: singleton `EracuniClient` (`:24`), `RateLimiter::for('eracuni', Limit::perSecond(1))` (`:36`), gateovi `manage-payments` (`:39`), `manage-fee-plans` (`:42`) i `assign-fee-plan` (`:45`); semantika gateova je u [07-autorizacija-i-sigurnost.md](07-autorizacija-i-sigurnost.md).

### `bootstrap/app.php`

`Application::configure(basePath: dirname(__DIR__))` (`:8`): `withRouting` s `web` = `routes/web.php`, `commands` = `routes/console.php` i zdravstvenom rutom `/up` (`:9-13`); `withMiddleware` je prazan (nema vlastitih middlewarea, aliasa ni `trustProxies`, `:14-16`); `withExceptions` samo traži JSON odgovor za zahtjeve na `api/*` ili one koji očekuju JSON (`:17-20`). Nema rute `api` (aplikacija nema API).

### `bootstrap/providers.php`

Registrira samo `App\Providers\AppServiceProvider` (`:5-7`).

## Testna okolina (`phpunit.xml`)

`phpunit.xml` `<php>` blok (`:20-36`) postavlja ove varijable (nadjačavaju `.env`):

| Varijabla | Vrijednost u testu |
| --- | --- |
| `APP_ENV` | `testing` |
| `APP_MAINTENANCE_DRIVER` | `file` |
| `BCRYPT_ROUNDS` | `4` |
| `BROADCAST_CONNECTION` | `null` |
| `CACHE_STORE` | `array` |
| `DB_CONNECTION` | `sqlite` |
| `DB_DATABASE` | `:memory:` |
| `DB_URL` | prazno |
| `MAIL_MAILER` | `array` |
| `QUEUE_CONNECTION` | `sync` |
| `SESSION_DRIVER` | `array` |
| `PULSE_ENABLED`, `TELESCOPE_ENABLED`, `NIGHTWATCH_ENABLED` | `false` (paketi nisu instalirani; bezopasno) |
| `FEATURE_PAYMENTS` | `false` |

Testovi (`tests/Unit`, `tests/Feature`) tako rade bez ikakve vanjske baze. Varijable koje `phpunit.xml` **ne** postavlja (npr. `ERACUNI_DRIVER`, `ERACUNI_ALLOW_WRITE`, `OPTIMIST_*`) uzimaju se iz stvarnog `.env` programera. Testovi eRačuna koje sam pregledao koriste `FakeEracuniClient`, `Http::fake` ili `Queue::fake` i ne ovise o `ERACUNI_DRIVER`. `composer test` prije testova briše cache konfiguracije (`composer.json`, skripta `test`: `config:clear`, zatim `artisan test`).

## Alati za izgradnju

- **PHP:** `composer.json` zahtijeva `php ^8.3` i `ext-intl` (potrebna za `CroatianCollator` i `Diacritics`); `config.platform.php = 8.3.0` (`composer.json:80-82`) tjera Composer da razriješi pakete kompatibilne s PHP 8.3.0 bez obzira na lokalnu verziju. Važni paketi: `laravel/framework ^13.17`, `livewire/livewire ^4.4`, `barryvdh/laravel-dompdf ^3.1`, `openspout/openspout ^5.3`. `optimize-autoloader` je uključen.
- **Vite** (`vite.config.js`): `laravel-vite-plugin` s ulazima `resources/css/app.css` i `resources/js/app.js`, `refresh: true`; fontovi preko `bunny` dobavljača: Bricolage Grotesque (težine 600, 700) i Hanken Grotesk (400, 500, 600, 700) (`:11-18`); dodatak `@tailwindcss/vite` (`:20`); osluškivanje promjena ignorira `storage/framework/views/**` (`:22-26`). Paketi: `vite ^8.0.0`, `laravel-vite-plugin ^3.1`, `tailwindcss ^4.0.0`, `@tailwindcss/vite ^4.0.0` (`package.json`). Skripte: `npm run build` (`vite build`) i `npm run dev`.
- **Tailwind v4** radi bez `tailwind.config.js`: `resources/css/app.css` počinje s `@import 'tailwindcss'` i `@import './tokens.css'`, `@source` navodi dodatne predloške (paginacija, `storage/framework/views`), a `@theme` postavlja `--font-sans` (Hanken Grotesk) i `--font-display` (Bricolage Grotesque). Boje i razmaci su CSS varijable u `resources/css/tokens.css`.
- Izgrađeni asseti (`public/build`) i `vendor/` su u gitu jer cPanel nema terminala ([10-deploy-i-operacije.md](10-deploy-i-operacije.md)).
