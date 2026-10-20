# ADR 0009: `vendor/` i `public/build` u gitu

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno)

## Context
Produkcija je cPanel bez terminala (`docs/deploy-cpanel-noterminal.md`): ne mogu se pokrenuti `composer install` ni `npm run build`. Deploy je „Update from Remote" u cPanel Gitu.

## Decision
`vendor/` (bez dev paketa, `composer install --no-dev --optimize-autoloader`) i `public/build` su u repozitoriju. Composer je pinan na PHP 8.3 (`config.platform.php`) da `vendor/` radi na hostingu.

## Alternatives Considered
Građenje na hostingu — nemoguće bez terminala.

## Consequences
- Dev `composer install` zaprlja praćene datoteke `vendor/composer/*`; prije commita treba vratiti produkcijski vendor ([09](../tehnicka/09-razvoj-i-testovi.md)).
- Promjene u `public/` treba ručno kopirati u `public_html/` ([10](../tehnicka/10-deploy-i-operacije.md)).
