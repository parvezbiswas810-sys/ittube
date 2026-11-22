# IT TUBE — repository notes

Premium video streaming platform: Laravel API + Blue Orbit admin panel (`backend/`), Flutter Android app (`ittube_app/`), distributable packages (`dist/`).

## Layout

- `backend/` — Laravel app. API at `/api/v1`, admin at `/admin`, installer at `/install`, media streaming at `/media/videos/{id}/stream`.
- `ittube_app/` — Flutter app, package `com.nexdicodelab.ittube`, API URL via `--dart-define=API_BASE_URL=...`.
- `dist/` — release artifacts: `ittube-backend.zip`, `ittube-android-source.zip`, `app-release.apk`, `app-release.aab`, `site/` (download portal).
- `branding/` — original logo reference.

## Local development

```bash
# backend
cd backend && php artisan serve --host=0.0.0.0 --port=12000
# Flutter (emulator host alias)
cd ittube_app && flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
```

Local MySQL: user `ittube` (password kept out of the repo — see the sandbox provisioning notes), databases `ittube` (dev) and `ittube_install_test` (installer test).
Seeded dev admin: `admin@ittube.test` / `AdminPass2026!`.

## Build

```bash
cd ittube_app
export PATH=/workspace/tools/flutter/bin:$PATH ANDROID_SDK_ROOT=/workspace/tools/android-sdk ANDROID_HOME=/workspace/tools/android-sdk
flutter build apk --release --dart-define=API_BASE_URL=https://api.adsxuit.com/api/v1
flutter build appbundle --release --dart-define=API_BASE_URL=https://api.adsxuit.com/api/v1
```

## Packaging the backend zip

`dist/ittube-backend/ittube_backend` is a staged copy (no `.env` from dev, no `storage/app` contents, no `installed.lock`).
After changing backend code, re-copy the changed files and rebuild:

```bash
cp backend/<path> dist/ittube-backend/ittube_backend/<path>
cd dist && zip -rq ittube-backend.zip ittube-backend && cp ittube-backend.zip site/
```

## Gotchas

- `asset()` uses `APP_URL`. For previews behind the work-host proxy, start the server with a matching `APP_URL` **and** keep `trustProxies(at: '*')` in `bootstrap/app.php`, otherwise CSS/JS links point at the production domain or downgrade to http.
- `FORCE_HTTPS` is env driven; production defaults to on.
- Installer locks via `storage/app/installed.lock`; `/install` returns 404 afterwards. The packaged `.env` ships `APP_ENV=production` and placeholder DB values so the installer can boot.
- `file_picker` v13 API: `FilePicker.pickFile(type: FileType.video)` (no `FilePicker.platform`).
- Flutter 3.50 reports `package:flutter/material.dart` deprecation info messages; harmless.
- Video uploads require FFmpeg for thumbnails/duration; falls back gracefully when missing.

## Upload pipeline

- Direct `POST /api/v1/videos` is capped by PHP (`post_max_size`); larger files go through the
  resumable endpoints: `POST /videos/chunk/init`, `POST /videos/chunk/{id}` (multipart `index` + `chunk`),
  `GET /videos/chunk/{id}`, `POST /videos/chunk/{id}/complete`, `DELETE /videos/chunk/{id}`.
- `GET /videos/upload/limits` drives the Flutter upload UI (max size, chunk size, formats, enabled flag).
- Chunks live in `storage/app/private/chunks/{session}`; assembly verifies size before FFmpeg runs.
- `RejectOversizedUploads` turns PHP's silent empty-POST into a 413 with an actionable message.

## Counters

Unsigned counter columns (`likes_count`, `dislikes_count`, `comments_count`, `subscribers_count`,
`videos_count`) must be changed through `App\Support\Counters` — plain `decrement()` throws
`SQLSTATE 22003` when the value is already 0, and the model's `getOriginal()` is already updated after
`update()`, so capture the previous value before mutating a reaction row.

## Admin settings form

Bool settings are only reset to `0` when the submitted form rendered them: the blade emits
`bool_fields[]` markers and `Admin\SettingController` ignores unlisted booleans. Adding a settings
form elsewhere means adding those markers too, otherwise unchecked boxes stay untouched.

## Build environment used here

```bash
export PATH=/opt/flutter/bin:$PATH ANDROID_SDK_ROOT=/opt/android-sdk ANDROID_HOME=/opt/android-sdk
export JAVA_HOME=/usr/lib/jvm/java-21-openjdk-amd64
```

Release builds use the debug signing config until `android/key.properties` is provided.

## Repackaging after backend changes

```bash
FILES="app/... routes/api.php resources/views/..."
for f in $FILES; do cp "backend/$f" "dist/ittube-backend/ittube_backend/$f"; cp "backend/$f" "dist/cpanel-package/ittube_backend/$f"; done
cd dist && rm -f ittube-backend.zip ittube-cpanel.zip \
  && zip -rq ittube-backend.zip ittube-backend -x "*/.env" -x "*/storage/app/*" -x "*/installed.lock" \
  && zip -rq ittube-cpanel.zip cpanel-package && cp ittube-backend.zip ittube-cpanel.zip site/
cd ittube_app && zip -rq /tmp/src.zip . -x "build/*" -x ".dart_tool/*" -x ".gradle/*" -x "android/.gradle/*" \
  && cp /tmp/src.zip ../dist/ittube-android-source.zip && cp /tmp/src.zip ../dist/site/
```

