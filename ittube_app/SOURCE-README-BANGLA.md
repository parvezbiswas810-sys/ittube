# IT TUBE — Android Studio / Flutter Source (v1.3.1)

Nexdi Code Lab Presents — IT TUBE (Blue Orbit Edition)

এটা অ্যাপের সম্পূর্ণ সোর্স কোড। Android Studio দিয়ে খুলে সরাসরি চালানো ও বিল্ড করা যাবে।

---

## ১. কী কী আছে

```
ittube_app/
├── lib/                     # সব Dart সোর্স (screens, widgets, providers, services, models)
├── android/                 # Android Gradle প্রজেক্ট (manifest, gradle, signing config)
├── assets/                  # লোগো, ফন্ট, ইমেজ
├── pubspec.yaml             # ডিপেন্ডেন্সি
├── pubspec.lock             # লক করা ভার্সন (একই ভার্সন পেতে)
└── analysis_options.yaml
```

API base URL কোডেই সেট করা আছে: `https://api.adsxuit.com/api/v1`
(`lib/core/api_client.dart` → `String.fromEnvironment('API_BASE_URL', defaultValue: ...)`)
তাই আলাদা করে কিছু পাস করতে হবে না। অন্য সার্ভারে চালাতে চাইলে:

```
flutter run --dart-define=API_BASE_URL=https://your-domain.com/api/v1
```

---

## ২. Android Studio-তে খোলা

1. Android Studio → **File ▸ Open** → `ittube_app` ফোল্ডার সিলেক্ট করুন
   (ভেতরের `android` ফোল্ডার নয় — পুরো `ittube_app`)
2. প্রথমবার খুললে ডানদিকে **"Pub get"** প্রম্পট আসবে → চাপুন।
   না এলে টার্মিনালে:
   ```
   flutter pub get
   ```
3. **Settings ▸ Languages & Frameworks ▸ Flutter** এ Flutter SDK path সেট করুন
   (যেমন `C:\flutter` বা `/home/user/flutter`)
4. **Settings ▸ Build ▸ Build Tools ▸ Gradle ▸ Gradle JDK** = **JDK 17 বা 21** সেট করুন
5. ডিভাইস/এমুলেটর সিলেক্ট করে ▶ Run

CLI দিয়ে:
```
flutter doctor
flutter devices
flutter run
```

---

## ৩. APK বিল্ড

**ডিবাগ / টেস্ট APK (সাইনিং কী ছাড়াই কাজ করবে):**
```
flutter build apk --release
```
> এই ফোল্ডারে `android/key.properties` নেই, তাই Gradle অটোমেটিক debug key দিয়ে
> সাইন করবে — ইনস্টল হবে, কিন্তু Play Store-এ দেওয়া যাবে না।

**নিজের রিলিজ কী দিয়ে সাইন করতে:**

```
keytool -genkeypair -v -keystore ittube-release.jks -alias ittube \
  -keyalg RSA -keysize 4096 -validity 10950
```

তারপর `android/key.properties` ফাইল বানান:

```
storePassword=আপনার_পাসওয়ার্ড
keyPassword=আপনার_পাসওয়ার্ড
keyAlias=ittube
storeFile=../ittube-release.jks
```

(`ittube-release.jks` ফাইলটা `android/` ফোল্ডারে রাখুন)

```
flutter build apk --release      # → build/app/outputs/flutter-apk/app-release.apk
flutter build appbundle --release # → build/app/outputs/bundle/release/app-release.aab
```

---

## ৪. Google Sign-In সেটআপ

অ্যাপে Google লগইন ব্রাউজার-ফ্লো দিয়ে কাজ করে, তাই সাধারণত কিছু করতে হয় না।

নিজের Google OAuth ব্যবহার করতে চাইলে:
1. Google Cloud Console → **APIs & Services ▸ Credentials**
2. **OAuth client ID ▸ Android** তৈরি করুন
   - Package name: `com.nexdicodelab.ittube`
   - SHA-1: আপনার signing key-এর SHA-1 (`keytool -list -v -keystore ...`)
3. **OAuth client ID ▸ Web application** তৈরি করে Client ID + Secret নিন
4. সার্ভারের `.env`-এ বসান:
   ```
   GOOGLE_CLIENT_ID=...
   GOOGLE_CLIENT_SECRET=...
   GOOGLE_REDIRECT_URI=https://api.adsxuit.com/auth/google/callback
   ```
5. অ্যাপে SHA-1 পাস করার দরকার হলে:
   ```
   flutter build apk --release --dart-define=BUILD_SHA1=AA:BB:...
   ```

---

## ৫. ফোল্ডার ম্যাপ (কোথায় কী)

| ফোল্ডার | কাজ |
|---|---|
| `lib/screens/home/` | হোম (হেডার, ট্যাব, শর্টস শেলফ, ফিড) |
| `lib/screens/video/` | ভিডিও প্লেয়ার, আপলোড, কমেন্ট |
| `lib/screens/shorts/` | ভার্টিকাল শর্টস প্লেয়ার |
| `lib/screens/shell/` | বটম নেভিগেশন + Create শিট |
| `lib/screens/channel/` | চ্যানেল পেজ, চ্যানেল তৈরি |
| `lib/screens/profile/` | প্রোফাইল ও "You" ট্যাব |
| `lib/widgets/` | কমন UI (কার্ড, স্কেলিটন, লোগো) |
| `lib/providers/` | Auth / Config / Feed স্টেট |
| `lib/services/` | API সার্ভিস, Google লগইন |
| `lib/models/` | ডেটা মডেল |

---

## ৬. সমস্যা হলে

| সমস্যা | সমাধান |
|---|---|
| `flutter: command not found` | Flutter SDK ইনস্টল করে PATH-এ যোগ করুন |
| Gradle JDK error | Settings → Gradle JDK = 17/21 |
| `pub get` ব্যর্থ | ইন্টারনেট চেক করুন, `flutter pub cache repair` |
| Google লগইন ফেল | সার্ভারে `GOOGLE_CLIENT_ID/SECRET` ঠিক আছে কিনা দেখুন |
| ভিডিও চলছে না | `https://api.adsxuit.com` অ্যাক্সেসযোগ্য কিনা দেখুন |

---

© Nexdi Code Lab — IT TUBE
