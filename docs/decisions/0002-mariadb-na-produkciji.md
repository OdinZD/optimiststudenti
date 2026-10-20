# ADR 0002: MariaDB na produkciji

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno)

## Context
Produkcija je cPanel dijeljeni hosting (`docs/deploy-cpanel-noterminal.md`). Dizajnerski spec (`docs/design/SPEC.md`, red 164) spominje PostgreSQL kao mogućnost za kolaciju; lokalni razvoj je koristio PostgreSQL.

## Decision
Produkcijska baza je MariaDB/MySQL (`pdo_mysql`), uvoz kroz phpMyAdmin. Testovi koriste SQLite `:memory:`.

## Alternatives Considered
PostgreSQL — dostupnost na cPanel hostingu nije zapisana u repozitoriju. [NEJASNO: je li PostgreSQL bio nedostupan na hostingu ili je jednostavno odabran MariaDB; pitati vlasnika.]

## Consequences
- Migracije i SQL skripte moraju raditi na MariaDB-u; sortiranje hrvatskih znakova rješava `CroatianCollator` u PHP-u (`ext-intl`).
- Moguće razlike lokalno/produkcija opisane su u [02-baza-podataka.md](../tehnicka/02-baza-podataka.md).
