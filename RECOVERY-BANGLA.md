# IT TUBE — RECOVERY / CONTINUE HERE

**Nexdi Code Lab Presents — IT TUBE (Blue Orbit Edition)**

এই রিপো = IT TUBE প্ল্যাটফর্মের সম্পূর্ণ কোড + পুরো কাজের ইতিহাস।
নতুন OpenHands conversation-এ এই রিপো খুললে নিচের ২ লাইন দিলেই আগের সব কনটেক্সট ফিরে আসবে।

---

## 🚀 নতুন কনভার্সেশনে কী বলবেন (কপি-পেস্ট)

```
এই রিপোটা পড়ো: RECOVERY-BANGLA.md, AGENTS.md, docs/conversation-transcript.md
তারপর IT TUBE-এর কাজ যেখানে শেষ হয়েছে সেখান থেকে চালিয়ে যাও।
```

`docs/conversation-transcript.md` = পুরো আগের conversation-এর ক্রমিক রেকর্ড
(সিদ্ধান্ত, কমান্ড, বাগ ফিক্স, ইউজারের নির্দেশ — সব; সিক্রেট রিড্যাক্ট করা)।

---

## 📦 রিপোতে কী আছে

| পাথ | কী |
|---|---|
| `backend/` | Laravel API + Admin Panel (PHP 8.2+, MySQL) |
| `ittube_app/` | Flutter Android app (package `com.nexdicodelab.ittube`) |
| `ittube_app/SOURCE-README-BANGLA.md` | Android Studio সেটআপ গাইড |
| `dist/site/*.txt` | বাংলা ইউজার গাইড, আপডেট নোট, cPanel ইনস্টল গাইড |
| `docs/conversation-transcript.md` | আগের পুরো conversation-এর ট্রান্সক্রিপ্ট |
| `AGENTS.md` | এজেন্টের জন্য প্রজেক্ট মেমরি (স্ট্যাটাস, ফাইল ম্যাপ, ফিক্স লগ) |

**রিপোতে নেই (ইচ্ছাকৃতভাবে):** `backend/.env`, `android/key.properties`,
`*.jks` keystore, `dist/KEYSTORE-INFO.txt` (এতে keystore পাসওয়ার্ড আছে),
`vendor/`, বিল্ড আর্টিফ্যাক্ট (APK/AAB)।

---

## 📌 বর্তমান অবস্থা (সর্বশেষ: v1.3.1 / versionCode 5)

### Android app (Flutter)
- হোম: হেডার (লোগো, নোটিফিকেশন ব্যাজ, সার্চ) + ট্যাব `Home / Subs / Music / Live / More`
- Shorts শেলফ + ফুল ফিড + Continue watching + infinite scroll + skeleton
- বটম নেভ: `Home · Shorts · Create(+) · Subs · You`
- প্লেয়ার: first-frame gate (লোড হওয়ার আগে অডিও নয়), পোস্টার, বাফারিং স্পিনার,
  retry, auto-hide controls, double-tap seek, speed, fullscreen, autoplay-next,
  প্রতি ১০ সেকেন্ডে progress সেভ, resume
- Lifecycle: background/route-push এ pause, ফিরলে resume (RouteAware)
- Shorts: ট্যাব ছাড়লে dispose (সাউন্ড বন্ধ), lifecycle pause
- Auth: শুধু "Continue with Google" (ব্রাউজার ফ্লো + সার্ভার callback)
- শেয়ার শিট (`share_plus`), keep-awake (`wakelock_plus`)
- API base: `https://api.adsxuit.com/api/v1` (কোডে ডিফল্ট, `--dart-define` দিয়ে বদলানো যায়)

### Backend (Laravel)
- REST API v1: auth, profile, videos, shorts, categories, channels, subscriptions,
  comments, playlists, history, notifications, reports, settings, live-streams
- Admin panel `/admin`: dashboard, users, channels, videos, shorts, categories,
  comments, reports, notifications, home sections, app settings, roles, activity logs
- Google OAuth: `GoogleSignInService`, `Web\GoogleAuthController`, callback ভিউ
- cPanel-compatible (public folder আলাদা, `.env` বাইরে)

---

## 🔨 বিল্ড করার নিয়ম

### Android
```bash
cd ittube_app
flutter pub get
flutter build apk --release        # debug-key দিয়ে সাইন হবে (key.properties না থাকলে)
flutter build appbundle --release
```
রিলিজ সাইনিং: `ittube_app/SOURCE-README-BANGLA.md` দেখুন (নিজের keystore বানিয়ে
`android/key.properties` বসাতে হবে)।

### Backend
```bash
cd backend
composer install
cp .env.example .env      # তারপর DB + Google creds বসান
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

### cPanel ডিপ্লয়
`dist/site/CPANEL-README-BANGLA.txt` ও `backend/DEPLOYMENT.md` দেখুন।
`/install` ইনস্টলার একবার চলে নিজেই বন্ধ হয়ে যায়।

---

## 🔐 যে সিক্রেটগুলো রিপোতে নেই

| সিক্রেট | কোথায় |
|---|---|
| Release keystore + পাসওয়ার্ড | লোকাল `ittube_app/android/ittube-release.jks` + `dist/KEYSTORE-INFO.txt` (রিপোতে কমিট করা হয়নি) |
| `.env` (DB, Google client secret, mail) | সার্ভারের cPanel-এ |
| GitHub token | OpenHands secret `GITHUB_TOKEN` |

**মনে রাখবেন:** keystore হারালে Play Store-এ আপডেট দেওয়া যাবে না — ব্যাকআপ রাখুন।

---

## ⚠️ যে কাজগুলো এখনো বাকি (রোডম্যাপ)

1. **Live studio / Go Live** — এখন শুধু লাইভ *দেখা* যায়; নিজে স্ট্রিম শুরু করার ফিচার নেই
2. **Picture-in-picture / background audio** — ইচ্ছাকৃতভাবে বন্ধ (ইউজার চায়নি)
3. **ডার্ক মোড** — পুরো অ্যাপে
4. **অফলাইন ডাউনলোড ম্যানেজার** — ডাউনলোড আছে, অফলাইন লাইব্রেরি নেই
5. **Push notification (FCM)** — টেবিল/API রেডি, Firebase প্রজেক্ট সংযুক্ত করতে হবে
6. **ডাউনলোড সার্ভার** — OpenHands sandbox-এর port 12000/12001; container restart হলে
   `bash dist/start-servers.sh` চালাতে হবে (বা ফাইল cPanel-এ রাখলে স্থায়ী হবে)

---

## 🧭 আগের সেশনে যা হয়েছিল (সংক্ষেপে)

1. Laravel backend + admin panel + Flutter app তৈরি (v1.1)
2. Google sign-in ফিক্স (`code 10`, `org_internal`), ব্রাউজার-ফ্লো ফলব্যাক
3. লগইন/রেজিস্টার সরিয়ে শুধু "Continue with Google"
4. YouTube-স্টাইল হোম/নেভিগেশন রিডিজাইন (v1.3.0)
5. প্লেব্যাক বাগ ফিক্স: লোড হওয়ার আগে অডিও, shorts থেকে বেরিয়ে সাউন্ড, background audio,
   per-frame rebuild, progress সেভ (v1.3.1)
6. সোর্স জিপ + APK/AAB ডেলিভারি, cPanel ও Android Studio গাইড

বিস্তারিত: `docs/conversation-transcript.md`

---

© Nexdi Code Lab — IT TUBE
