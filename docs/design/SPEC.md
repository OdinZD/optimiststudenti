# Optimist – specifikacija aplikacije za evidenciju polaznika

Izvor: interaktivni dizajn prototip (7.10.2026.) i tablica `OPTIMIST_FINAL_TABLICA_TRENERI.xlsx`.
Slike ekrana: `docs/design/screens/`. Tokeni: `docs/design/tokens.css`.

---

## 1. Cilj i opseg

Treneri vode polaznike kluba u web aplikaciji umjesto u Excelu. Ništa se ne računa formulama u ćelijama:
uzrasna kategorija i datum prelaska računaju se automatski.

**V1 uključuje:** prijavu trenera, popis polaznika s pretragom i filtrima, dodavanje, uređivanje,
brisanje s poništavanjem, uvoz 79 postojećih polaznika.

**Izvan opsega v1:** raspored treninga, kata timovi, članarine, izvoz, ovlasti po treneru,
dan stupnjevi (crni pojas). Model neka ih ne priječi.

---

## 2. Ekrani

| Datoteka | Ekran |
|---|---|
| `01-popis.png` | Popis polaznika (desktop 1440 px) |
| `02-uredivanje.png` | Drawer „Uredi polaznika“ preko popisa (cijela forma) |
| `03-novi-polaznik.png` | Drawer „Novi polaznik“, prazna forma |
| `04-brisanje.png` | Dijalog potvrde brisanja |
| `05-mobilni-popis.png` | Popis na mobitelu (390 px): kartice umjesto tablice |
| `06-mobilna-forma.png` | Forma na mobitelu, cijela visina |
| `07-filtri-aktivni.png` | Aktivni filtri: U10 + „Liječnički istekao“ |
| `08-validacija.png` | Greške validacije nakon pokušaja spremanja |

---

## 3. Model podataka

### 3.1 `locations` (lokacija / termin)
| stupac | tip | napomena |
|---|---|---|
| id | bigint PK | |
| slug | string unique | |
| name | string | prikazni naziv |

Seed: `os-stanovi` „OŠ Stanovi“, `visnjik-cetvrtak` „Višnjik · četvrtak“, `visnjik-pon-sri` „Višnjik · pon. i sri., 20–21 h“.

### 3.2 `training_groups` (grupa)
| stupac | tip | napomena |
|---|---|---|
| id | bigint PK | |
| slug | string unique | |
| name | string | |
| description | text null | iz sheeta „raspored treninga“; za sada se ne prikazuje |

Seed (iz JSON-a): Početna (Odin), Početna + borci (Odin), Kate, Kate i borci, Borci stariji slabije kate.

### 3.3 `students`
| stupac | tip | pravila |
|---|---|---|
| id | bigint PK | |
| last_name | string(100) | obavezno |
| first_name | string(100) | obavezno |
| birth_date | date null | ne u budućnosti |
| oib | char(11) null, unique | 11 znamenki + kontrolna znamenka (ISO 7064, MOD 11,10) |
| location_id | FK null → locations, nullOnDelete | |
| training_group_id | FK null → training_groups, nullOnDelete | |
| belt_kyu | tinyint null | `null` = nije uneseno, `0` = bez pojasa, `1–9` = kyu |
| next_grade_status | string(20), default `none` | `none` / `to_register` / `registered` |
| next_grade_kyu | tinyint null | obavezno i 1–9 kad je status `registered`, inače `null` |
| medical_valid_until | date null | mora biti `null` kad je `has_no_medical = true` |
| has_no_medical | boolean default false | |
| parent_name | string(150) null | roditelj / skrbnik |
| parent_phone | string(30) null | sprema se kako je uneseno (trim) |
| parent_email | string null | valjani e-mail |
| note | text null | max 2000 znakova |
| flag_t | boolean default false | oznaka „t“ iz Excela, značenje još nepoznato (§10) |
| timestamps, deleted_at | | **SoftDeletes** |

Indeksi: `(last_name, first_name)`, `birth_date`, `medical_valid_until`.

### 3.4 Enumi (PHP backed enums)

**Belt** (`belt_kyu`): labela i boja trake (vidi `tokens.css`):

