# ADR 0011: Brisanje polaznika je soft delete uz „Poništi"

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno)

## Context
`docs/design/SPEC.md` (red 77 i 190) određuje da `students` ima `deleted_at` i da „Da, obriši" radi soft delete uz toast „Obrisano: Prezime Ime" s gumbom „Poništi".

## Decision
`Student` koristi `SoftDeletes`; brisanje ne uklanja redak, a „Poništi" ga vraća (komponenta `StudentDelete`, događaj `student-deleted`).

## Alternatives Considered
Trajno brisanje — nije zapisan razlog protiv; SPEC jednostavno propisuje soft delete.

## Consequences
- Upiti polaznika ne vide obrisane retke; zapisi ostaju u bazi (važno za osobne podatke i GDPR: trajno brisanje trenutno ne postoji kao funkcija). [NEJASNO: postoji li postupak trajnog brisanja]
