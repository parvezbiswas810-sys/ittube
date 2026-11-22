# IT TUBE — REST API v1

Base URL: `https://api.adsxuit.com/api/v1`

All responses use this envelope:

```json
{ "success": true, "message": "Request completed successfully", "data": {}, "pagination": null }
```

Errors:

```json
{ "success": false, "message": "Validation failed", "errors": { "email": ["The email has already been taken."] } }
```

Authentication: `Authorization: Bearer <token>` (Laravel Sanctum personal access token).

Rate limits: `api` 120/min · `login` 8/min · `uploads` 10/min · `reports` 6/min.

---

## Auth

| Method | Endpoint | Auth | Body / Notes |
|---|---|---|---|
| POST | `/auth/register` | – | `username, email, password, password_confirmation, display_name?, terms_accepted=1, device_name` → returns `user` + `token` |
| POST | `/auth/login` | – | `login` (email or username), `password`, `device_name` → `user` + `token` |
| POST | `/auth/logout` | ✔ | revokes current token |
| GET | `/auth/me` | ✔ | current user with profile + channel |
| POST | `/auth/forgot-password` | – | `email` → sends reset code |
| POST | `/auth/reset-password` | – | `email, token, password, password_confirmation` |
| GET | `/auth/email/verify/{id}/{hash}` | signed | email verification link |
| POST | `/auth/email/resend` | ✔ | resend verification |
| POST | `/auth/delete-account` | ✔ | `password` — deletes account and content |

## Profile

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| GET | `/profile` | ✔ | user, profile, channel |
| PUT | `/profile` | ✔ | `display_name, bio, country, avatar` (multipart with `_method=PUT`) |
| POST | `/profile/password` | ✔ | `current_password, password, password_confirmation` |

## App config & settings

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| GET | `/app/config` | – | maintenance, features, upload limits, links, home sections |
| GET | `/settings/public` | – | public branding + legal links |
| GET | `/home` | – | ordered home sections with videos |

## Videos

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| GET | `/videos` | – | `page, per_page, sort=recent\|popular\|trending, category, channel, q, type=video\|short` |
| GET | `/videos/mine` | ✔ | current user's uploads (any status) |
| GET | `/videos/{id}` | – | video detail (increments nothing; use `/view`) |
| GET | `/videos/{id}/related` | – | related videos |
| POST | `/videos` | ✔ | multipart: `video` (file), `title, description?, category_id?, visibility, type, tags?, thumbnail?` |
| PUT | `/videos/{id}` | ✔ | owner or admin |
| DELETE | `/videos/{id}` | ✔ | owner or admin (soft delete) |
| POST | `/videos/{id}/view` | optional | `position, completed` — records watch history |
| POST | `/videos/{id}/like` | ✔ | toggles like |
| POST | `/videos/{id}/dislike` | ✔ | toggles dislike |
| POST | `/videos/{id}/report` | ✔ | `reason, details?` |

Upload response includes `thumbnail_url` and `playback_url` (`/media/videos/{id}/stream`).

## Shorts

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| GET | `/shorts` | – | paginated vertical videos |
| GET | `/shorts/{id}` | – | single short |

## Categories / search / channels

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| GET | `/categories` | – | active categories with video counts |
| GET | `/search` | – | `q` (2+ chars), `filter=all\|videos\|channels\|shorts`, `page` |
| GET | `/channels` | – | `q, featured=1, page` |
| GET | `/channels/{handle}` | – | `channel, videos, shorts` (+ `is_subscribed` when authenticated) |
| GET | `/channels/mine` | ✔ | own channel |
| POST | `/channels` | ✔ | `name, handle, description?, category_id?` |
| PUT | `/channels/{id}` | ✔ | owner edits own channel |

## Subscriptions

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| POST | `/channels/{id}/subscribe` | ✔ | idempotent |
| DELETE | `/channels/{id}/subscribe` | ✔ | |
| GET | `/subscriptions` | ✔ | subscribed channels |
| GET | `/subscriptions/feed` | ✔ | latest videos from subscriptions |

## Comments

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| GET | `/videos/{id}/comments` | – | paginated, includes replies |
| POST | `/videos/{id}/comments` | ✔ | `body, parent_id?` |
| PUT | `/comments/{id}` | ✔ | owner only |
| DELETE | `/comments/{id}` | ✔ | owner or admin |
| POST | `/comments/{id}/like` | ✔ | toggles |
| POST | `/comments/{id}/report` | ✔ | `reason, details?` |

## Playlists

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| GET | `/playlists` | ✔ | own playlists |
| POST | `/playlists` | ✔ | `title, description?, visibility` |
| GET | `/playlists/{id}` | ✔ | with videos |
| PUT | `/playlists/{id}` | ✔ | |
| DELETE | `/playlists/{id}` | ✔ | |
| POST | `/playlists/{id}/videos` | ✔ | `video_id` |
| DELETE | `/playlists/{id}/videos/{videoId}` | ✔ | |
| POST | `/playlists/{id}/reorder` | ✔ | `video_ids: []` |

## History

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| GET | `/history` | ✔ | `history_id, position, video` |
| DELETE | `/history/{id}` | ✔ | remove one |
| DELETE | `/history` | ✔ | clear all |
| POST | `/history/pause` | ✔ | toggle pause tracking |

