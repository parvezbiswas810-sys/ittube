================================================================
 IT TUBE — GOOGLE LOGIN FIX (browser fallback) — আপডেট প্যাচ
================================================================

কেন: ফোনে Google চাপলে "code 10" আসছিল (Play Services-এর native flow-এ
Android OAuth client + SHA-1 ঠিকভাবে না মিললে হয়)। এখন অ্যাপ native flow
fail হলে নিজে থেকেই browser দিয়ে sign-in শেষ করবে — এতে Android client /
SHA-1 লাগে না, শুধু Web client + client secret লাগে।

----------------------------------------------------------------
ধাপ ১ — এই zip টা সার্ভারে আপলোড করুন
----------------------------------------------------------------
cPanel → File Manager → /home/adsxuitc/  (এখানে ittube_backend ফোল্ডার আছে)
  → ittube-google-patch.zip upload → Extract (overwrite: Yes)

এতে এই ফাইলগুলো আপডেট হবে:
  ittube_backend/app/Services/GoogleSignInService.php            (নতুন)
  ittube_backend/app/Http/Controllers/Web/GoogleAuthController.php (নতুন)
  ittube_backend/app/Http/Controllers/Api/V1/AuthController.php   (আপডেট)
  ittube_backend/routes/web.php                                   (আপডেট)
  ittube_backend/resources/views/web/google-callback.blade.php     (নতুন)
  ittube_backend/resources/views/admin/auth-settings/index.blade.php (আপডেট)

----------------------------------------------------------------
ধাপ ২ — cache মুছুন (খুব গুরুত্বপূর্ণ)
----------------------------------------------------------------
File Manager → /home/adsxuitc/ittube_backend/bootstrap/cache/ → ভিতরের সব .php ফাইল delete
(অথবা আগের fix-storage.php একবার চালালেই এটা নিজে করে নেবে)

----------------------------------------------------------------
ধাপ ৩ — Client Secret বসান
----------------------------------------------------------------
https://api.adsxuit.com/admin/authentication
  "Client secret" ঘরে GOCSPX-... বসিয়ে Save করুন
  (যদি secret reset করে থাকেন, নতুনটা বসাবেন — অ্যাপে কিছুই বসাতে হবে না)

তারপর "Test configuration" → সব OK হলে "Enable Google login"

----------------------------------------------------------------
ধাপ ৪ — নতুন APK ইনস্টল
----------------------------------------------------------------
https://work-1-zxsvxbhwmtyjswuu.prod-runtime.all-hands.dev/app-release.apk

----------------------------------------------------------------
ধাপ ৫ — টেস্ট
----------------------------------------------------------------
Login → Continue with Google:
  • native কাজ করলে সোজা login
  • না করলে নিজে থেকেই browser খুলবে → Gmail দিয়ে sign in → আবার অ্যাপে ফিরে আসবে
    (অথবা "Trouble signing in? Continue in browser" চাপলেই browser খুলবে)

----------------------------------------------------------------
নিরাপত্তা নোট
----------------------------------------------------------------
• state parameter একবারই ব্যবহার হয় (10 মিনিটেই expire) — replay আটকানো আছে
• client secret শুধু সার্ভারে থাকে, অ্যাপে যায় না
• token custom scheme (com.nexdicodelab.ittube://oauth) দিয়ে অ্যাপে ফেরে
