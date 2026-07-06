# Hostinger shared hosting — fallback files

These files are only needed if your Hostinger plan/domain **cannot** have its
document root pointed directly at Laravel's `public/` folder. Most current
Hostinger shared plans *can* do this via hPanel (Domains → your domain/subdomain
→ "Change document root"), which is simpler and needs none of the files here —
see the main guide at `docs/deployment/hostinger-shared-hosting.md` first.

Use this folder only for the fallback "split root" layout:

```
/home/username/laravel_app/     <- the whole Laravel app, EXCEPT public/'s contents
/home/username/public_html/     <- the CONTENTS of Laravel's public/ folder
```

## Steps

1. Upload the entire project to `laravel_app/` (a sibling of `public_html`,
   not inside it).
2. Copy everything from `laravel_app/public/` into `public_html/`:
   `.htaccess`, `favicon.ico`, `robots.txt`, `css/`, `js/`.
3. Replace `public_html/index.php` with `public_html-index.php` from this
   folder, renamed to `index.php`.
4. Create the storage symlink manually (the default `php artisan storage:link`
   won't find the right target in a split-root layout):
   ```
   ln -s /home/username/laravel_app/storage/app/public /home/username/public_html/storage
   ```
5. Everything else (composer install, migrations, `.env`, permissions) is the
   same as the main guide.
