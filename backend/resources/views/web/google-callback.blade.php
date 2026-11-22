<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{{ $error ? 'Sign-in failed' : 'Signing in' }} · IT TUBE</title>
<style>
  :root { --blue:#0866F5; --navy:#071B68; --cyan:#00C7F2; --ice:#F4F9FF; --text:#14213D; --muted:#64748B; --line:#E3EAF5; }
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
    padding: 24px; background: radial-gradient(1200px 600px at 50% -10%, #EAF5FF 0%, var(--ice) 45%, #FFFFFF 100%);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: var(--text);
  }
  .card {
    width: 100%; max-width: 380px; background: #fff; border: 1px solid var(--line); border-radius: 24px;
    padding: 32px 26px; text-align: center; box-shadow: 0 24px 60px rgba(7, 27, 104, .12);
  }
  .mark {
    width: 64px; height: 64px; margin: 0 auto 18px; border-radius: 22px; display: grid; place-items: center;
    font-size: 30px; color: #fff; background: linear-gradient(135deg, var(--blue), var(--cyan));
    box-shadow: 0 12px 28px rgba(8, 102, 245, .32);
  }
  h1 { margin: 0 0 8px; font-size: 20px; color: var(--navy); letter-spacing: -.01em; }
  p { margin: 0 0 22px; font-size: 14px; line-height: 1.55; color: var(--muted); }
  .btn {
    display: block; padding: 14px 18px; border-radius: 14px; background: var(--blue); color: #fff;
    font-weight: 600; font-size: 15px; text-decoration: none; box-shadow: 0 12px 24px rgba(8, 102, 245, .28);
  }
  .brand { margin-top: 18px; font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: #94A3B8; }
</style>
</head>
<body>
  <main class="card">
    <div class="mark">{{ $error ? '!' : '✓' }}</div>
    @if ($error)
      <h1>Sign-in failed</h1>
      <p>{{ $error }}</p>
    @else
      <h1>Signed in</h1>
      <p>Taking you back to the IT TUBE app…</p>
    @endif
    <a class="btn" href="{{ $deepLink }}">Open IT TUBE</a>
    <div class="brand">IT TUBE · Nexdi Code Lab</div>
  </main>
  @if (! $error)
    <script>setTimeout(function () { window.location.href = @json($deepLink); }, 250);</script>
  @endif
</body>
</html>
