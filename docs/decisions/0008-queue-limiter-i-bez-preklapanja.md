# ADR 0008: eRačuni poslovi u redu, najviše 1 poziv u sekundi i bez preklapanja

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno)

## Context
Pozivi prema eRačuni API-ju su spori (timeout 60 s) i ograničeni brzinom.

## Decision
Pozivi idu kroz queue (driver `database`). `RateLimiter::for('eracuni')` dopušta 1 poziv u sekundi (`AppServiceProvider.php:36`). Poslovi koriste `WithoutOverlapping` i `RateLimited('eracuni')`, `tries` 5, `backoff` [10, 30, 60, 300].

## Alternatives Considered
Nisu zapisane. Limit od ~1 zahtjev/s dolazi iz dokumentacije dobavljača (iz iskustva projekta, nije u repozitoriju; vidi [06-eracuni.md](../tehnicka/06-eracuni.md)).

## Consequences
- Bez queue workera na produkciji poslovi se ne obrađuju ([11](../tehnicka/11-poznata-ogranicenja.md), nalaz 6).