| kyu | labela | traka |
|---|---|---|
| 9 | 9. kyu · bijeložuti | pola #FFFFFF / pola #F2D13B |
| 8 | 8. kyu · žuti | #F2C230 |
| 7 | 7. kyu · narančasti | #EE8A2B |
| 6 | 6. kyu · crveni | #D0342C |
| 5 | 5. kyu · zeleni | #3C9A4B |
| 4 | 4. kyu · plavi | #2F6FD0 |
| 3 | 3. kyu · ljubičasti | #7A4BB5 |
| 2 | 2. kyu · smeđi | #7A4A2B |
| 1 | 1. kyu · smeđe crni | pola #7A4A2B / pola #14171F |
| 0 | Bez pojasa | bijela, rub 1px solid #9AA2B2 |
| null | Nije uneseno (tekst u --ink3) | bijela, rub 1px dashed #9AA2B2 |

Traka: 30×10 px, radius 3 px, rub `1px solid rgba(20,23,31,.3)` (osim 0/null).

**NextGradeStatus** (u sučelju „Upis sljedećeg stupnja“):
| vrijednost | gumb u formi | oznaka u popisu |
|---|---|---|
| none | Nije potrebno | – |
| to_register | Treba upisati | „Treba upisati“ (žuta oznaka) |
| registered | Već upisan + select „Upisan na“ N. kyu | „Upisan: N. kyu“ (indigo oznaka) |

---

## 4. Poslovna pravila

### 4.1 Uzrasna kategorija (WKF/HKS) i prelazak u višu, NE sprema se
Dob = pune godine na današnji datum (rođendan danas = već ima tu dob).
Prelazak = datum rođenja + N mjeseci, s „no overflow“ ponašanjem kao Excelov `EDATE` (29.2. → 28.2. u neprijestupnoj godini).
Logika je preslikana iz Excel formule. Na 55 polaznika iz bloka OŠ Stanovi podudara se 55/55.

| dob | kategorija | prelazak (mjeseci od rođenja) |
|---|---|---|
| < 6 | Ispod U8 | 72 |
| < 8 | U08 · Cicibani | 96 |
| < 10 | U10 · Mlađi učenici | 120 |
| < 12 | U12 · Učenici | 144 |
| < 14 | U14 · Mlađi kadeti | 168 |
| < 16 | U16 · Kadeti | 192 |
| < 18 | U18 · Juniori | 216 |
| < 21 | U21 | 252 |
| ≥ 21 | Seniori | nema (prikaz „—“) |
| bez datuma rođenja | „—“ (filter „Bez datuma rođenja“) | „—“ |

Napomena: `diffInYears` u Carbonu 3 vraća float. Dob računaj eksplicitno kao cijeli broj.

**Testovi (danas = 2026-10-07):**
| rođen | dob | kategorija | prelazak |
|---|---|---|---|
| 2020-10-08 | 5 | Ispod U8 | 2026-10-08 |
| 2020-10-07 | 6 | U08 · Cicibani | 2028-10-07 |
| 2018-10-13 | 7 | U08 · Cicibani | 2026-10-13 |
| 2017-10-10 | 8 | U10 · Mlađi učenici | 2027-10-10 |
| 2015-03-05 | 11 | U12 · Učenici | 2027-03-05 |
| 2013-12-30 | 12 | U14 · Mlađi kadeti | 2027-12-30 |
| 2011-05-07 | 15 | U16 · Kadeti | 2027-05-07 |
| 2008-02-29 | 18 | U21 | 2029-02-28 |
| 2003-06-19 | 23 | Seniori | — |

### 4.2 Status liječničkog pregleda (računa se)
| uvjet | status | pozadina / tekst |
|---|---|---|
| has_no_medical | Nema | #EEF0F4 / #454C5E |
| datum null | Nepoznato | #EEF0F4 / #454C5E |
| datum < danas | Istekao | #FBE9E7 / #9F1F17 |
| 0–30 dana do datuma | Ističe uskoro | #FFF1CC / #7A4F00 |
| > 30 dana | Vrijedi | #E3F3E8 / #1B5E32 |

Datum „vrijedi do“ uključuje taj dan. **Testovi (danas = 2026-10-07):** 2026-10-06 → Istekao; 2026-10-07 → Ističe uskoro;
2026-11-06 → Ističe uskoro; 2026-11-07 → Vrijedi; 2026-07-14 → Istekao.
Ispod oznake u popisu: „do DD.MM.YYYY.“ (za Istekao samo datum).

### 4.3 Pretraga
Jedno polje, pretražuje prezime, ime, roditelja, e-mail, mobitel i OIB. Ne razlikuje velika i mala slova ni dijakritike:
č, ć, š, ž → c, s, z; đ → d. „kardum“ vraća 3 polaznika.

### 4.4 Sortiranje
Prezime, pa ime, po hrvatskoj abecedi (C < Č < Ć, D < Đ, S < Š, Z < Ž).
U PostgreSQL-u ICU kolacija za hrvatski ili sortiranje u PHP-u s `Collator('hr_HR')` (ext-intl).

