# ADR 0006: eRačuni izdaje račune; HUB-3 (uplatnice) se ne koristi

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno)

## Context
Članarine se naplaćuju roditeljima. Račune izdaje vanjski sustav eRačuni (fiskalizacija).

## Decision
Optimist ne izdaje račune sam: zaduženja se prenose u eRačuni, a Optimist prati uplate. Polja za HUB-3 postoje u postavkama, ali su označena „nekorišteno" (`app/Livewire/ClubBillingSettingsForm.php:42`).

## Alternatives Considered
Generiranje HUB-3 uplatnica u aplikaciji — otkazano jer se aplikacija spaja na sustav eRačuni, a njegov PDF račun već sadrži barkod za plaćanje, pa je vlastita uplatnica suvišna (izvor: vlasnik, 2026-10-20; promjenu smjera potvrđuje commit `556259a`).

## Consequences
- Ovisnost o eRačuni Premium API-ju; modul je na čekanju dok on ne proradi ([06-eracuni.md](../tehnicka/06-eracuni.md)).
