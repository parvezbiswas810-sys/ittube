# IT TUBE — cPanel Deployment Guide

This guide deploys the IT TUBE Laravel backend + Blue Orbit admin panel on **shared cPanel hosting** (no Docker, no VPS required).

- API base: `https://api.adsxuit.com/api/v1`
- Admin panel: `https://api.adsxuit.com/admin`
- Installer: `https://api.adsxuit.com/install`

---

## 0. Requirements

| Item | Minimum |
|---|---|
| PHP | 8.2 (8.3/8.4 recommended) |
| PHP extensions | pdo_mysql, mbstring, openssl, fileinfo, gd, zip, curl, json, tokenizer, xml, ctype, bcmath |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Disk | 2 GB+ (more for video storage) |
| SSL | Required (Let's Encrypt via cPanel AutoSSL) |

Optional: `ffmpeg` binary on the server for automatic thumbnails/duration detection. The uploader works without it (it falls back to client-provided thumbnails and `getID3`-free metadata detection).

---

## 1. Create the database (cPanel → MySQL Databases)

1. **Databases → MySQL Databases**
2. Create database: `cpuser_ittube`
3. Create user: `cpuser_ittube` with a strong password
4. **Add user to database** → select the database → grant **ALL PRIVILEGES**

Note the full names, e.g. `cpuser_ittube` / `cpuser_ittube`.

---

## 2. Upload the backend

The recommended layout keeps Laravel outside `public_html`:

```
/home/CPANEL_USER/
├── ittube_backend/          <-- all Laravel files (app, config, vendor, storage ...)
└── public_html/             <-- only the contents of ittube_backend/public
    ├── index.php
    ├── .htaccess
    └── assets/ ...
```

### Steps

1. **File Manager → Upload** `ittube-backend.zip` into `/home/CPANEL_USER/`.
2. Extract it. Rename the extracted folder to `ittube_backend` if needed.
3. Open `ittube_backend/public/` and **move all of its contents** into `public_html/` (including `.htaccess` and `assets/`).
4. Edit `public_html/index.php` and fix the two paths:

```php
require __DIR__.'/../ittube_backend/vendor/autoload.php';
$app = require_once __DIR__.'/../ittube_backend/bootstrap/app.php';
```

5. Edit `public_html/.htaccess` (already included) — it routes everything to `index.php`.
6. Make sure `.env` lives in `ittube_backend/.env` (never inside `public_html`).

### Alternative: subdomain document root

If `api.adsxuit.com` is a subdomain, set its **Document Root** to `/home/CPANEL_USER/ittube_backend/public` in cPanel → Domains. Then you can skip step 3–4 entirely.

---

## 3. Permissions

Set these to `755` for folders and `644` for files (File Manager → Permissions):

```
ittube_backend/storage            -> 755 (recursive)
ittube_backend/bootstrap/cache    -> 755
ittube_backend/.env               -> 644 (writable during install, then 600 if possible)
public_html                       -> 755
```

---

## 4. Run the web installer

1. Visit `https://api.adsxuit.com/install`
2. The page checks PHP version, extensions, writable folders and FFmpeg.
3. Fill in:
   - **Application URL**: `https://api.adsxuit.com`
   - **Database host**: usually `localhost` (or `127.0.0.1`)
   - **Database name / user / password**: the cPanel values from step 1
   - **Super Admin** name, email and a password of 10+ characters
4. Click **Install IT TUBE now**.

The installer will:

- write `.env` with a fresh `APP_KEY`
- run all migrations (29 tables)
- seed roles, permissions, default categories, home sections and app settings
- create your Super Admin
- write `storage/app/installed.lock` (installer locks itself, returns 404 afterwards)

> **Bangla:** `/install` এ DB details আর admin details দিয়ে Install চাপলে সব table তৈরি হবে, admin account তৈরি হবে, এবং install শেষ হলে installer নিজেই lock হয়ে যাবে।

### Optional: one-time install key

Add to `.env` before installing:

```
ITTUBE_INSTALL_KEY=some-long-random-string
```

The installer then requires that key.

### If SSH is available (alternative to the web installer)

```bash
cd ~/ittube_backend
cp .env.example .env
php artisan key:generate
# edit .env with DB credentials
php artisan migrate --force
php artisan db:seed --force
php artisan ittube:create-admin you@example.com --name="IT TUBE Admin"
```

`ittube:create-admin` also writes the install lock.

---

## 5. Storage & uploads

Video and image files are stored on the `public` disk for images and the `private` disk for videos:

```
storage/app/public/thumbnails/...   -> served via /storage (or /media route)
storage/app/private/videos/...      -> streamed only through /media/videos/{id}/stream
```

Video streaming is authorised by the API: private videos are never exposed as direct file URLs.

Run once (SSH or cPanel Terminal):

```bash
php artisan storage:link
```

If symlinks are blocked, the included `/media/{path}` route serves public files without a symlink.

### PHP upload limits

The app validates size in Laravel, but PHP itself must allow it. In cPanel → **MultiPHP INI Editor**:

```
upload_max_filesize = 512M
post_max_size = 512M
max_execution_time = 600
memory_limit = 512M
```

Then set the same limit in **Admin → App Settings → max_upload_size_mb**.

---

## 6. SSL

1. cPanel → **SSL/TLS Status** → Run AutoSSL for `api.adsxuit.com`.
2. Force HTTPS is automatic in production (`APP_ENV=production`).
3. Ensure `APP_URL=https://api.adsxuit.com` in `.env`.

---

## 7. Post-install checklist

| Check | URL |
|---|---|
| API config | `https://api.adsxuit.com/api/v1/app/config` |
| Public settings | `https://api.adsxuit.com/api/v1/settings/public` |
| Admin login | `https://api.adsxuit.com/admin` |
| Installer locked | `https://api.adsxuit.com/install` should return 404 |
| Terms page | `https://api.adsxuit.com/terms` |
| Privacy page | `https://api.adsxuit.com/privacy` |

Then, in the admin panel:

1. **App Settings** → set app name, logo, support email, upload limits, feature toggles.
2. **Home Page** → reorder / enable sections.
3. **Categories** → add or hide categories used by the Android app.

---

## 7b. Google sign-in, downloads, live and copyright modules

These modules ship disabled by default. Enable them from the admin panel after go-live.

### Google sign-in (Android + web)

1. Google Cloud Console → *APIs & Services → Credentials*:
   - **Android OAuth client**: package `com.nexdicodelab.ittube` + SHA-1 of the keystore used to sign the APK/AAB.
   - **Web OAuth client**: used as the `serverClientId` so the backend can verify the token audience.
2. Admin → **Authentication** (`/admin/authentication`, permission `auth.manage`):
   - Paste the Web client ID, toggle **Google sign-in** on, press **Test** (returns 302 with a status message).
   - Optionally toggle **Guest browsing** on/off. Off means every app screen requires an account.
3. The client ID is exposed to the app through `/api/v1/app/config`; no client secret is stored or shipped.

Secrets are stored encrypted (`APP_KEY`) and are never re-displayed in the form (`secret_kept` behaviour).

### Video downloads

1. Admin → **Platform** (`/admin/platform`, permission `platform.manage`) → enable **Downloads**.
2. Per video, the uploader (or admin) sets **Allow download**.
3. The `private` disk must be writable: `storage/app/private` (chmod 775). Files are streamed through `/api/v1/videos/{id}/download` with a Sanctum token, never by public URL.
4. Rate limit: 60 downloads/hour per user (`AppServiceProvider`).

### Live streams and live chat

1. Admin → **Platform** → enable **Live streaming**.
2. Admin → **Live streams** (`/admin/live-streams`) to moderate: status (live/ended), featured, remove.
3. Creators call `POST /api/v1/live-streams` to get RTMP/SRT ingest credentials, then `start`/`end`.
4. Viewers poll `GET /api/v1/live-streams/{id}/chat` (last 100 messages) and post with `POST` (500 char limit, rate limited).
5. HLS playback needs an HLS-capable origin; the API returns the playback URL configured on the stream.

### Copyright / DMCA

- Public claim form: `POST /api/v1/copyright/reports`.
- Review queue: Admin → **Copyright** (`/admin/copyright`, permission `copyright.manage`) → open → review → resolve/reject with a note.

### FFmpeg

Optional. Without FFmpeg, uploads still succeed: duration/resolution stay at the browser-provided values, thumbnails must be uploaded manually, and HLS conversion is skipped. The admin panel reports FFmpeg availability instead of pretending a conversion happened.

---

## 8. Cron (recommended)

Add in cPanel → **Cron Jobs** (every minute):

```
/usr/local/bin/php /home/CPANEL_USER/ittube_backend/artisan schedule:run >> /dev/null 2>&1
```

---

## 9. Hardening after go-live

1. **Delete the installer** (recommended):
   - remove `app/Http/Controllers/InstallerController.php`
   - remove the `/install` routes from `routes/web.php`
   - delete `resources/views/install/`
2. Set `.env` permissions to `600` and `APP_DEBUG=false`.
3. Keep `storage/app/installed.lock` — it blocks the installer even if the routes exist.
4. Restrict admin access by IP in cPanel if desired.
5. Back up the database and `storage/app` regularly.

---

## 10. Troubleshooting

| Symptom | Fix |
|---|---|
| 500 error on first load | Check `storage/logs/laravel.log`; usually permissions or missing `APP_KEY` |
| Blank white page | `APP_DEBUG=true` temporarily; check PHP error log in cPanel |
| `could not find driver` | Enable `pdo_mysql` in **Select PHP Version → Extensions** |
| Uploads fail at ~2 MB | Raise `upload_max_filesize` and `post_max_size` |
| 419 Page Expired in admin | Session/cookie domain mismatch — clear cookies, check `APP_URL` |
| Videos do not play | Check `/media/videos/{id}/stream` returns 206; ensure `storage/app/private` is writable |
| Thumbnails broken | Run `php artisan storage:link` or use the `/media/...` URLs |
| Installer says requirements failed | Fix the red item (extension or writable folder) |
| Admin 404 | Ensure `public_html/.htaccess` is present and `mod_rewrite` is enabled |
| CSS not loading | Confirm `public_html/assets/admin.css` exists |

---

## 11. Updating the backend

1. Back up `.env`, `storage/app`, and the database.
2. Upload the new files over the old ones (keep `.env` and `storage/`).
3. Run:

```bash
php artisan migrate --force
php artisan config:clear
php artisan view:clear
php artisan route:clear
```
