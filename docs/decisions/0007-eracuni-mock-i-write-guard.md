# ADR 0007: eRačuni klijent prvo kroz mock, uz zaštitu pisanja

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno)

## Context
Upisi u eRačuni (npr. izdavanje računa) imaju stvarne posljedice. Test okolina nije dostupna kao zasebna organizacija. Izvor: `config/eracuni.php:15-19` („nema test organizacije") i docblock `app/Services/Eracuni/HttpEracuniClient.php:16-18`.

## Decision
`EracuniClient` je sučelje s dvije implementacije: `MockEracuniClient` (zadano, `ERACUNI_DRIVER=mock`) i `HttpEracuniClient` (`http`). Čak i s HTTP driverom, **pisanje** je blokirano dok `ERACUNI_ALLOW_WRITE` nije uključen (provedba: `app/Services/Eracuni/HttpEracuniClient.php:59-64`, postavka `config/eracuni.php:21`; odabir drivera: `AppServiceProvider.php:24`).

## Alternatives Considered
Nisu zapisane.

## Consequences
- Razvoj i testovi ne dodiruju vanjski sustav.
- Za stvarno izdavanje treba svjesno uključiti dva prekidača.
