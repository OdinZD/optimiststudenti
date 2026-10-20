# ADR 0004: Novac u centima, bez brisanja novčanih zapisa, audit zapis

**Status:** Accepted
**Datum:** 2026-10-20 (zapisano naknadno)

## Context
Članarine bilježe zaduženja i uplate (`charges`, `payments`, `payment_allocations`).

## Decision
- Iznosi su cijeli brojevi u centima (`app/Support/Money.php`, stupci `*_cents`).
- Novčani zapisi se ne brišu: uplata se poništava *storno* zapisom (negativna zrcalna uplata i alokacije, `PaymentRecorder::reverse`), a zaduženje se otpisuje (*write-off*).
- Promjene se bilježe u `audit_logs` (`Auditer`, samo dodavanje).
- Alokacija uplata je u DB transakciji (FIFO ili ručni odabir; preplata je dopuštena).

## Alternatives Considered
Nisu zapisane. Odluka prati uobičajenu računovodstvenu praksu (nepromjenjiv trag); izričit razlog nije u repozitoriju.

## Consequences
- Status zaduženja je izveden iz alokacija (ADR [0003](0003-izracunato-ne-spremljeno.md)).
- Ispravak pogreške znači novi zapis, ne izmjenu.
- Pojedinosti: [05-domena-i-servisi.md](../tehnicka/05-domena-i-servisi.md).