## Notifications

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| GET | `/notifications` | ✔ | `notifications[], unread_count` |
| POST | `/notifications/{id}/read` | ✔ | |
| POST | `/notifications/read-all` | ✔ | |
| DELETE | `/notifications/{id}` | ✔ | |
| GET | `/notifications/preferences` | ✔ | |
| PUT | `/notifications/preferences` | ✔ | `new_subscribers, new_uploads, comment_replies, announcements` |

## Reports

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| POST | `/reports` | ✔ | `type=video\|short\|channel\|comment\|user, id, reason, details?` |
| GET | `/reports/mine` | ✔ | own report history + status |

### Report reasons

`spam`, `harassment`, `violence`, `sexual_content`, `copyright`, `misinformation`, `other`

---

## Google sign-in

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| POST | `/auth/google` | ✘ | Body: `id_token`, optional `device_name`. Returns the same payload as `/auth/login` (`token`, `user`). |

Disabled unless **Admin → Authentication** has `google_login_enabled = yes` and a client ID; the endpoint then answers `403`. The server verifies the ID token signature against Google's JWKS, checks `aud` against the configured client ID, `iss`, `exp` and `email_verified`, then creates or links the local account (matching by `google_id`, then by verified email). No password is stored for Google-only accounts.

### Google setup checklist

1. Google Cloud Console → *APIs & Services → Credentials*.
2. Create an **Android OAuth client** with package `com.nexdicodelab.ittube` and the SHA-1 of the signing key (`keytool -list -v -keystore ...`).
3. Create a **Web OAuth client** and copy its client ID.
4. Admin → Authentication → paste the Web client ID, toggle Google sign-in on, run **Test**.
5. Rebuild the app; the client ID is delivered through `/app/config` (`auth.google_client_id`), so no secret ships in the APK.

## Device / push tokens

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| POST | `/auth/device-tokens` | ✔ | Body: `token`, `platform` (`android`/`ios`/`web`). Idempotent per token. |
| DELETE | `/auth/device-tokens` | ✔ | Body: `token`. Called on logout. |

## Video downloads

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| GET | `/videos/{id}/download` | ✔ | Streams the original file. Requires `downloads_enabled` (Admin → Platform) **and** `video.allow_download` (or ownership). Rate limit: 60/hour. |

`allow_download` is accepted by `POST /videos` and `PUT /videos/{id}` and returned in every video resource.

## Copyright / DMCA

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| POST | `/copyright/reports` | ✘ | Body: `video_id?`, `url?`, `claimant_name`, `claimant_email`, `reason`, `evidence?`. Throttled. |
| GET | `/copyright/reports/mine` | ✔ | Status of the caller's own claims. |

Admin review lives at **/admin/copyright** (permission `copyright.manage`): open → review → resolved/rejected, with the resolution note stored on the report.

## Live streams and live chat

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| GET | `/live-streams` | ✘ | Published streams (`status=live` first). |
| GET | `/live-streams/{id}` | ✘ | Stream details, playback URL. |
| GET | `/live-streams/{id}/chat` | ✘ | Last 100 messages, polled by the app. |
| POST | `/live-streams/{id}/chat` | ✔ | Body: `message` (max 500). Rate limited. |
| GET | `/live-streams/mine` | ✔ | Caller's streams. |
| POST | `/live-streams` | ✔ | Create stream; returns RTMP/SRT ingest credentials. |
| PUT | `/live-streams/{id}` | ✔ | Update title, description, schedule, thumbnail. |
| GET | `/live-streams/{id}/credentials` | ✔ | Owner-only ingest credentials. |
| POST | `/live-streams/{id}/start` \| `/end` | ✔ | Owner-only lifecycle. |

All live endpoints answer `403` with a clear message when `live_stream_enabled = no` in Admin → Platform. Admin moderation: **/admin/live-streams** (permission `live.manage`).

## App config additions

`GET /app/config` now also returns:

```json
{
  "google_login_enabled": true,
  "guest_mode_enabled": true,
  "downloads_enabled": false,
  "live_stream_enabled": false,
  "auth": { "google_client_id": "…apps.googleusercontent.com", "guest_mode_enabled": true },
  "features": { "downloads": false, "live_stream": false }
}
```

---

## Admin panel (session auth, not part of `/api/v1`)

| Area | URL |
|---|---|
| Login | `/admin/login` |
| Dashboard | `/admin` |
| Users | `/admin/users` |
| Channels | `/admin/channels` |
| Videos | `/admin/videos` |
| Shorts | `/admin/shorts` |
| Categories | `/admin/categories` |
| Comments | `/admin/comments` |
| Reports | `/admin/reports` |
| Notifications | `/admin/notifications` |
| Home page | `/admin/home-page` |
| App settings | `/admin/settings` |
| Authentication (Google / guest) | `/admin/authentication` |
| Copyright / DMCA | `/admin/copyright` |
| Live streams | `/admin/live-streams` |
| Platform (downloads, live, ads, monetization) | `/admin/platform` |
| Admin roles | `/admin/roles` |
| Activity logs | `/admin/activity-logs` |
