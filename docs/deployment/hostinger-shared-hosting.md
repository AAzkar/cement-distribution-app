# Deploying to Hostinger Shared Hosting

This app has been validated against real MySQL (migrations + full sales-order/
stock/report flows all run cleanly — see verification notes at the bottom).
It requires PHP `^8.3` and standard extensions (gd, intl, mbstring, pdo_mysql,
bcmath, zip, curl, openssl) — all enabled by default on Hostinger.

**Plan requirement:** use a **Business** or **Premium** shared plan (or
whatever Hostinger currently calls the tier with SSH access + Git deployment).
The entry-level plan typically doesn't offer SSH/Composer, both of which you
need. Check hPanel → Advanced → SSH Access to confirm before you start.

## 1. Create the MySQL database

hPanel → Databases → MySQL Databases → create a database and a user, and
**note the database name, username, and password** (Hostinger prefixes them
with your account ID, e.g. `u123456789_cement`).

## 2. Choose your document root strategy

**Preferred:** hPanel → Domains → your domain (or a subdomain) → set the
document root directly to wherever you'll upload the project's `public/`
folder, e.g. `domains/your-domain.com/laravel_app/public`. This lets Laravel's
`public/index.php` and `.htaccess` work completely unmodified.

**Fallback:** if your plan won't let you change the document root, use the
split-root layout and bridge files in `deploy/hostinger/README.md` instead.

The rest of this guide assumes the preferred approach.

## 3. Upload the code

Either:
- **Git** (hPanel → Advanced → Git, if available) — point it at your repo, or
- **SSH**: `git clone` your repo, or
- **File Manager/FTP**: zip the project (excluding `vendor/`, `node_modules/`,
  `.git/`), upload, and extract.

Upload everything to `laravel_app/` (a folder one level *above* the document
root you set in step 2, so `laravel_app/public` is what's actually served).

## 4. Install dependencies (via SSH)

```bash
cd ~/laravel_app
composer install --optimize-autoloader --no-dev
```

## 5. Configure `.env`

Copy `.env.hostinger.example` to `.env` on the server and fill in:
- `APP_URL` — your real domain, with `https://`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — from step 1
- `MAIL_*` — Hostinger's SMTP details (hPanel → Emails) or your own mail provider

Then generate a real app key (don't reuse the one from local dev):

```bash
php artisan key:generate
```

## 6. Run migrations — seed roles/master data only, NOT demo users

```bash
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\RolesAndPermissionsSeeder --force
php artisan db:seed --class=Database\\Seeders\\MasterDataSeeder --force
```

**Do not run the full `DatabaseSeeder`** — it creates demo accounts
(`admin@cementco.test`, etc.) with the password `password`, which is now
public in this conversation history. Create your real admin instead:

```bash
php artisan tinker
>>> $u = App\Models\User::create(['name' => 'Your Name', 'email' => 'you@your-domain.com', 'password' => 'a-strong-password', 'is_active' => true]);
>>> $u->assignRole('Admin');
>>> exit
```

## 7. Storage symlink

```bash
php artisan storage:link
```

This makes uploaded logos, cheque photos, and attachments (stored in
`storage/app/public`) reachable at `/storage/...`.

## 8. Set permissions

```bash
chmod -R 755 storage bootstrap/cache
```

If PHP still can't write to them, check what user PHP-FPM runs as in hPanel
and `chown` accordingly — Hostinger usually already sets this up correctly
for your account's own files.

## 9. Cache for production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Re-run these after every future deploy or `.env` change (`config:clear` first
if something looks stale).

## 10. SSL

hPanel → SSL → enable the free Let's Encrypt certificate for your domain, and
make sure `APP_URL` uses `https://`.

## 11. Post-deploy checklist

- [ ] Log in to `/admin` and `/rep` with your real admin account
- [ ] Set your company name/logo under Admin → Branding
- [ ] Confirm currency shows as **LKR** throughout
- [ ] Create a warehouse, zone, product, and a test customer
- [ ] Place a test sales order end-to-end (draft → confirm → check stock deducted)
- [ ] Generate a PDF export (a report or a receipt) to confirm dompdf works
      under the server's PHP/GD setup
- [ ] Delete the test data once confirmed working

## Notes on background jobs

Shared hosting can't run a persistent `queue:work` process. `.env.hostinger.example`
sets `QUEUE_CONNECTION=sync`, which is correct for this app today (it doesn't
dispatch queued jobs). If you add real background jobs later, switch to
`QUEUE_CONNECTION=database` and add a cron job (hPanel → Advanced → Cron Jobs)
running `php artisan queue:work --stop-when-empty` every minute instead.

## Verification performed locally before writing this guide

- Installed MySQL locally and ran all 39 migrations against it directly — clean,
  no "specified key too long" or enum/index errors.
- Ran a full sales-order flow (create → confirm → stock deduction → customer
  balance update) against that MySQL database — correct results.
- Ran the monthly sales report (which uses `whereMonth`/`whereYear`, not raw
  SQL date functions) against MySQL — correct results.
- Added `Schema::defaultStringLength(191)` in `AppServiceProvider` as a
  defensive measure for older MySQL/MariaDB builds sometimes found on budget
  shared hosting (harmless on modern MySQL, prevents index-length errors on
  older ones).
