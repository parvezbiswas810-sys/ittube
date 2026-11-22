# IT TUBE — Laravel Backend + Blue Orbit Admin Panel

**Nexdi Code Lab Presents · IT TUBE · Blue Orbit Edition**

A complete Laravel 12/13 REST API and responsive admin panel powering the IT TUBE Android app.

- API: `https://api.adsxuit.com/api/v1`
- Admin: `https://api.adsxuit.com/admin`
- Installer: `https://api.adsxuit.com/install`

---

## What is included

**Backend**

- 31 MySQL tables with foreign keys, indexes, unique constraints and soft deletes
- Sanctum token authentication, rate limiting, email verification, password reset
- Video upload with FFmpeg thumbnail/duration/resolution detection (graceful fallback)
- Private video streaming with HTTP range requests (`/media/videos/{id}/stream`)
- Full REST API v1 (see `docs/API.md`)
- One-time web installer (`/install`) — DB setup, migrations, seeders, Super Admin
- Console command `php artisan ittube:create-admin`

**Admin panel (Blade + Blue Orbit design system)**

- Dashboard with real MySQL KPIs and Chart.js growth charts
- Users, channels, videos, shorts, categories, comments, reports, notifications
- Home page section builder (drives the Android home feed)
- App settings (feature flags, limits, branding) stored in MySQL
- Roles & permissions (Super Admin, Moderator, Support Admin, custom)
- Activity/audit logs with IP addresses
- Responsive layout, white sidebar, electric-blue active states

**Upgrade modules (v1.1)**

- **Google sign-in** — server-side ID-token verification (JWKS, `aud`, `iss`, `exp`, `email_verified`), account linking by `google_id`/verified email, plus a web/server OAuth flow. Controlled from **Admin → Authentication** (`auth.manage`); the client ID is published via `/app/config`, secrets are encrypted at rest.
- **Guest browsing** — optional unauthenticated browsing of home, search, videos, shorts, channels and live lists (`guest.browse` middleware); every write still requires a token.
- **Device / push tokens** — `POST|DELETE /auth/device-tokens`, unique per token, cleaned up on logout (Firebase-ready, no FCM key required to store tokens).
- **Video downloads** — per-video `allow_download` flag, platform-wide switch, Sanctum-protected streaming download with a 60/hour rate limit.
- **Live streams + live chat** — stream lifecycle (create → start → end), RTMP/SRT ingest credentials, HLS playback URL, polling chat API, admin moderation.
- **Copyright / DMCA** — public claim form, claimant status endpoint, admin review queue with resolution notes.
- **Platform controls** — downloads, live streaming, ad placements and creator monetization profiles from **Admin → Platform** (`platform.manage`).

**Branding**

- Blue Orbit palette: `#0866F5`, `#1677FF`, `#071B68`, `#0A1260`, `#00C7F2`, `#00B9E8`, `#74C9FF`, `#EAF5FF`, `#F4F9FF`
- Logo assets in `public/assets/logo.png`, `logo-512.png`, `logo.svg`, `favicon.png`

---

## Requirements

PHP 8.2+ (8.4 tested), MySQL/MariaDB, Composer, extensions: pdo_mysql, mbstring, openssl, fileinfo, gd, zip, curl, bcmath, intl. FFmpeg optional.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure DB in .env
php artisan migrate --seed
php artisan ittube:create-admin admin@example.com --name="IT TUBE Admin"
php artisan serve
```

Admin: http://127.0.0.1:8000/admin
API: http://127.0.0.1:8000/api/v1/app/config

## cPanel deployment

See **`docs/DEPLOYMENT-CPANEL.md`** for the full guide (database creation, upload layout, permissions, `/install`, storage, SSL, cron, hardening, troubleshooting).

## Project structure

```
app/
  Http/Controllers/Api/V1/   REST API controllers
  Http/Controllers/Admin/    Admin panel controllers
  Http/Middleware/           EnsureInstalled, EnsureAdmin, EnsurePermission, ForceJson
  Http/Resources/            API resources
  Models/                    29 Eloquent models
  Services/                  VideoService, UploadService, NotificationService, SettingService, ActivityLogger
  Support/                   ApiResponse, Media
database/migrations/         29 migrations
resources/views/admin/       Admin panel views (Blue Orbit)
resources/views/install/     Web installer
routes/api.php               API v1 routes
routes/web.php               Admin, installer, media, legal routes
docs/                        API + cPanel documentation
```

## Admin modules map

| Module | URL | Permission |
|---|---|---|
| Dashboard, users, channels, videos, shorts, categories, comments, reports, notifications, home page, settings, roles, logs | `/admin/...` | role based |
| Authentication (Google / guest mode) | `/admin/authentication` | `auth.manage` |
| Copyright / DMCA | `/admin/copyright` | `copyright.manage` |
| Live streams | `/admin/live-streams` | `live.manage` |
| Platform (downloads, live, ads, monetization) | `/admin/platform` | `platform.manage` |

## Security notes

- Passwords hashed with bcrypt, tokens revocable, rate limits on auth/upload/report
- Server-side validation on every endpoint; ownership checks on videos, comments, playlists, channels
- Admin CSRF protection, session regeneration, role/permission middleware
- Private videos never exposed as public URLs
- Installer locks itself (`storage/app/installed.lock`); delete it after go-live
- `.env`, `vendor/`, `storage/` must stay outside `public_html`

## Default seeded data

12 categories (Gaming, Music, Technology, Entertainment, Education, Sports, News, Movies, Comedy, Travel, Food, Lifestyle), 6 home sections, 13 permissions, 3 roles, and all app settings. No fake users or fake videos are created.

---

© Nexdi Code Lab — IT TUBE Blue Orbit Edition
