IT TUBE — cPanel ওয়ান-ক্লিক ইনস্টল (api.adsxuit.com)
=====================================================
এই প্যাকেজে আপনার ডাটাবেস আগেই বসানো আছে:
  DB Name : YOUR_DB_NAME
  DB User : YOUR_DB_NAME
  DB Pass : YOUR_DB_NAME
  APP URL : https://api.adsxuit.com
  Deploy Key : YOUR_DEPLOY_KEY

--- ধাপ ১: আপলোড ---
cPanel → File Manager → /home/adsxuitc/ এ যান
ittube-cpanel.zip আপলোড করুন → Extract করুন
ফলাফল:
  /home/adsxuitc/ittube_backend/        <-- Laravel (এখানেই থাকবে)
  /home/adsxuitc/public_html_files/     <-- পাবলিক ফাইল

--- ধাপ ২: পাবলিক ফাইল কপি ---
File Manager এ ঢুকে /home/adsxuitc/public_html_files/ এর ভিতরের সব কিছু
(index.php, .htaccess, assets ফোল্ডার, install) সিলেক্ট করে Copy করুন
→ /home/adsxuitc/api.adsxuit.com/ ফোল্ডারে Paste করুন (Replace/Overwrite দিন)

⚠️ ittube_backend ফোল্ডারটি api.adsxuit.com এর ভিতরে রাখবেন না —
   এটি /home/adsxuitc/ittube_backend এ থাকবে (এক লেভেল উপরে)।

--- ধাপ ৩: পারমিশন ---
File Manager → ittube_backend → storage ফোল্ডারে right click → Change Permissions → 755
একইভাবে ittube_backend → bootstrap → cache → 755

--- ধাপ ৪: ইনস্টল চালান (এক ক্লিক) ---
ব্রাউজারে খুলুন:
  https://api.adsxuit.com/install

স্ক্রিনে ধাপে ধাপে OK/FAIL দেখাবে। শেষে আপনার অ্যাডমিন ইমেইল ও পাসওয়ার্ড দেখাবে:
  Admin URL : https://api.adsxuit.com/admin
  Email     : admin@adsxuit.com
  Password  : (স্ক্রিনে দেখাবে)

চাইলে নিজের ইমেইল/পাসওয়ার্ড আগেই দিতে পারেন:
  https://api.adsxuit.com/install

ইনস্টল শেষ হলে install নিজে নিজেই লক হয়ে যাবে।
ক্রেডেনশিয়াল সেভ থাকবে: ittube_backend/storage/app/admin-credentials.txt

--- বিকল্প: Terminal থাকলে (SSH/cPanel Terminal) ---
  cd /home/adsxuitc
  bash deploy.sh /home/adsxuitc/api.adsxuit.com

--- চেক লিস্ট ---
  API  : https://api.adsxuit.com/api/v1/app/config   -> success:true দেখা যাবে
  Admin: https://api.adsxuit.com/admin               -> লগইন পেজ
  Installer লক: https://api.adsxuit.com/install      -> 404 (নিরাপত্তার জন্য)

--- সমস্যা হলে ---
1) সাদা পেজ / 500:
   ittube_backend/storage/logs/laravel.log দেখুন
   .env এ APP_DEBUG=true করে আবার দেখুন, পরে false করে দিন
2) "could not find driver" / DB error:
   cPanel → Select PHP Version → Extensions → pdo_mysql টিক দিন
   PHP ভার্সন 8.2 বা তার উপরে সেট করুন
3) Permission denied:
   storage, bootstrap/cache → 755 (কিছু হোস্টে 775 লাগে)
4) CSS/ডিজাইন ভাঙা:
   api.adsxuit.com ফোল্ডারে assets/admin.css আছে কিনা দেখুন
5) 419 Page Expired:
   কুকি ক্লিয়ার করুন; SSL চালু থাকলে .env এ FORCE_HTTPS=true ও
   SESSION_SECURE_COOKIE=true রাখুন
6) SSL পরে চালু করলে:
   .env এ FORCE_HTTPS=true এবং SESSION_SECURE_COOKIE=true করে দিন

--- ইনস্টলের পরে ---
- Admin → App Settings এ app name, logo, upload limit, feature toggles সেট করুন
- Admin পাসওয়ার্ড বদলান
- cPanel → MultiPHP INI Editor: upload_max_filesize=512M, post_max_size=512M
- চাইলে deploy.sh, README ফাইল ডিলিট করতে পারেন (ittube_backend ফোল্ডার রাখুন)

===========================================================================
নতুন (v1.1) — Google লগইন, ডাউনলোড, কপিরাইট, লাইভ, প্ল্যাটফর্ম
===========================================================================
১) Google লগইন চালু করা
   - Google Cloud Console → Credentials → Android OAuth client
       package name : com.nexdicodelab.ittube
       SHA-1        : আপনার keystore এর SHA-1
                      (এই বিল্ডের ডিবাগ SHA-1: E2:15:53:D3:9C:DC:CE:BC:C5:B5:D7:B0:81:E8:97:76:E4:EC:9A:D1)
   - একটি Web OAuth client তৈরি করে Client ID কপি করুন
   - Admin → Authentication → Client ID বসান → Google sign-in চালু → Test চাপুন
   - অ্যাপের লগইন স্ক্রিনে "Continue with Google" বাটন দেখা যাবে
   - Guest browsing বন্ধ করলে সব স্ক্রিনে লগইন বাধ্যতামূলক হবে

২) ভিডিও ডাউনলোড
   - Admin → Platform → Downloads চালু করুন
   - প্রতিটি ভিডিও এডিট করে "Allow download" টিক দিন
   - অ্যাপে প্লেয়ারের উপরে ডাউনলোড আইকন; Profile → Downloads এ অফলাইন প্লে/ডিলিট

৩) কপিরাইট / DMCA
   - দাবি জমা: POST /api/v1/copyright/reports
   - রিভিউ: Admin → Copyright (open → review → resolved/rejected + নোট)

৪) লাইভ স্ট্রিম + লাইভ চ্যাট
   - Admin → Platform → Live streaming চালু করুন
   - Admin → Live streams → স্ট্যাটাস/ফিচার্ড/রিমুভ মডারেশন
   - ক্রিয়েটর: POST /api/v1/live-streams → RTMP ingest credentials → start/end
   - চ্যাট: GET /api/v1/live-streams/{id}/chat (পোলিং), POST একই URL (৫০০ অক্ষর)

৫) গুরুত্বপূর্ণ নোট
   - জিপের ভিতরে আর .env নেই — /install উইজার্ড নিজেই .env + APP_KEY তৈরি করে
   - APK/AAB ডিবাগ কী দিয়ে সাইন করা হয়েছে; Play Store এ দেওয়ার আগে নিজের keystore ব্যবহার করুন
   - FFmpeg ইনস্টল না থাকলেও আপলোড কাজ করবে (অটো থাম্বনেইল/HLS বন্ধ থাকবে)
