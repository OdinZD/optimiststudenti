# ADR 0003: Izvedene vrijednosti se računaju, ne spremaju

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno)

## Context
Mnoge vrijednosti ovise o vremenu ili o drugim zapisima: uzrasna kategorija, status liječničkog pregleda, status zaduženja, medalja, termini treninga djeteta.

## Decision
Te se vrijednosti računaju iz izvornih podataka pri čitanju: `AgeCategory` (iz datuma rođenja), `MedicalStatus` (`SoonExpiring` kad je do isteka 0–30 dana, `Expired` kad je istekao), `ChargeStatus` (iz zaduženja i alokacija; redoslijed WrittenOff → Paid → Overdue → Partial → Unpaid), `Medal` (iz mjesta), `Student::training_slots` / `training_locations` (iz grupa).

## Alternatives Considered
Spremanje u stupce zahtijevalo bi periodično ažuriranje, a na hostingu nema crona (vidi [11](../tehnicka/11-poznata-ogranicenja.md)). Taj razlog je **zaključak iz stanja sustava**, ne izričito zapisana odluka. [Potvrditi s vlasnikom.]

## Consequences
- Nema zastarjelih podataka i nema posla koji ih osvježava.
- Filtriranje po izvedenoj vrijednosti ponekad traži računanje u PHP-u umjesto u SQL-u.
- Detalji algoritama u [05-domena-i-servisi.md](../tehnicka/05-domena-i-servisi.md).
