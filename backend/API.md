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

### Resumable (chunked) upload — use for large files

Shared hosting caps a single request (`post_max_size` / `upload_max_filesize`), so files above
`max_direct_upload_mb` must be sent in chunks.

| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| GET | `/videos/upload/limits` | ✔ | `max_upload_mb, max_direct_upload_mb, chunk_size, allowed_formats, uploads_enabled` |
| POST | `/videos/chunk/init` | ✔ | JSON: `filename, size, total_chunks, title, description?, category_id?, visibility?, type?, tags?` → `upload_id, chunk_size` |
| POST | `/videos/chunk/{upload_id}` | ✔ | multipart: `index` (0-based), `chunk` (raw bytes ≤ `chunk_size`) |
| GET | `/videos/chunk/{upload_id}` | ✔ | `received_indexes, received_chunks, progress, status` — use to resume |
| POST | `/videos/chunk/{upload_id}/complete` | ✔ | optional multipart `thumbnail`; assembles chunks and creates the video |
| DELETE | `/videos/chunk/{upload_id}` | ✔ | cancel and delete uploaded chunks |

Sessions are scoped to the authenticated user, expire with their chunk folder, and the assembled
file is size-verified before FFmpeg processing.

**Oversized / invalid upload errors**

| Status | When | Body |
|---|---|---|
| 413 | file exceeds PHP `post_max_size`/`upload_max_filesize` | `success:false`, clear message naming the limit |
| 422 | validation failure (title, format, size vs admin setting) | `success:false`, `errors{}` with field messages |

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
| Admin roles | `/admin/roles` |
| Activity logs | `/admin/activity-logs` |
