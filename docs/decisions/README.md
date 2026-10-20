# Arhitekturne odluke (ADR)

Kratki zapisi važnih odluka u projektu (predložak: Status, Datum, Context, Decision, Alternatives Considered, Consequences). Većina je **zapisana naknadno** (2026-10-20) iz koda, SPEC-a i povijesti; gdje razlog nije bio zapisan, piše „nije zabilježen" i pitanje za vlasnika, a ne izmišljeno obrazloženje.

| # | Odluka | Razlog zapisan? |
|---|---|---|
| [0001](0001-livewire-class-komponente-i-tailwind.md) | Livewire 4 class komponente + Tailwind v4 | da (vlasnik) |
| [0002](0002-mariadb-na-produkciji.md) | MariaDB na produkciji | da (vlasnik) |
| [0003](0003-izracunato-ne-spremljeno.md) | Izvedeno se računa, ne sprema | da (vlasnik) |
| [0004](0004-novac-u-centima-bez-brisanja.md) | Novac u centima, storno, audit | ne |
| [0005](0005-feature-flag-clanarine.md) | Feature flag Članarine | da (kod) |
| [0006](0006-eracuni-izdaje-racune.md) | eRačuni izdaje račune; HUB-3 otkazan | da (vlasnik) |
| [0007](0007-eracuni-mock-i-write-guard.md) | Mock klijent + write guard | da (kod) |
| [0008](0008-queue-limiter-i-bez-preklapanja.md) | Queue limiter 1/s | djelomično |
| [0009](0009-vendor-i-build-u-gitu.md) | `vendor/` i `public/build` u gitu | da (deploy doc) |
| [0010](0010-grupe-many-to-many-termini-izvedeni.md) | Grupe many-to-many, termini izvedeni | da (razgovor) |
| [0011](0011-soft-delete-i-ponistavanje.md) | Soft delete + Poništi | da (SPEC) |
| [0012](0012-izjave-sifrirane-na-privatnom-disku.md) | Šifrirane izjave | da (kod) |
| [0013](0013-sezona-1-9-do-1-7.md) | Sezona 1.9.–1.7. | da (kod) |
| [0014](0014-jedan-klub-bez-tenant-id.md) | Jedan klub | da (vlasnik) |

Novi ADR: sljedeći broj, isti predložak, dodati red u tablicu.
