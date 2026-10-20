# ADR 0005: Feature flag za modul Članarine

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno)

## Context
Članarine se razvijaju na grani `feature/clanarine`, a ovise o eRačuni Premium API-ju koji još ne radi. Ostatak aplikacije mora ostati uporabljiv.

## Decision
Cijeli modul stoji iza `config('features.payments')` (env `FEATURE_PAYMENTS`, zadano `false`). S isključenim flagom nema ruta, navigacije ni ekrana (`config/features.php`, komentar u datoteci).

## Alternatives Considered
Nisu zapisane (npr. zasebna grana bez flagova).

## Consequences
- Isti kod može ići na produkciju s isključenim modulom.
- Tablice postoje u bazi bez obzira na flag.
- Testovi su pokrenuti s isključenim flagom (`phpunit.xml`), pa testovi Članarina uključuju flag sami.