### 4.5 Filtri
- Svi filtri se kombiniraju (AND) i čuvaju u URL-u: `?q=&uzrast=u10&lokacija=os-stanovi&grupa=kate&pojas=6&istekao=1&upisati=1`.
- **Chipovi uzrasta:** „Svi“, pa kategorije koje postoje u podacima (redoslijed iz §4.1), pa „Bez datuma rođenja“ ako takvih ima.
  Broj na chipu = broj polaznika te kategorije uz SVE OSTALE aktivne filtre (bez filtra uzrasta).
- **Selecti:** Sve lokacije / Sve grupe / Svi pojasevi (opcije iz šifrarnika i enuma Belt).
- **Chipovi pažnje** (toggle, `aria-pressed`): „Liječnički istekao“ (status Istekao) i „Treba upisati“ (`to_register`).
  Broj na njima je ukupan, ne ovisi o filtrima.
- „Poništi filtre“ se pojavljuje kad je bilo koji filtar aktivan.
- Podnaslov: bez filtara „79 upisanih polaznika“, s filtrima „Prikazano X od Y“.

### 4.6 Validacija i poruke
| polje | poruka |
|---|---|
| prezime, ime prazno | Obavezno polje |
| OIB nije 11 znamenki ili ne prolazi kontrolnu znamenku | OIB mora imati 11 znamenki / OIB nije ispravan |
| OIB već postoji | Polaznik s ovim OIB-om već postoji |
| e-mail neispravan | Provjeri e-mail adresu |
| datum rođenja u budućnosti | Datum rođenja ne može biti u budućnosti |

