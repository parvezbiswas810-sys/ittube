<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Install IT TUBE</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin.css') }}">
</head>
<body>
<div class="install-wrap">
    <div class="install-head">
        <img src="{{ asset('assets/logo.png') }}" alt="IT TUBE">
        <h1>Install IT TUBE</h1>
        <p>Laravel backend + Blue Orbit admin panel · Nexdi Code Lab</p>
    </div>

    <div class="steps">
        <div class="step"><b>1</b> Server check</div>
        <div class="step"><b>2</b> Database</div>
        <div class="step"><b>3</b> Super Admin</div>
        <div class="step"><b>4</b> Go live</div>
    </div>

    @if ($errors->any())
        <div class="alert alert-error">
            <div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        </div>
    @endif

    <div class="card" style="margin-bottom:16px">
        <div class="card-head">
            <h3>Server requirements</h3>
            <span class="pill {{ $ready ? 'pill-green' : 'pill-amber' }}">{{ $ready ? 'Ready to install' : 'Fix required items' }}</span>
        </div>
        <div class="card-pad">
            <div class="req-list">
                @foreach ($requirements as $requirement)
                    <div class="req">
                        <span class="dot {{ $requirement['ok'] ? 'ok' : 'bad' }}">{{ $requirement['ok'] ? '✓' : '!' }}</span>
                        <span>{{ $requirement['label'] }}</span>
                        @if (! empty($requirement['optional']))
                            <span class="pill pill-gray" style="margin-left:auto">Optional</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('install.run') }}">
        @csrf
        <div class="card" style="margin-bottom:16px">
            <div class="card-head"><h3>Application</h3></div>
            <div class="card-pad">
                <div class="field">
                    <label>Application URL (no trailing slash)</label>
                    <input name="app_url" value="{{ old('app_url', request()->getSchemeAndHttpHost()) }}" required>
                    <div class="hint">Example: https://api.adsxuit.com — must match the domain that serves the admin panel.</div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:16px">
            <div class="card-head"><h3>MySQL database</h3><span class="pill pill-blue">cPanel friendly</span></div>
            <div class="card-pad">
                <div class="grid grid-2" style="gap:14px">
                    <div class="field">
                        <label>Database host</label>
                        <input name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required>
                    </div>
                    <div class="field">
                        <label>Port</label>
                        <input name="db_port" value="{{ old('db_port', '3306') }}" required>
                    </div>
                    <div class="field">
                        <label>Database name</label>
                        <input name="db_database" value="{{ old('db_database') }}" placeholder="cpuser_ittube" required>
                    </div>
                    <div class="field">
                        <label>Database username</label>
                        <input name="db_username" value="{{ old('db_username') }}" placeholder="cpuser_ittube" required>
                    </div>
                    <div class="field">
                        <label>Database password</label>
                        <input type="password" name="db_password" value="{{ old('db_password') }}">
                    </div>
                </div>
                <div class="hint" style="margin-top:10px">Create the database and user in cPanel → MySQL Databases, then assign all privileges.</div>
            </div>
        </div>

        <div class="card" style="margin-bottom:16px">
            <div class="card-head"><h3>Super Admin account</h3></div>
            <div class="card-pad">
                <div class="grid grid-2" style="gap:14px">
                    <div class="field">
                        <label>Admin name</label>
                        <input name="admin_name" value="{{ old('admin_name') }}" required>
                    </div>
                    <div class="field">
                        <label>Admin email</label>
                        <input type="email" name="admin_email" value="{{ old('admin_email') }}" required>
                    </div>
                    <div class="field">
                        <label>Password (min 10 characters)</label>
                        <input type="password" name="admin_password" required minlength="10">
                    </div>
                    <div class="field">
                        <label>Confirm password</label>
                        <input type="password" name="admin_password_confirmation" required minlength="10">
                    </div>
                    @if ($installKeyRequired)
                        <div class="field" style="grid-column:1/-1">
                            <label>One-time installation key</label>
                            <input type="password" name="install_key" required>
                            <div class="hint">Set in .env as ITTUBE_INSTALL_KEY.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:18px">
            <div class="card-pad" style="display:flex;gap:14px;align-items:flex-start">
                <span class="pill pill-amber">Security</span>
                <div class="cell-sub" style="line-height:1.6">
                    The installer writes your <b>.env</b> file, runs migrations, seeds default data and creates the Super Admin.
                    It locks itself automatically after a successful install and returns 404 afterwards. Delete
                    <span class="mono">app/Http/Controllers/InstallerController.php</span> and the <span class="mono">/install</span> routes
                    from <span class="mono">routes/web.php</span> after going live for maximum safety.
                </div>
            </div>
        </div>

        <button class="btn btn-primary btn-block" type="submit" @disabled(! $ready)>Install IT TUBE now</button>
    </form>

    <p style="text-align:center;color:var(--muted);font-size:12px;margin-top:22px">IT TUBE · Blue Orbit Edition · Nexdi Code Lab</p>
</div>
</body>
</html>
