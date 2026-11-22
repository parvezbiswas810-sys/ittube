@extends('admin.layout')
@section('title', 'Authentication')
@section('page_title', 'Authentication')

@section('content')
    <div class="page-head">
        <div>
            <h1>Authentication &amp; Google sign-in</h1>
            <p>Control how users sign in. These values are stored in MySQL (the secret is encrypted) and published to the Android app through <span class="mono">/api/v1/app/config</span>.</p>
        </div>
        <div style="display:flex;gap:8px">
            <form method="POST" action="{{ route('admin.auth.test') }}">@csrf
                <button class="btn btn-soft" type="submit">Test configuration</button>
            </form>
            <form method="POST" action="{{ route('admin.auth.toggle') }}">@csrf
                <input type="hidden" name="enable" value="{{ $state['google_login_enabled'] ? 0 : 1 }}">
                <button class="btn {{ $state['google_login_enabled'] ? 'btn-danger' : 'btn-primary' }}" type="submit">
                    {{ $state['google_login_enabled'] ? 'Disable Google login' : 'Enable Google login' }}
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-4" style="margin-bottom:16px">
        <div class="kpi">
            <div class="cell-sub">Google login</div>
            <div class="cell-title" style="font-size:22px">
                <span class="pill {{ $state['google_login_enabled'] ? 'pill-green' : 'pill-gray' }}">{{ $state['google_login_enabled'] ? 'Enabled' : 'Disabled' }}</span>
            </div>
        </div>
        <div class="kpi">
            <div class="cell-sub">Guest mode</div>
            <div class="cell-title" style="font-size:22px">
                <span class="pill {{ $state['guest_mode_enabled'] ? 'pill-blue' : 'pill-gray' }}">{{ $state['guest_mode_enabled'] ? 'Allowed' : 'Sign-in required' }}</span>
            </div>
        </div>
        <div class="kpi">
            <div class="cell-sub">OAuth configuration</div>
            <div class="cell-title" style="font-size:22px">
                <span class="pill {{ $inspection['ok'] ? 'pill-green' : 'pill-amber' }}">{{ $inspection['ok'] ? 'Valid' : 'Incomplete' }}</span>
            </div>
        </div>
        <div class="kpi">
            <div class="cell-sub">Firebase (push)</div>
            <div class="cell-title" style="font-size:22px">
                <span class="pill {{ $state['firebase_configured'] ? 'pill-green' : 'pill-gray' }}">{{ $state['firebase_configured'] ? 'Configured' : 'Not configured' }}</span>
            </div>
        </div>
    </div>

    <div class="split" style="display:grid;grid-template-columns:1.6fr 1fr;gap:16px">
        <form method="POST" action="{{ route('admin.auth.update') }}" class="card">
            @csrf @method('PUT')
            <div class="card-head"><h3>Google OAuth settings</h3><span class="pill pill-blue">Encrypted at rest</span></div>
            <div class="card-pad">
                <div class="grid grid-2" style="gap:16px">
                    <div class="field">
                        <label>Web OAuth Client ID</label>
                        <input type="text" name="google_client_id" value="{{ $state['google_client_id'] }}" placeholder="1234567890-abc.apps.googleusercontent.com">
                        <div class="hint">Used as the token audience. The Android app passes this as <span class="mono">serverClientId</span>.</div>
                    </div>
                    <div class="field">
                        <label>Android OAuth Client ID</label>
                        <input type="text" name="google_android_client_id" value="{{ $state['google_android_client_id'] }}" placeholder="1234567890-android.apps.googleusercontent.com">
                        <div class="hint">Optional reference for the Android client created with your SHA-1 fingerprint.</div>
                    </div>
                    <div class="field">
                        <label>Client secret</label>
                        <input type="password" name="google_client_secret" value="" autocomplete="new-password"
                               placeholder="{{ $state['has_secret'] ? '•••••••• stored — leave blank to keep' : 'Not set — required for browser sign-in fallback' }}">
                        <div class="hint">Encrypted with the app key. It is never returned by the API or shown again after saving.</div>
                    </div>
                    <div class="field">
                        <label>Guest mode</label>
                        <label class="switch">
                            <input type="hidden" name="bool_fields[]" value="guest_mode_enabled">
                            <input type="checkbox" name="guest_mode_enabled" value="1" @checked($state['guest_mode_enabled'])>
                            Allow browsing without an account
                        </label>
                        <div class="hint">When off, public content endpoints require a signed-in user.</div>
                    </div>
                    <div class="field">
                        <label>Google login</label>
                        <label class="switch">
                            <input type="hidden" name="bool_fields[]" value="google_login_enabled">
                            <input type="checkbox" name="google_login_enabled" value="1" @checked($state['google_login_enabled'])>
                            Enable Google sign-in
                        </label>
                        <div class="hint">Run “Test configuration” first. The app shows the Google button only when this is on.</div>
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <div class="hint">Last updated: {{ $state['updated_at'] ?: 'never' }}</div>
                        <div class="hint">Last successful test: {{ $state['verified_at'] ?: 'never' }}</div>
                        @if ($state['last_error'])
                            <div class="hint" style="color:#b42318">{{ $state['last_error'] }}</div>
                        @endif
                    </div>
                </div>
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
                    <button class="btn btn-primary" type="submit">Save settings</button>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-head"><h3>Configuration checks</h3></div>
            <div class="card-pad">
                @foreach ($inspection['checks'] as $key => $check)
                    <div class="list-row" style="align-items:flex-start">
                        <div>
                            <div class="cell-title">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
                            <div class="cell-sub">{{ $check['message'] }}</div>
                        </div>
                        <span class="pill {{ $check['ok'] ? 'pill-green' : 'pill-amber' }}">{{ $check['ok'] ? 'OK' : 'Needs work' }}</span>
                    </div>
                @endforeach
                <div class="alert alert-error" style="margin-top:12px">
                    <div>
                        <b>Not automatable:</b> Google Cloud Console credentials and the Android SHA-1 fingerprint must be created by you.
                        The panel can only store and validate them.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-head"><h3>Required setup (Google Cloud Console)</h3><span class="pill pill-navy">One-time</span></div>
        <div class="card-pad">
            <div class="steps steps-cards">
                <div class="step"><b>1</b><h4>Create the project</h4><p>console.cloud.google.com → new project → APIs &amp; Services → OAuth consent screen (External) → add your support email.</p></div>
                <div class="step"><b>2</b><h4>Web client</h4><p>Credentials → Create credentials → OAuth client ID → <b>Web application</b>. Copy the client ID here. This is the token audience.</p></div>
                <div class="step"><b>3</b><h4>Android client</h4><p>Create another OAuth client → <b>Android</b> → package <span class="mono">com.nexdicodelab.ittube</span> + your release SHA-1 (<span class="mono">keytool -list -v -keystore your.jks</span>).</p></div>
                <div class="step"><b>4</b><h4>Test &amp; enable</h4><p>Save, press Test configuration, then Enable Google login. The app picks it up from <span class="mono">/api/v1/app/config</span> without a rebuild.</p></div>
            </div>
            <p class="hint" style="margin-top:10px">The server verifies every Google ID token against Google's public keys (tokeninfo), the issuer, the audience and the expiry. No client secret is ever sent to the app.</p>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-head"><h3>Reset</h3></div>
        <div class="card-pad" style="display:flex;justify-content:space-between;align-items:center;gap:12px">
            <div>
                <div class="cell-title">Clear Google OAuth configuration</div>
                <div class="cell-sub">Disables Google login and removes the client IDs and stored secret. Existing user accounts are not touched.</div>
            </div>
            <form method="POST" action="{{ route('admin.auth.reset') }}" onsubmit="return confirm('Clear the Google OAuth configuration?')">
                @csrf
                <button class="btn btn-danger" type="submit">Reset configuration</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-head"><h3>Recent authentication activity</h3></div>
        <div class="card-pad">
            @forelse ($recent as $log)
                <div class="list-row">
                    <div><div class="cell-title">{{ $log->description }}</div><div class="cell-sub mono">{{ $log->action }} · {{ $log->created_at?->diffForHumans() }}</div></div>
                </div>
            @empty
                <div class="empty">No authentication changes yet.</div>
            @endforelse
        </div>
    </div>
@endsection
