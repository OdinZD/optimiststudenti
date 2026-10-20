# ADR 0006: eRačuni izdaje račune; HUB-3 (uplatnice) se ne koristi

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno)

## Context
Članarine se naplaćuju roditeljima. Račune izdaje vanjski sustav eRačuni (fiskalizacija).

## Decision
Optimist ne izdaje račune sam: zaduženja se prenose u eRačuni, a Optimist prati uplate. Polja za HUB-3 postoje u postavkama, ali su označena „nekorišteno" (`app/Livewire/ClubBillingSettingsForm.php:42`).

## Alternatives Considered
Generiranje HUB-3 uplatnica u aplikaciji — otkazano. Točan razlog otkazivanja nije zabilježen u repozitoriju (commit `556259a` „usklađivanje s eRačuni smjerom" potvrđuje promjenu smjera, ne i razlog). [Pitanje za vlasnika: zašto je HUB-3 otkazan?]

## Consequences
- Ovisnost o eRačuni Premium API-ju; modul je na čekanju dok on ne proradi ([06-eracuni.md](../tehnicka/06-eracuni.md)).
