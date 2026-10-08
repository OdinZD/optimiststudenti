# Deploy to cPanel without Terminal (git pull + phpMyAdmin)

This is the flow actually used to deploy Optimist on a shared cPanel account with **no SSH/Terminal access**
(so no `composer`, `artisan`, `migrate`, or `npm` on the server). Everything is prepared in the repo and
loaded through the cPanel UI. Replace `<cpanel_user>` with your account name (home dir `/home/<cpanel_user>`).

## Why this works without a terminal

The repo is set up so a plain `git pull` delivers a **runnable** app:

- `vendor/` **and** `public/build/` are committed (normally gitignored) — no `composer install` / `npm` needed on the host.
- Composer is pinned to **PHP 8.3** (`config.platform.php` in `composer.json`), so Symfony resolves to the 7.4 LTS line that runs on 8.3.
- The database is shipped as a SQL dump and imported via **phpMyAdmin** (replaces `migrate`/`seed`).

The repo must be **public** (or you add a deploy key) so cPanel's Git tool can clone it without shell access.

## Host prerequisites (cPanel → Software → Select PHP Version)

- **PHP 8.3** (or newer).
- Extensions: **`intl`** (required — Croatian sort/search), **`nd_pdo_mysql`** (the native-driver PDO MySQL;
  if enabling plain `pdo_mysql` shows a "conflict", it's because `nd_pdo_mysql` is already active — that's fine),
  `mbstring`, `openssl`, `bcmath`, `ctype`, `fileinfo`, `tokenizer`, `curl`.

---

## 1. Clone the repo (cPanel → Git™ Version Control)

- **Create** → paste the repo URL (`https://github.com/<you>/optimiststudenti.git`) → clone.
- It lands at `/home/<cpanel_user>/repositories/optimiststudenti`.
- Future updates: **Manage → Pull or Deploy → Update from Remote**.

## 2. Database (cPanel → MySQL® Databases, then phpMyAdmin)

1. Create a **database** and a **user**, add the user to the DB with **ALL PRIVILEGES**.
   Use a password with **letters and numbers only** (symbols like `$ # "` break `.env` parsing).
2. **phpMyAdmin** → select that database → **Import** → upload the `optimist-db.sql` dump → **Go**.
   It creates all tables incl. `students` (79) and `users` (the 3 trainers).
3. The SQL dump holds children's personal data — **delete it after import** and never commit it.

> Note: cPanel usually prefixes names (`<cpanel_user>_optimist`). Whatever the exact names are, they must match `.env` in step 3.

## 3. Create `.env` (File Manager)

File Manager → `repositories/optimiststudenti` → enable **Settings → Show Hidden Files** → create **`.env`**
in the app root (NOT in `public_html`). Fill it in:

```ini
APP_NAME=Optimist
APP_ENV=production
APP_KEY=                                  # generate locally: php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://your-domain
APP_LOCALE=hr
APP_TIMEZONE=Europe/Zagreb
LOG_LEVEL=error

DB_CONNECTION=mariadb
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<exact db name>
DB_USERNAME=<exact db user>
DB_PASSWORD=<that user's password>
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log

OPTIMIST_TRAINER_EMAILS=a@example.com,b@example.com,c@example.com
OPTIMIST_DEFAULT_PASSWORD=<only needed if you re-seed; the trainers are already in the DB dump>
```

## 4. Point the website at the app's `public/`

**Best case:** cPanel → **Domains → Manage** → set **Document Root** to
`/home/<cpanel_user>/repositories/optimiststudenti/public`.

**If the document root is locked** (common for the main domain, stuck at `public_html`) — serve from `public_html`:

1. File Manager → copy **all contents** of `repositories/optimiststudenti/public/` into `public_html/`
   (`index.php`, `.htaccess`, `favicon.ico`, `robots.txt`, `build/`). Remove any default `index.html`.
2. Edit **`public_html/index.php`** and point it back at the app folder:

   ```php
   <?php

   use Illuminate\Foundation\Application;
   use Illuminate\Http\Request;

   define('LARAVEL_START', microtime(true));

   $base = '/home/<cpanel_user>/repositories/optimiststudenti';

   if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
       require $maintenance;
   }

   require $base.'/vendor/autoload.php';

   /** @var Application $app */
   $app = require_once $base.'/bootstrap/app.php';

   $app->handleRequest(Request::capture());
   ```

3. If assets ever change, re-copy `repositories/optimiststudenti/public/build` into `public_html/build`.
   If **Force HTTPS Redirect** stops working after copying `.htaccess`, toggle it off→on in the Domains list.

## 5. HTTPS

cPanel → **SSL/TLS Status → Run AutoSSL**, and keep **Force HTTPS Redirect** on.
Login only works over HTTPS (`SESSION_SECURE_COOKIE=true`), so test on `https://`.

## 6. Permissions

Not needed on this host — PHP runs as your account user, and the git-checked-out `storage/` and
`bootstrap/cache/` are `0755` (owner-writable). Only if you see *"could not be opened"* in the log, set
those two folders to `0755`.

---

## Updating later

1. Locally make changes. If dependencies changed: `composer install --no-dev --optimize-autoloader`.
   If assets changed: `npm run build`. Commit `vendor/` / `public/build/` as needed. Push.
2. cPanel → Git™ Version Control → **Update from Remote**.
3. If assets changed and you're on the `public_html` layout, re-copy `public/build` → `public_html/build`.
4. If the schema changed, export a fresh dump locally and re-import via phpMyAdmin (no `migrate` on the host).

## Troubleshooting (issues actually hit)

- **"Composer dependencies require PHP >= 8.4.1"** → the server PHP is older than the vendor was built for.
  This repo pins Composer to 8.3 (Symfony 7.4) to avoid it; make sure the domain runs PHP 8.3+.
- **Can't enable `pdo_mysql` ("conflict")** → `nd_pdo_mysql` is already enabled and provides it. Leave it.
- **`SQLSTATE[HY000] [1045] Access denied … (using password: YES)`** → wrong DB user/password (not a privileges
  issue). Reset the DB user's password to letters+numbers only and copy the exact username into `.env`.
- **500 page with no detail** → set `APP_DEBUG=true` in `.env`, reload to read the error, then set it back to `false`.
  The real error is also at the bottom of `repositories/optimiststudenti/storage/logs/laravel.log`.
