# ADR 0010: Više grupa po djetetu, termini izvedeni iz grupa, stari stupci ostaju

**Status:** Accepted
**Datum:** 2026-10-20

## Context
Dijete može trenirati u više grupa (npr. Kata napredni i Kumite stariji), a grupe se održavaju u terminima (lokacija + dani + vrijeme). Trener želi filtrirati po terminu i izvesti Excel po terminu. Zahtjev vlasnika u razgovoru 2026-10-20.

## Decision
Veza dijete–grupa je many-to-many; termin (`training_slots`) je jedinica, povezana s grupama. Dijete nema vlastiti termin: `Student::training_slots` i `training_locations` se izvode iz njegovih grupa. Termin Uto/Čet ide zajedno, bez iznimki po djetetu. Stari stupci `students.location_id` i `students.training_group_id` ostaju (*add-only*; `hasLegacyLocationOnly()`).

## Alternatives Considered
Termin kao dodatna ručna oznaka na djetetu (odbačeno u razgovoru; vlasnik je odabrao „termin kao jedinica"). Detalji alternativa nisu u repozitoriju.

## Consequences
- Promjena grupe automatski mijenja djetetove termine.
- Produkcija se nadograđuje skriptom `docs/sql/2026-10-20-termini-grupe.sql`.
- `StudentSeeder` još piše samo stari stupac ([11](../tehnicka/11-poznata-ogranicenja.md), nalaz 7).
