<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password · IT TUBE</title>
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
        <div><h2>Reset access safely.</h2><p>We will email a secure, time-limited reset link to your administrator address.</p></div>
        <p style="font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.5)">Blue Orbit Edition</p>
    </div>
    <div class="auth-card">
        <div class="auth-box">
            <h1>Forgot password</h1>
            <p class="sub">Enter your admin email to receive a reset link.</p>
            @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('admin.password.email') }}" class="form-grid">
                @csrf
                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="admin@example.com">
                </div>
                <button class="btn btn-primary btn-block" type="submit">Send reset link</button>
            </form>
            <div class="auth-links"><a href="{{ route('admin.login') }}">Back to sign in</a></div>
        </div>
    </div>
</div>
</body>
</html>
