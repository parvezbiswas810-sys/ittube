<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose new password · IT TUBE</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-art">
        <div class="brand">
            <img src="{{ asset('assets/logo.png') }}" alt="IT TUBE" style="width:44px;height:44px;border-radius:13px">
            <div><div class="brand-name">IT TUBE</div><div class="brand-sub">Nexdi Code Lab</div></div>
        </div>
        <div><h2>Set a strong new password.</h2><p>Use at least 8 characters. All existing sessions will remain signed in until they expire.</p></div>
        <p style="font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.5)">Blue Orbit Edition</p>
    </div>
    <div class="auth-card">
        <div class="auth-box">
            <h1>New password</h1>
            <p class="sub">Choose a new password for your admin account.</p>
            @if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('admin.password.update') }}" class="form-grid">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" value="{{ $email }}" required>
                </div>
                <div class="field">
                    <label for="password">New password</label>
                    <input id="password" type="password" name="password" required minlength="8">
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8">
                </div>
                <button class="btn btn-primary btn-block" type="submit">Update password</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
