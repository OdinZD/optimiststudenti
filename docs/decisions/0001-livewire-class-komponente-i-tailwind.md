# ADR 0001: Livewire 4 class komponente i Tailwind v4, bez starter kita

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno; odluka je starija)

## Context
Aplikacija je interni registar polaznika za jedan klub, bez zasebnog frontenda. Dizajn je opisan u `docs/design/SPEC.md`.

## Decision
UI se gradi kao Livewire 4 *class* komponente (`app/Livewire`) s Blade pogledima i Tailwind v4 (tokeni u `resources/css/tokens.css`). Ne koristi se Laravel starter kit (Breeze/Jetstream); prijava je vlastita komponenta `Auth/Login`.

## Alternatives Considered
Razlog odabira i razmatrane alternative **nisu zabilježeni** u repozitoriju. [Pitanje za vlasnika: zašto Livewire umjesto SPA-a / Inertije?]

## Consequences
- Sva logika ekrana je na poslužitelju; svaka javna metoda komponente je dostupna iz preglednika (autorizacija mora biti u metodi), vidi [07](../tehnicka/07-autorizacija-i-sigurnost.md).
- Build frontenda (Vite) ide u `public/build` koji je u gitu (ADR [0009](0009-vendor-i-build-u-gitu.md)).
