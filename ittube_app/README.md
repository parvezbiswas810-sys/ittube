# IT TUBE — Android App (Flutter)

**Nexdi Code Lab Presents · IT TUBE · Blue Orbit Edition**

Premium video streaming app for Android, connected to the IT TUBE Laravel API.

- Package: `com.nexdicodelab.ittube`
- API: `https://api.adsxuit.com/api/v1`
- Built with Flutter 3.50 (Dart 3.14)

---

## Features

| Area | Implemented |
|---|---|
| Splash | Logo animation, config load, connectivity check, maintenance gate |
| Onboarding | 4 pages (watch, discover, shorts, create channel) with skip/next |
| Auth | Register, login, Google sign-in (ID token), guest browsing, forgot/reset password, secure token storage, logout, delete account |
| Home | API-driven sections, horizontal feeds, pull-to-refresh, skeletons, error/empty states |
| Shorts | Vertical fullscreen player, swipe navigation, like/comment/share/subscribe |
| Explore | Categories, trending, popular creators |
| Search | Videos + channels, filter chips, saved history, clear history |
| Player | Real playback, seek, fullscreen/landscape, like/dislike, subscribe, save to playlist, report, comments, related |
| Upload | File picker, thumbnail, title/description/tags/category/visibility, resumable chunked upload with progress, channel-creation gate |
| Channels | Banner, avatar, subscribe, videos / shorts / about tabs |
| Subscriptions | Subscribed channels + latest feed |
| Library | Watch history, playlists, my uploads |
| Notifications | List, unread badge, mark read / mark all |
| Profile | Edit profile + avatar, create channel, downloads, change password, legal pages, logout, delete account |
| Downloads | Offline downloads of videos with `allow_download` (documents folder), in-app offline player, delete |
| Remote control | Maintenance mode, feature flags (uploads/comments/shorts/registration/downloads/live), guest mode, Google client ID, upload limits |

## Google sign-in setup

The app uses `google_sign_in` and sends the Google **ID token** to `POST /api/v1/auth/google`.

1. In Google Cloud Console create an **Android OAuth client** for package `com.nexdicodelab.ittube` with the SHA-1 of your signing key.
2. Create a **Web OAuth client** and paste its client ID in **Admin → Authentication**.
3. Toggle Google sign-in on. The app reads the client ID from `/app/config` (`auth.google_client_id`) at runtime.
4. Without the matching SHA-1, Google returns `PlatformException` code 10 — the login screen shows a readable message.

Guest browsing and the Google button appear automatically when the admin enables them.

## Run locally

```bash
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
```

`10.0.2.2` is the Android emulator alias for your host machine. For a physical device use your computer's LAN IP, e.g. `http://192.168.0.10:8000/api/v1`.

## Build APK / AAB

```bash
# APK (production API)
flutter build apk --release --dart-define=API_BASE_URL=https://api.adsxuit.com/api/v1

# Play Store bundle
flutter build appbundle --release --dart-define=API_BASE_URL=https://api.adsxuit.com/api/v1
```

Outputs:

- `build/app/outputs/flutter-apk/app-release.apk`
- `build/app/outputs/bundle/release/app-release.aab`

## Release signing (Play Store)

1. Create a keystore:

```bash
keytool -genkey -v -keystore ~/ittube-upload.jks -keyalg RSA -keysize 2048 -validity 10000 -alias ittube
```

2. Create `android/key.properties`:

```properties
storePassword=YOUR_STORE_PASSWORD
keyPassword=YOUR_KEY_PASSWORD
keyAlias=ittube
storeFile=/absolute/path/to/ittube-upload.jks
```

3. Add to `android/app/build.gradle.kts` inside `android { ... }`:

```kotlin
val keystoreProperties = java.util.Properties()
val keystorePropertiesFile = rootProject.file("key.properties")
if (keystorePropertiesFile.exists()) {
    keystoreProperties.load(java.io.FileInputStream(keystorePropertiesFile))
}

signingConfigs {
    create("release") {
        keyAlias = keystoreProperties["keyAlias"] as String
        keyPassword = keystoreProperties["keyPassword"] as String
        storeFile = file(keystoreProperties["storeFile"] as String)
        storePassword = keystoreProperties["storePassword"] as String
    }
}
buildTypes {
    release {
        signingConfig = signingConfigs.getByName("release")
    }
}
```

Without this, `flutter build apk --release` still produces an installable APK signed with the debug key (not suitable for Play Store).

## Project structure

```
lib/
  core/            api_client.dart (HTTP + upload progress), theme.dart (Blue Orbit)
  models/          User, Channel, Video, Comment, Playlist, Notification, HomeSection, AppConfig
  providers/       AuthProvider, ConfigProvider, FeedProvider, NotificationProvider
  services/        ApiService (all API endpoints)
  screens/         splash, onboarding, auth, shell, home, shorts, explore, subscriptions,
                   library, search, video (player/comments/upload), channel, notifications, profile
  widgets/         VideoCard, ChannelTile, SectionHeader, skeletons, empty/error states
assets/            logo.png, logo-512.png
```

## API base URL

The default is compiled in via `String.fromEnvironment('API_BASE_URL')` in `lib/core/api_client.dart`. Change it at build time with `--dart-define`, or edit the default value for a hard-coded build.

## Notes

- Tokens are stored with `flutter_secure_storage` (falls back to shared preferences).
- No API secrets are embedded in the APK.
- Uploads respect the admin-configured max size and allowed formats (enforced again server-side).
- If the admin enables maintenance mode, the app shows the maintenance screen after launch.