## v1.1 upgrade modules (Google OAuth build)

Backend additions: `Admin\{AuthSettings,Platform,LiveStream,Copyright}Controller` + `Api\V1\{DeviceToken,Download,Copyright,LiveStream}Controller`,
views under `resources/views/admin/{auth-settings,platform,live,copyright}`, permissions `auth.manage`, `platform.manage`, `live.manage`, `copyright.manage`.

Flutter additions: `lib/services/google_auth.dart`, `lib/widgets/auth_gate.dart`, `lib/screens/downloads/downloads_screen.dart`,
`lib/screens/channel/create_channel_screen.dart`; `AppConfig` gained `googleClientId`, `guestModeEnabled`, `downloadsEnabled`, `liveStreamEnabled`.

Route-ordering traps fixed here (keep the constraints):
- `channels/{handle}` needs `->where('handle','^(?!mine$)...')`, otherwise `/channels/mine` 404s.
- `videos/{id}`, `shorts/{id}`, `live-streams/{id}` need `->whereNumber('id')`, otherwise `/videos/mine` is swallowed.
- `videos/{video}/download` lives inside the `auth:sanctum` group — guest downloads must stay 401.

Verification recipe (no pdo_sqlite here, so PHPUnit only covers DB-free tests):
boot the kernel in a scratch PHP script, `app()->instance('request', $req)`, `auth('admin')->setUser(...)`, then `$kernel->handle()` per URL.
Real HTTP checks: `php artisan serve` + `curl` for guest 401s (guard caching hides them in-process).

Security scrubs applied to `dist/site` and the cpanel package (do not reintroduce):
- no `.env`, no `deploy.php`, no `admin-reset.php`, no `check.php` in served/public bundles;
- no DB credentials or deploy keys in the Bangla guides or `VERIFICATION-REPORT.txt`.

Packaging now also excludes `bootstrap/cache/*.php` (absolute paths from the build machine) and `database/database.sqlite`.

## Google sign-in: browser fallback (v1.2)
- `backend/app/Services/GoogleSignInService.php` — shared verify+provision used by API and web.
- `backend/app/Http/Controllers/Web/GoogleAuthController.php` — `/auth/google/redirect` + `/auth/google/callback` (state in cache, 10 min, one-time; code exchange with Web client + secret; returns `com.nexdicodelab.ittube://oauth?token=…`).
- App: `lib/services/google_browser_auth.dart` (app_links + url_launcher), login screen falls back automatically on native failure, `AuthProvider.loginWithToken`.
- cPanel patch bundle: `dist/google-login-patch/` → `ittube-google-patch.zip` (6 files).

## v1.2.0 premium update
- Auth is Google-only in the app (email/password + register screens removed).
- Home rebuilt: greeting header, hero card, `ContinueWatchingRow` (history API).
- Player: double-tap seek ±10s with haptic feedback.
- Release signing moved to `android/ittube-release.jks` + `android/key.properties`
  (gitignored). SHA-1 `7A:1C:32:FB:3B:41:2F:10:19:2A:E4:59:7F:0F:DD:8D:C7:C1:E0:BB`.
  Old APKs used the debug key, so upgrades need an uninstall first.
- Local docroot for the download site: `dist/site` served on port 12000.

## v1.3.0 YouTube-style layout
- `screens/home/home_screen.dart`: logo header (bell badge + search) + 5 scrollable tabs
  (Home/Subs/Music/Live/More) with `HomeFeedTab` (shorts shelf + paginated feed).
- `screens/home/home_tabs.dart`: SubsTab, MusicTab (admin "Music" category), LiveTab
  (respects `live_stream_enabled`), MoreTab.
- `widgets/feed_widgets.dart`: FeedVideoCard, ShortsCard, ShortsRow, LiveCard, FeedSkeletonList.
- `screens/shell/main_shell.dart`: bottom nav Home/Shorts/Create/Subs/You + create sheet
  (channel guard), `screens/profile/you_tab.dart` for the You tab.
- Theme gained violet accents (`AppColors.violet`, `AppGradients.violetBrand`).
- `LiveStream` model + `ApiService.liveStreams()`; `UploadScreen(initialType:)`.

## v1.3.1 playback fixes
- Player: first-frame gate before `play()`, `_showPoster` overlay, buffering spinner,
  Retry button, throttled rebuilds (500ms), controls auto-hide, wakelock while playing.
- Lifecycle: `WidgetsBindingObserver` + `RouteAware` (`core/route_observer.dart`,
  registered in `main.dart`) pause/resume on background and route push.
- Progress saved every 10s and on dispose via `recordView(position, completed)`.
- Autoplay next from `_related`; `VideoPlayerScreen(startAt:)` used by
  `ContinueWatchingRow` (now on Home).
- Shorts: lifecycle pause, throttled rebuilds, disposed on tab switch in `main_shell`,
  share sheet.
- New deps: `share_plus`, `wakelock_plus`.
