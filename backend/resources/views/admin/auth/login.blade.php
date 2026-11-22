<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login · IT TUBE</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-art">
        <div class="brand">
            <img src="{{ asset('assets/logo.png') }}" alt="IT TUBE" style="width:44px;height:44px;border-radius:13px">
            <div>
                <div class="brand-name">IT TUBE</div>
                <div class="brand-sub">Nexdi Code Lab</div>
            </div>
        </div>
        <div>
            <h2>Premium video streaming, controlled from one place.</h2>
            <p>Manage users, channels, videos, shorts, moderation and the Android app experience with the Blue Orbit admin panel.</p>
            <div class="auth-points">
                <div><span>1</span> Real-time analytics from your MySQL database</div>
                <div><span>2</span> Full content moderation and reporting tools</div>
                <div><span>3</span> Control every Android app feature remotely</div>
            </div>
        </div>
        <p style="font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.5)">Blue Orbit Edition</p>
    </div>
    <div class="auth-card">
        <div class="auth-box">
            <h1>Admin sign in</h1>
            <p class="sub">Use your IT TUBE administrator account.</p>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="form-grid">
                @csrf
                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="admin@example.com">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                </div>
                <label class="switch"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                <button class="btn btn-primary btn-block" type="submit">Sign in to dashboard</button>
            </form>
            <div class="auth-links">
                <a href="{{ route('admin.password.request') }}">Forgot password?</a>
                <span>IT TUBE Admin</span>
            </div>
        </div>
    </div>
</div>
</body>
</html>