Greške se prikazuju tek nakon prvog pokušaja spremanja, ispod polja (crveni rub inputa #B3261E, tekst 13px/500 u --danger).
U podnožju forme se tada prikazuje „Provjeri označena polja“ (`role="alert"`). Greška nestaje čim se polje ispravi.

### 4.7 Brisanje i poništavanje
1. Ikonica kante u retku ili „Obriši polaznika“ u formi otvara dijalog potvrde.
2. „Da, obriši“ radi soft delete, zatvara dijalog i formu i prikazuje toast „Obrisano: Prezime Ime“ s gumbom „Poništi“.
3. „Poništi“ vraća zapis (restore). Toast nestaje nakon 7 s.

Spremanje prikazuje toast „Spremljeno: Prezime Ime“ (bez „Poništi“).

### 4.8 Zadane vrijednosti u novoj formi
Lokacija = trenutno filtrirana lokacija, inače OŠ Stanovi. Upis = Nije potrebno.
Klik na „Već upisan“ predlaže (trenutni kyu − 1), najmanje 1. Bez pojasa ili bez unosa predlaže 9. kyu.

---

## 5. Sučelje

Sav tekst na hrvatskom, točno ovako. `lang="hr"`.

### 5.1 Okvir stranice
- Gornja traka: 60 px, bijela, donji rub --line. Logo: indigo kvadrat 28 px (radius 8) s bijelim prstenom 12 px, uz njega „Optimist“ (display 20/700).
- Sadržaj: max-width 1344 px, padding 28 px gore, 32 px sa strane. Na desktopu stranica zauzima visinu prozora i skrola se samo tablica (zaglavlje tablice je sticky).
- Zaglavlje: H1 „Polaznici“ (display 34/600, -0.02em) + podnaslov (15px, --ink2). Desno primarni gumb „+ Novi polaznik“.
- Red chipova uzrasta (visina 40, radius 999, 14/600; neaktivan: bijeli s rubom --line2; aktivan: --acc s bijelim tekstom; broj s opacity .8). Vodoravni skrol ako ne stane.
- Alatna traka (wrap, gap 12): pretraga s ikonom (placeholder „Ime, roditelj, OIB…“, max 300 px), 3 selecta (max 190 px), 2 chipa pažnje s obojenom točkom, „Poništi filtre“ (link-gumb u akcentu).

### 5.2 Tablica (desktop)
Bijela kartica, radius 12, rub --line. Zaglavlja 12/600, uppercase, letter-spacing .05em, --ink3. Ćelije padding 12/16, donji rub --line. Hover retka #F7F8FB, klik na redak otvara uređivanje.

| stupac | širina | sadržaj |
|---|---|---|
| Polaznik | 250 | „Prezime Ime“ (15/600, gumb) + oznaka „t“ ako je flag_t; ispod lokacija (13, --ink3) |
| Pojas | 210 | traka + labela; ispod oznaka upisa ako postoji |
| Grupa | 180 | naziv ili „—“ (--ink3) |
| Uzrast | 190 | kategorija (500); ispod „DD.MM.YYYY. · N g.“ ili „Nema datuma rođenja“ |
| Liječnički | 150 | statusna oznaka (pill s točkom) + datum ispod |
| Roditelj / kontakt | 200 | roditelj, ispod mobitel; bez roditelja samo mobitel; bez ičega „—“ |
| (Radnje) | 104 | ikonice Uredi (olovka, --ink2) i Obriši (kanta, --danger), 44×44, `aria-label="Uredi: Prezime Ime"` |

Prikaz mobitela: 10 znamenki kao „091 234 5678“.
Prazno stanje: „Nema polaznika za zadane filtre“ (display 20/600), „Pokušaj s drugim pojmom ili makni neki filter.“ i gumb „Poništi filtre“.

### 5.3 Mobitel (≤ 760 px)
Tablica se skriva, prikazuju se kartice (bijele, radius 12): ime i lokacija; desno ikonice Uredi/Obriši; red s pojasom i oznakom upisa;
red s uzrastom i oznakom „Liječnički: Status“. Cijela stranica se skrola. Selecti se šire na punu širinu retka.

### 5.4 Forma (drawer)
Desni drawer, širina min(680 px, 100%), cijela visina, overlay rgba(20,23,31,.45). Klik na overlay ili Esc zatvara.
Zaglavlje: „Novi polaznik“ / „Uredi polaznika“ (display 24/600) + gumb X. Tijelo se skrola. Podnožje je fiksno.

Sekcije (naslov display 16/600, razdjelnik --line između sekcija). Polja u gridu
`repeat(auto-fit, minmax(min(240px,100%), 1fr))`, gap 16. Labela 13/600 --ink2 iznad inputa. Input 44 px, radius 10, rub --line2.

1. **Osobni podaci**: Prezime *, Ime *, Datum rođenja, OIB (placeholder „11 znamenki“).
   Ispod je info-okvir (pozadina --bg, radius 10): „UZRASTNA KATEGORIJA“ i „PRELAZI U VIŠU KATEGORIJU“ (12/600 uppercase --ink3) s vrijednostima (600),
   te „Računa se automatski iz datuma rođenja (WKF/HKS), ne unosi se ručno.“ Vrijednosti se ažuriraju odmah pri promjeni datuma.
2. **Karate**: Pojas (traka uživo lijevo od selecta; opcije „— nije uneseno“, 9…1 kyu, „Bez pojasa“), Grupa („— bez grupe“ + grupe),
   Lokacija / termin (puna širina; „— nije odabrano“ + lokacije),
   „Upis sljedećeg stupnja“: segmentirani izbor od 3 jednaka gumba (Nije potrebno / Treba upisati / Već upisan), max 400 px, aktivan u --acc.
   Uz „Već upisan“ pojavi se select „Upisan na“ (1.–9. kyu).
3. **Liječnički pregled**: „Vrijedi do“ (date) + statusna oznaka uživo; checkbox „Nema liječnički pregled“ (onemogućuje i prazni datum).
4. **Roditelj / kontakt**: Roditelj / skrbnik, Mobitel (placeholder „091 234 5678“), E-mail (puna širina).
5. **Ostalo**: Napomena (textarea, 3 reda), checkbox „Oznaka „t““.

Podnožje: lijevo „Obriši polaznika“ (samo kod uređivanja; bijeli, rub #E5B3AE, tekst --danger, ikona kante), desno „Odustani“ (sekundarni) i
„Dodaj polaznika“ / „Spremi promjene“ (primarni). Na mobitelu: red [Odustani][Spremi], ispod „Obriši polaznika“ preko cijele širine.

### 5.5 Dijalog brisanja
Overlay rgba(20,23,31,.55), kartica 440 px, radius 16, padding 24, `role="alertdialog"`. Ikona kante u krugu 44 px (#FBE9E7).
Naslov „Obrisati polaznika?“ (display 22/600). Tekst: „Zapis **Prezime Ime** bit će uklonjen s popisa. Brisanje možeš poništiti samo odmah nakon toga.“
Gumbi: „Odustani“ (sekundarni), „Da, obriši“ (pozadina --danger, bijeli tekst). Esc zatvara.

### 5.6 Toast
Dolje po sredini, 24 px od ruba, pozadina --ink, bijeli tekst 14/500, radius 12, `role="status"`. „Poništi“ je podcrtan bijeli gumb 44 px.

### 5.7 Stanja
Primarni gumb hover: svjetliji (brightness 1.15). Sekundarni hover: --bg. Ikonica Uredi hover: --tint. Obriši hover: #FBE9E7.
Fokus: `outline: 2px solid var(--acc); outline-offset: 2px` na svim interaktivnim elementima.

---

## 6. Tipografija i fontovi
- Naslovi i logo: **Bricolage Grotesque** 600/700. Tekst i kontrole: **Hanken Grotesk** 400/500/600/700. Fallback `system-ui, sans-serif`.
- Na slikama u `screens/` je zamjenski sistemski font, jer fontovi nisu bili dostupni pri snimanju. Mjerodavni su ovi nazivi.
- Fontove hostaj lokalno (npr. preuzeti woff2 u `public/fonts` ili npm paket), bez poziva Google Fonts CDN-a.

---

## 7. Pristupačnost
Pravi `<button>`, `<a>`, `<input>` s `<label>`; ikonice imaju `aria-label`. Chipovi koriste `aria-pressed`.
Drawer je `role="dialog" aria-modal="true"` s `aria-labelledby`. Kontrast teksta je najmanje 4.5:1 (boje u tokenima su provjerene).
Status se nikad ne prikazuje samo bojom: uvijek ima i tekst. Ciljna površina klika je najmanje 44 px.

---

## 8. Uvoz podataka
`database/seeders/data/polaznici.json`: `locations`, `training_groups`, `students` (79), stupci kao u §3 (lokacija i grupa kao slug).
Seeder neka bude idempotentan (`updateOrCreate`; ključ je OIB, a bez OIB-a prezime + ime + lokacija) i u jednoj transakciji.

Što je napravljeno pri normalizaciji Excela:
- Sufiks „ t“ iz imena prebačen je u `flag_t` (18 polaznika).
- „(upisan N)“ / „(N. kyu upisan)“ → `registered` + N (11 polaznika); „(upisati!!!)“ → `to_register` (4 polaznika).
- „nema“ u liječničkom → `has_no_medical`; „?“ → prazno.
- OIB-ovima s 10 znamenki vraćena je vodeća nula. Sva 62 OIB-a prolaze kontrolnu znamenku.
  Mobitelima s 9 znamenki dodana je vodeća 0.
- Grupe Višnjik: pojas uglavnom nepoznat (`null`). 15 polaznika nema datum rođenja.
  Bilješke bez zaglavlja („upisninu platio“, „9 godina · 40“, „20“…) prebačene su u `note`.
- Unos oblika „Prezime (mama Prezime2)“ → prezime je „Prezime“, a roditelj/skrbnik ide u `parent_name`.
- Nejasan unos pojasa tipa „9.kyu?? bijeložuti“ → odabrani kyu + napomena da je vrijednost nesigurna.

---

## 9. Prihvatni kriteriji (uz seed i datum 7.10.2026.)
- [ ] Popis prikazuje 79 polaznika, sortirano po hrvatskoj abecedi (prvi: Anđić Laura, Antišin Tonka, Ažić Korina…).
- [ ] Chipovi: Svi 79 · U08 7 · U10 14 · U12 20 · U14 14 · U16 4 · Seniori 5 · Bez datuma rođenja 15.
- [ ] „Liječnički istekao“ 28, „Treba upisati“ 4.
- [ ] „kardum“ → 3 rezultata; pojas 6. kyu → 19; U12 → 20.
- [ ] Novi polaznik bez imena i prezimena → 2× „Obavezno polje“ + „Provjeri označena polja“.
- [ ] Unos datuma rođenja 2013-12-30 odmah prikazuje „U14 · Mlađi kadeti“ i „30.12.2027.“.
- [ ] Brisanje → toast → „Poništi“ vraća polaznika (broj se vraća na isti).
- [ ] Na 390 px širine nema vodoravnog skrola stranice; prikazuju se kartice; forma je preko cijelog ekrana.
- [ ] Testovi za §4.1 i §4.2 prolaze.

---

## 10. Otvorena pitanja (za trenere)
1. Što znači oznaka „t“ uz ime? Kad se zna, preimenovati `flag_t` i labelu.
2. Jesu li „upisan N“ / „upisati!!!“ stvarno upis na sljedeće polaganje (ili upis u knjižicu / registar HKS-a)? Prilagoditi labele.
3. Što su brojke 20 / 40 u grupama na Višnjiku (članarina?). Ako je članarina, to je posebno polje.
4. Mapiranje opisa grupa iz rasporeda („početnici (19)“ ↔ „Početna (Odin)“, a u tablici ih je 18) treba potvrditi.
