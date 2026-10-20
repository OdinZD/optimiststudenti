# Upute za trenere (izvor i izrada PDF-a)

Korisničke upute za trenere: `upute.md` je **jedini izvor teksta**, a `Upute-za-trenere.pdf` se iz njega izrađuje.
Piše se u obliku "ti", a nazivi gumba i polja pišu se u navodnicima, doslovno kao u aplikaciji.

## Kako promijeniti tekst i izraditi PDF

1. Uredi `docs/upute/upute.md`.
2. `php docs/upute/izradi-pdf.php` (treba Google Chrome; na drugom putu postavi `CHROME_BIN`).
3. Provjeri da svaki naziv u „…" postoji u kodu (naredba u zadnjem odlomku).

Markdown se pretvara u HTML (`predlozak.html`) pa u PDF preko headless Chromea. Koristi se `GithubFlavoredMarkdownConverter`
jer obični CommonMark ne podržava tablice. Font je sistemski (Helvetica/Arial), jer ugrađeni brand-fontovi nemaju č ć đ š ž.

## Kako osvježiti slike zaslona

Slike (`slike/*.png`) prave se **samo na probnoj bazi s izmišljenim podacima** (`optimist_upute`), nikad na stvarnoj.

```sh
# 1. probna baza (admin pristup MariaDB-u preko soketa)
mariadb -e "CREATE DATABASE optimist_upute CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
            GRANT ALL ON optimist_upute.* TO 'optimist'@'127.0.0.1'; GRANT ALL ON optimist_upute.* TO 'optimist'@'localhost';"
# 2. SIGURNOSNA PROVJERA prije migrate:fresh (briše sve tablice): konfiguracija se ne smije keširati, a baza mora biti probna
php artisan config:clear
DB_DATABASE=optimist_upute php artisan db:show | grep -q optimist_upute || { echo "NIJE probna baza, prekidam"; exit 1; }
DB_DATABASE=optimist_upute php artisan migrate:fresh --force
DB_DATABASE=optimist_upute php artisan db:seed --class=ReferenceSeeder --force
DB_DATABASE=optimist_upute php artisan tinker docs/upute/alati/seed-demo.php
# 3. dva servera na probnoj bazi (izravno, NE php artisan serve: ono prepisuje varijable okoline iz .env)
cd public
DB_DATABASE=optimist_upute FEATURE_PAYMENTS=false php -S 127.0.0.1:8780 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php &
DB_DATABASE=optimist_upute FEATURE_PAYMENTS=true  php -S 127.0.0.1:8781 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php &
cd ..
# 4. slike (osnovne sa isključenim Članarinama, slike Članarina s uključenim); CHROME_BIN po potrebi
npm i --no-save puppeteer-core        # nije deklariran u package.json
NODE_PATH=$PWD/node_modules node docs/upute/alati/slike.cjs
# 5. na kraju: zaustavi servere i obriši probnu bazu
pkill -f "php -S 127.0.0.1:878"; mariadb -e "DROP DATABASE optimist_upute;"
```

`seed-demo.php` odbija raditi ako baza nije `optimist_upute`. Imena su izmišljena (npr. "Ana Probni").
Slike se snimaju u prozoru širine 1100 px (datoteke imaju 2200 px zbog `deviceScaleFactor: 2`; prijava 700 px, mobitel 390 px), da tekst u PDF-u ostane čitljiv.

## Provjera točnosti naziva

Svaki tekst u „…" u `upute.md` mora postojati u `app/`, `resources/` ili `database/seeders/`:

```sh
python3 - <<'PY'
import re, subprocess
s = open('docs/upute/upute.md', encoding='utf-8').read()
q = sorted(set(re.findall(r'„([^“”"]+)["“”]', s)))
miss = [x.strip() for x in q if subprocess.run(['grep','-rqF','--',x.strip(),'app','resources','database/seeders']).returncode != 0]
print(len(q), 'naziva,', len(miss), 'promašaja', miss)
PY
```

## Stanje

Opisuje `feature/clanarine` (Članarine su iza `FEATURE_PAYMENTS`). Poglavlje 5 je označeno kao "još nije u funkciji";
kad se članarine uključe, treba ga ažurirati (izdavanje i slanje računa putem eRačuna, podsjetnici, uloge).
