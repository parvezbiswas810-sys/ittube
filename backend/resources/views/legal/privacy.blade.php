<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy · IT TUBE</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body>
<div class="install-wrap">
    <div class="install-head">
        <img src="{{ asset('assets/logo.png') }}" alt="IT TUBE">
        <h1>Privacy Policy</h1>
        <p>IT TUBE by Nexdi Code Lab</p>
    </div>
    <div class="card card-pad">
        <h3 style="margin:0 0 8px">Data we store</h3>
        <p style="color:var(--muted)">Account details (username, email, hashed password), channel and profile information, uploaded videos, comments, reactions, subscriptions and watch history.</p>
        <h3 style="margin:18px 0 8px">How we use it</h3>
        <p style="color:var(--muted)">To operate the streaming service, personalise recommendations, and keep the community safe through moderation.</p>
        <h3 style="margin:18px 0 8px">Your controls</h3>
        <p style="color:var(--muted)">You can edit your profile, pause watch history, clear history and request account deletion inside the Android app.</p>
        <h3 style="margin:18px 0 8px">Contact</h3>
        <p style="color:var(--muted)">{{ \App\Services\SettingService::get('contact_email', 'support@adsxuit.com') }}</p>
    </div>
</div>
</body>
</html>
