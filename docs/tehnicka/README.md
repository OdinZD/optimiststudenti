# Tehnička dokumentacija (za programera)

**Izvor istine:** kod u ovom repozitoriju; svaki dokument navodi svoje datoteke u zaglavlju.
**Stanje:** `feature/clanarine` @ 87307b0, 2026-10-20

Cilj: razumjeti što se događa u aplikaciji i kako — svaki ekran, gumb, poziv, odgovor, tablicu i vanjsku vezu. Dokumentacija opisuje **kod kakav jest**; što nije moglo biti provjereno, označeno je s `[NEJASNO: …]`, a nedostaci su u [11-poznata-ogranicenja.md](11-poznata-ogranicenja.md) (nisu popravljani).

## Kako čitati

| Ako želiš … | čitaj |
|---|---|
| Pregled cjeline i slojeva | [01-arhitektura.md](01-arhitektura.md) |
| Shemu i ER dijagrame, rječnik podataka | [02-baza-podataka.md](02-baza-podataka.md) |
| Rute, Livewire zahtjeve, događaje, primjer sirovog zahtjeva | [03-http-i-livewire.md](03-http-i-livewire.md) |
| Što radi pojedini gumb (metoda → baza → odgovor) | [04-ekrani/](04-ekrani/): [prijava](04-ekrani/prijava.md), [polaznici](04-ekrani/polaznici.md), [natjecanja](04-ekrani/natjecanja.md), [popisi natjecanja](04-ekrani/popisi-natjecanja.md), [kata timovi](04-ekrani/kata-timovi.md), [članarine](04-ekrani/clanarine.md) |
| Domenska pravila i izvozi (`app/Support`) | [05-domena-i-servisi.md](05-domena-i-servisi.md) |
| eRačuni API i poslovi u redu | [06-eracuni.md](06-eracuni.md) |
| Uloge, gateove, gdje se što provodi | [07-autorizacija-i-sigurnost.md](07-autorizacija-i-sigurnost.md) |
| Sve env varijable | [08-konfiguracija.md](08-konfiguracija.md) |
| Lokalni rad, testovi, „kako dodati …", zamke | [09-razvoj-i-testovi.md](09-razvoj-i-testovi.md) |
| Izdanje na cPanel, SQL skripte, povrat | [10-deploy-i-operacije.md](10-deploy-i-operacije.md) |
| Zašto je nešto tako napravljeno | [../decisions/](../decisions/README.md) (ADR) |
| Što je poznato kao problem | [11-poznata-ogranicenja.md](11-poznata-ogranicenja.md) |

Korisničke upute za trenere su zasebno u `docs/upute/`.

## Konvencije

- Tekst je hrvatski, identifikatori (klase, metode, tablice, stupci, rute, događaji) su doslovno kao u kodu, u `kodu`. Natpisi u sučelju su u navodnicima „…".
- Reference na kod: `putanja:redak`. Brojevi redaka stare s kodom; provjerava ih skripta.
- Dijagrami su Mermaid (renderira ih GitHub i većina uređivača).
- U dokumentaciji **nema** vrijednosti iz `.env`, adresa e-pošte ni stvarnih imena; primjeri su izmišljeni.

## Održavanje

Dokumentacija se provjerava protiv koda skriptama u [alati/](alati/):

```bash
php docs/tehnicka/alati/provjeri.php
MERMAID_DIR=/tmp/mermaid-provjera node docs/tehnicka/alati/mermaid.cjs
```

Jedan PDF cijele dokumentacije (s Mermaid dijagramima; zahtijeva iste privremene alate kao `mermaid.cjs`):

```bash
php docs/tehnicka/alati/izradi-pdf.php   # izlaz: docs/tehnicka/Tehnicka-dokumentacija.pdf (nije u gitu, ~11 MB)
```

Pravilo: kad se mijenja shema, ruta, javna metoda Livewire komponente, env varijabla ili događaj, ažuriraj pripadni dokument i pokreni provjeru. Odluke koje mijenjaju smjer dodaju se kao novi ADR.

## Rječnik

| Pojam | Značenje |
|---|---|
| polaznik / dijete | zapis u `students` (karate član) |
| grupa (`training_groups`) | skupina po razini/disciplini (npr. Početnici); dijete može biti u više grupa |
| termin (`training_slots`) | lokacija + dani + vrijeme održavanja; izvodi se iz grupa |
| lokacija (`locations`) | dvorana/škola gdje se trenira |
| natjecanje (`competitions`) | događaj; rezultati djeteta su u `competition_results` |
| popis natjecanja / roster (`competition_rosters`) | popis djece koja se prijavljuju na natjecanje, s disciplinom i kategorijom |
| kata tim (`kata_teams`) | momčad za disciplinu kata |
| izjava (`declarations`) | roditeljska izjava (datoteka, šifrirana) |
| tarifa (`fee_plans`) | cjenik članarine |
| zaduženje (`charges`) | obveza plaćanja za razdoblje |
| uplata (`payments`) / alokacija (`payment_allocations`) | primljeni novac i raspodjela po zaduženjima |
| storno | poništenje uplate negativnim zrcalnim zapisom |
| otpis (write-off) | zaduženje se ne naplaćuje |
| sezona | 1. 9. – 1. 7. ([ADR 0013](../decisions/0013-sezona-1-9-do-1-7.md)) |
| flag / Članarine | modul iza `FEATURE_PAYMENTS` |
