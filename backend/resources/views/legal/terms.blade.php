<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service · IT TUBE</title>
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body>
<div class="install-wrap">
    <div class="install-head">
        <img src="{{ asset('assets/logo.png') }}" alt="IT TUBE">
        <h1>Terms of Service</h1>
        <p>IT TUBE by Nexdi Code Lab</p>
    </div>
    <div class="card card-pad">
        <p style="margin-bottom:12px">By creating an account or using the IT TUBE application you agree to these terms.</p>
        <h3 style="margin:18px 0 8px">1. Content</h3>
        <p style="color:var(--muted)">You are responsible for the videos, comments and channel information you publish. Do not upload content that infringes copyright, harasses others, or violates the law.</p>
        <h3 style="margin:18px 0 8px">2. Moderation</h3>
        <p style="color:var(--muted)">IT TUBE administrators may review, restrict or remove content and suspend accounts that break these terms.</p>
        <h3 style="margin:18px 0 8px">3. Account security</h3>
        <p style="color:var(--muted)">Keep your password private. You are responsible for activity on your account.</p>
        <h3 style="margin:18px 0 8px">4. Contact</h3>
        <p style="color:var(--muted)">Questions: {{ \App\Services\SettingService::get('contact_email', 'support@adsxuit.com') }}</p>
    </div>
</div>
</body>
</html>
