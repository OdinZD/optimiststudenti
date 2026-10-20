# Deploy i operacije

**Izvor istine:** `docs/deploy-cpanel-noterminal.md`, `docs/deploy-cpanel.md`, `docs/sql/*.sql`, `README.md` (dio o PWA), `.gitignore`, `routes/console.php`
**Stanje:** `feature/clanarine` @ 87307b0, 2026-10-20

Produkcija: cPanel dijeljeni hosting **bez terminala**, MariaDB. Zato su u gitu i `vendor/` (bez dev paketa) i `public/build`, a shema se mijenja ručno SQL skriptama u phpMyAdminu (ADR [0009](../decisions/0009-vendor-i-build-u-gitu.md)).
Ovaj dokument je sažetak i postupak izdanja; potpuni postupak prve instalacije je u `docs/deploy-cpanel-noterminal.md` (ne dupliciram ga).

## Kako je produkcija posložena

| Dio | Gdje |
|---|---|
| Kod aplikacije | `/home/<cpanel_korisnik>/repositories/optimiststudenti` (cPanel Git Version Control, kloniran iz javnog repozitorija) |
| `.env` | u korijenu aplikacije (nije u gitu); predložak u `docs/deploy-cpanel-noterminal.md`, imena varijabli u [08-konfiguracija.md](08-konfiguracija.md) |
| Web korijen | `public_html/` je **kopija** sadržaja `public/`; `public_html/index.php` ima `$base` koji pokazuje na mapu aplikacije („Path-B") |
| Baza | MariaDB; shema se uvozi dumpom, a kasnije promjene SQL skriptama iz `docs/sql` |
| HTTPS | AutoSSL + Force HTTPS Redirect; kolačić sesije je secure (`SESSION_SECURE_COOKIE=true`), pa prijava radi samo preko `https://` |

Posljedica Path-B rasporeda: **sve što se mijenja u `public/` (npr. `build/`, `manifest.json`, `sw.js`, `offline.html`, `icons`) mora se ponovno kopirati u `public_html/`**; `git pull` to ne napravi.

## Postupak izdanja (korak po korak)

Lokalno:

1. Napravi promjene na grani i provjeri: `php artisan test` (vidi [09-razvoj-i-testovi.md](09-razvoj-i-testovi.md)).
2. Ako su se promijenili frontend resursi: `npm run build` (izlaz ide u `public/build`, koji je u gitu).
3. Ako su se promijenile PHP ovisnosti: `composer install --no-dev --optimize-autoloader`, pa provjeri da `git diff vendor/composer/autoload_*` sadrži samo očekivane nove klase (dev instalacija ostavlja dev pakete i prljavi `vendor/composer/*`; **ne commitati dev vendor**).
4. Ako se mijenja shema: uz migraciju napiši idempotentnu SQL skriptu u `docs/sql/GGGG-MM-DD-naziv.sql` (vidi katalog dolje).
5. Commit i push radi vlasnik repozitorija.

Na hostingu (cPanel, bez terminala):

6. **Git™ Version Control → Update from Remote.**
7. Ako je mijenjan `public/`: ponovno kopiraj `public/build` (i ostale izmijenjene datoteke) u `public_html/`. Za PWA datoteke (`manifest.json`, `sw.js`, `offline.html`, `icons`) isto; ako su se promijenile statičke datoteke, **podigni verziju cachea u `sw.js`** (`optimist-static-v1` → `-v2`) da klijenti dobiju novo.
8. Ako se mijenja shema: phpMyAdmin → odabrati bazu → kartica „SQL" → zalijepiti skriptu → „Pokreni". **Većina skripti je jednokratna** (`CREATE TABLE` bez `IF NOT EXISTS`, `ADD COLUMN`) i pada pri ponavljanju; ponovljive su samo one označene „Da" u stupcu „Višekratno?" u [katalogu](02-baza-podataka.md#katalog-docssql). Pročitaj zaglavlje skripte prije pokretanja.
9. Ako je dodan novi paket (promjena u `vendor/`), obrisati `bootstrap/cache/packages.php` i `services.php` na hostingu da se ponovno generiraju (operativno iskustvo projekta; izvor: razgovor, nije zapisano u repozitoriju). [NEJASNO: potvrditi na hostingu je li taj korak još potreban]
10. Provjera: otvoriti adresu aplikacije (`https://<domena>`), prijaviti se, otvoriti popis; ako je 500 bez detalja → privremeno `APP_DEBUG=true` u `.env`, pročitati grešku, vratiti na `false`; pravi trag je i u `storage/logs/laravel.log`.

## Katalog SQL skripti (`docs/sql`)

Potpuni katalog (što svaka skripta radi, je li višekratna, kojoj migraciji odgovara) nalazi se u [02-baza-podataka.md](02-baza-podataka.md#katalog-docssql); ovdje se ne duplicira. Redoslijed je po datumu u nazivu. Samo dio skripti ima komentar „Kako pokrenuti na produkciji"; pokretanje je uvijek isto: phpMyAdmin → baza → kartica „SQL" → zalijepiti → „Pokreni".

Stanje tablice `migrations` na produkciji nije poznato iz repozitorija (vidi [11-poznata-ogranicenja.md](11-poznata-ogranicenja.md)), pa se **ne smije pokretati `php artisan migrate` na produkciji** bez provjere.

## Redovni poslovi koji na produkciji NE postoje

| Što | Zašto je potrebno | Stanje |
|---|---|---|
| cron za `php artisan schedule:run` | `routes/console.php` planira `clanarine:zaduzi` svakog 1. u mjesecu u 06:00 | nije postavljen; zaduženja se ne generiraju sama |
| queue worker (`queue:work`) | eRačuni poslovi (`FetchEracuniPartnersJob`, `CreateEracuniPartnerJob`) idu u `database` red | nije postavljen; poslovi ostaju u tablici `jobs` |

Oba su vezana uz Članarine (iza `FEATURE_PAYMENTS`, trenutno na čekanju). cPanel ima „Cron Jobs"; kad se Članarine uključe, dodati npr. `* * * * * php /home/<korisnik>/repositories/optimiststudenti/artisan schedule:run` i način obrade reda (npr. `queue:work --stop-when-empty` iz crona). [NEJASNO: stvarni put do PHP binarija na hostingu]

## Povrat (rollback)

- **Kod:** u cPanel Gitu vratiti na prethodni commit (ili `git revert` lokalno pa novo izdanje), ponovno kopirati `public/` u `public_html/`.
- **Baza:** skripte su add-only; povrat nema automatskog koraka. Prije svake promjene sheme napraviti izvoz baze u phpMyAdminu (Export). [Preporuka, ne postoji u repozitoriju.]

## Održavanje i sigurnost podataka

- `.env` i `polaznici.json` (osobni podaci) nisu u gitu (`.gitignore`).
- Izjave polaznika su na privatnom disku (`storage/app/private`), vidi [07-autorizacija-i-sigurnost.md](07-autorizacija-i-sigurnost.md).
- Sigurnosna kopija baze i `storage/app/private` **nije automatizirana** u repozitoriju.

## Rješavanje poznatih problema (iz iskustva)

| Simptom | Uzrok / rješenje |
|---|---|
| „Composer dependencies require PHP >= 8.4.1" | PHP na domeni je stariji od onog za koji je `vendor/` građen; projekt je pinan na 8.3 (`config.platform.php` u `composer.json`); domena mora imati PHP ≥ 8.3 |
| Ne može se uključiti `pdo_mysql` („conflict") | već je uključen `nd_pdo_mysql` koji ga pruža; ostaviti |
| `SQLSTATE[HY000] [1045] Access denied` | pogrešan DB korisnik/lozinka; lozinka samo slova i brojevi |
| Force HTTPS prestane raditi nakon kopiranja `.htaccess` | isključiti i ponovno uključiti u cPanel „Domains" |
