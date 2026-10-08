# Optimist — cPanel deployment runbook

Deploying **Optimist** (Laravel 13 + Livewire 4 + MariaDB, 3 trainers, ~79 students) to **cPanel shared hosting**. Follow top to bottom. Commands assume cPanel **Terminal** access; where it's missing, use File Manager + phpMyAdmin instead.

---

## 0. Host prerequisites (check first)

In cPanel:

- **PHP 8.3 or 8.4** selected for the domain (*MultiPHP Manager* or *Select PHP Version*).
- **Extensions enabled** (*Select PHP Version → Extensions*):
  - **`intl`** — critical. The app uses it for Croatian-correct sorting and search. Without it, sorting degrades (there is a fallback, but enable `intl`).
  - `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `ctype`, `fileinfo`, `tokenizer`, `curl`, `json`.
- **MariaDB** available (it is on cPanel).
- **AutoSSL / Let's Encrypt** available for HTTPS.
- **Composer** in Terminal (`composer --version`) — or plan to build `vendor/` locally.

> No Node.js on shared hosting — front-end assets are **built locally** and uploaded (step 4).

---

## 1. Create the database (cPanel → *MySQL® Databases*)

1. **Create database** — e.g. `acct_optimist` (cPanel prefixes your account name).
2. **Create user** — e.g. `acct_optimist` with a **strong** password.
3. **Add user to database** → grant **ALL PRIVILEGES**.
4. Note: DB name, user, password, host (usually `localhost`).

---

## 2. Upload the code

Put the project **outside** `public_html`, e.g. `/home/acct/optimist`.

- **Git** (if available): `git clone … ~/optimist`.
- **Upload**: zip locally (exclude `node_modules`; include `vendor` only if you won't run Composer on the host), upload to `~/optimist`, extract.

---

## 3. Point the domain at `/public`

Laravel must serve from `optimist/public`, never the project root.

- **Preferred:** create a **subdomain** (e.g. `optimist.tvojadomena.hr`) or addon domain and set its **Document Root** to `/home/acct/optimist/public`.
- **Primary-domain fallback** (docroot fixed to `public_html`): move the contents of `optimist/public/` into `public_html/`, then edit `public_html/index.php` so the two `require` paths point at `../optimist/vendor/autoload.php` and `../optimist/bootstrap/app.php`. The subdomain route is cleaner — prefer it.

---

## 4. Install dependencies

- **PHP (on host):**
  ```sh
  cd ~/optimist
  composer install --no-dev --optimize-autoloader
  ```
  No Composer on host? Run that **locally on PHP 8.3 with `intl`** and upload the resulting `vendor/`.
- **Assets (locally, then upload):**
  ```sh
  npm ci && npm run build     # local machine
  ```
  Upload `public/build/` to the server (it includes the self-hosted Bricolage/Hanken fonts). Do **not** run Vite on the server.

---

## 5. Configure `.env` (on the server)

Copy `.env.example` → `.env`, then set:

```ini
APP_NAME=Optimist
APP_ENV=production
APP_DEBUG=false
APP_URL=https://optimist.tvojadomena.hr
APP_KEY=                       # see below
APP_LOCALE=hr
APP_TIMEZONE=Europe/Zagreb

DB_CONNECTION=mariadb
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=acct_optimist
DB_USERNAME=acct_optimist
DB_PASSWORD=********
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log

OPTIMIST_TRAINER_EMAILS=odin.perica@gmail.com,frane.herenda@gmail.com,mandicmarija60@gmail.com
OPTIMIST_DEFAULT_PASSWORD=<STRONG shared password — not the dev one>
```

Generate the app key: `php artisan key:generate` on the server (or locally `php artisan key:generate --show` and paste the `base64:…` value). **Keep `APP_KEY` stable** — it encrypts sessions/cookies.

---

## 6. Migrate and seed

```sh
php artisan migrate --force
php artisan db:seed --class=TrainerSeeder --force      # the 3 trainer logins
```

Import the 79 existing students (**personal data of minors — handle carefully**):

1. Upload `database/seeders/data/polaznici.json` via **SFTP/File Manager** — **never git**.
2. `php artisan db:seed --class=ReferenceSeeder --force && php artisan db:seed --class=StudentSeeder --force`
3. **Delete `polaznici.json` from the server** afterward.

---

## 7. Optimize for production

```sh
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Re-run all three after **any** `.env` change (cached config ignores later edits).

---

## 8. Permissions

`storage/` and `bootstrap/cache/` must be writable:

```sh
chmod -R 775 storage bootstrap/cache
```

(On cPanel you own the files, so this is usually already fine.)

---

## 9. HTTPS & hardening

- Enable **AutoSSL** for the domain; turn on **Force HTTPS Redirect**.
- Confirm `APP_DEBUG=false` (never expose stack traces in production).
- `SESSION_SECURE_COOKIE=true` (set in step 5).
- Optional: add `X-Frame-Options: DENY` and a basic CSP via `public/.htaccess` or middleware.

---

## 10. GDPR / PII notes (children's data)

- Shared hosting = shared DB server, limited isolation. Keep the DB user scoped to this one database (cPanel does this by default).
- Enable **cPanel backups** and keep an **encrypted offsite copy**; test a restore.
- `polaznici.json` must never remain on the server or enter git.
- Define a **retention / erasure** policy. Soft-deleted students still hold PII in the table — add a purge step if your policy requires hard deletion.
- Ensure a lawful basis / parental consent for storing OIB + birth dates, and a privacy notice.

---

## Smoke test after deploy

- `https://…/login` loads over HTTPS, **no debug banner**.
- Log in as a trainer → `/polaznici` shows **79 students**, sorted (Anđić, Antišin, Ažić…).
- Create, edit, and delete a test student; **Poništi** (undo) restores it.

## Updating later

Upload/pull new code → `composer install --no-dev -o` → upload fresh `public/build` → `php artisan migrate --force` → re-run the three `*:cache` commands.
