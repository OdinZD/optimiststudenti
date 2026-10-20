# ADR 0014: Jedan klub, bez `tenant_id`

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno)

## Context
Aplikacija služi jednom klubu (Karate klub Optimist). Postavke naplate drže se u jednom retku (`ClubBillingSettingsSeeder`: „the single club billing-settings row").

## Decision
Nema višeklupske podrške: tablice nemaju `tenant_id`, a postavke kluba su jedan redak.

## Alternatives Considered
Razlog nije zabilježen. (Postoji zaseban fork `ucenici` za više sportova; to je zasebna aplikacija, ne višeklupski način rada.)

## Consequences
- Dodavanje drugog kluba zahtijeva promjenu sheme ili zasebnu instalaciju.
