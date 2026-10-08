# Optimist — evidencija polaznika

Web aplikacija za evidenciju polaznika karate kluba Optimist. Zamjenjuje Excel tablicu:
treneri vode polaznike kroz popis s pretragom i filtrima te formu za unos i uređivanje.

## Mogućnosti

- Prijava trenera (bez javne registracije)
- Popis polaznika: pretraga, filtri po uzrastu / lokaciji / grupi / pojasu, oznake liječničkog i upisa
- Dodavanje, uređivanje i brisanje (soft delete s mogućnošću poništavanja)
- Uzrasna kategorija (WKF/HKS) i status liječničkog pregleda računaju se automatski iz datuma rođenja
- Hrvatsko sortiranje i pretraga neovisna o dijakritici

## Tehnologija

- Laravel 13, PHP 8.3+
- Livewire 4 + Alpine.js, Tailwind CSS v4
- MariaDB (kompatibilno s MySQL-om)
- PHPUnit

## Lokalno pokretanje

```sh
cp .env.example .env
# postavi DB_* i OPTIMIST_* vrijednosti u .env
composer install
npm install && npm run build
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Prijava je na `/login`. Treneri se postavljaju kroz `OPTIMIST_TRAINER_EMAILS` i
`OPTIMIST_DEFAULT_PASSWORD` u `.env` (vidi `database/seeders/TrainerSeeder.php`).

## Testovi

```sh
php artisan test
```

## Zahtjevi okruženja

PHP 8.3+ s ekstenzijama `intl` (hrvatsko sortiranje i pretraga), `pdo_mysql`, `mbstring`,
`openssl`, `bcmath`, `ctype`, `fileinfo`, `tokenizer`.

## Dokumentacija

- `docs/design/SPEC.md` — funkcionalna i dizajnerska specifikacija
- `docs/deploy-cpanel.md` — upute za postavljanje na cPanel

## Napomena o privatnosti

Aplikacija obrađuje osobne podatke maloljetnika (OIB, datumi rođenja). Datoteka
`database/seeders/data/polaznici.json` je namjerno izvan verzioniranja i ne smije završiti u repozitoriju.
