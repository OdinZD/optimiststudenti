# ADR 0014: Jedan klub, bez `tenant_id`

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno)

## Context
Aplikacija služi jednom klubu (Karate klub Optimist). Postavke naplate drže se u jednom retku (`ClubBillingSettingsSeeder`: „the single club billing-settings row").

## Decision
Nema višeklupske podrške: tablice nemaju `tenant_id`, a postavke kluba su jedan redak.

## Alternatives Considered
Višeklupski način rada nije potreban: ova aplikacija je namjerno centralizirana samo za karate. Za ostale sportove koji nose kimono i promiču polaznike pojasevima (judo, taekwondo, jiu jitsu) vlasnik razvija zaseban fork `ucenici` — to je zasebna aplikacija, ne višeklupski način rada (izvor: vlasnik, 2026-10-20).

## Consequences
- Dodavanje drugog kluba zahtijeva promjenu sheme ili zasebnu instalaciju.
