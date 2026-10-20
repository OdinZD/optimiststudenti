# ADR 0012: Izjave polaznika šifrirane na privatnom disku

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno)

## Context
Potpisana roditeljska izjava je osobni dokument djeteta.

## Decision
`DeclarationStorage` sprema datoteku u `storage/app/private/izjave` pod slučajnim imenom (UUID), šifriranu ključem aplikacije (`Crypt::encryptString`); nikad web-dostupno ni u gitu; čita se samo kroz autenticiranu rutu `izjava` (komentar u `app/Support/DeclarationStorage.php:12-16`).

## Alternatives Considered
Nisu zapisane.

## Consequences
- Gubitak `APP_KEY` znači nečitljive izjave.
- Svaki prijavljeni korisnik može preuzeti bilo koju izjavu ([11](../tehnicka/11-poznata-ogranicenja.md), nalaz 1).
