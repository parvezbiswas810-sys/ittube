<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\GoogleTokenVerifier;
use App\Services\SettingService;
use Illuminate\Http\Request;

class AuthSettingsController extends Controller
{
    public function index()
    {
        SettingService::ensureDefaults();

        $state = [
            'google_login_enabled' => SettingService::bool('google_login_enabled'),
            'google_client_id' => SettingService::get('google_client_id'),
            'google_android_client_id' => SettingService::get('google_android_client_id'),
            'has_secret' => SettingService::hasSecret('google_client_secret'),
            'guest_mode_enabled' => SettingService::bool('guest_mode_enabled', true),
            'registration_enabled' => SettingService::bool('registration_enabled', true),
            'verified_at' => SettingService::get('google_oauth_verified_at'),
            'updated_at' => SettingService::get('google_oauth_updated_at'),
            'last_error' => SettingService::get('google_oauth_last_error'),
            'firebase_configured' => (bool) env('FIREBASE_CREDENTIALS'),
        ];

        $inspection = GoogleTokenVerifier::inspectConfiguration();
        $recent = \App\Models\AdminActivityLog::where('action', 'like', 'auth.%')
            ->orderByDesc('created_at')->limit(8)->get();

        return view('admin.auth-settings.index', compact('state', 'inspection', 'recent'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'google_client_id' => ['nullable', 'string', 'max:255'],
            'google_android_client_id' => ['nullable', 'string', 'max:255'],
            'google_client_secret' => ['nullable', 'string', 'max:255'],
            'guest_mode_enabled' => ['nullable', 'boolean'],
            'google_login_enabled' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['google_client_id']) && ! str_ends_with($data['google_client_id'], '.apps.googleusercontent.com')) {
            return back()->withErrors(['google_client_id' => 'The Web client ID must end with .apps.googleusercontent.com']);
        }

        SettingService::set('google_client_id', $data['google_client_id'] ?? null, 'auth');
        SettingService::set('google_android_client_id', $data['google_android_client_id'] ?? null, 'auth');

        // A blank secret field keeps the stored value: the admin can replace it without
        // ever seeing the previous value.
        if (! empty($data['google_client_secret'])) {
            SettingService::setSecret('google_client_secret', $data['google_client_secret'], 'auth');
        }

        SettingService::set('guest_mode_enabled', (bool) ($data['guest_mode_enabled'] ?? false), 'auth', 'bool');
        SettingService::set('google_login_enabled', (bool) ($data['google_login_enabled'] ?? false), 'auth', 'bool');
        SettingService::set('google_oauth_updated_at', now()->toIso8601String(), 'auth');
        SettingService::flush();

        ActivityLogger::log('auth.settings.update', 'Updated Google authentication settings');

        return back()->with('status', 'Authentication settings saved.');
    }

    public function test()
    {
        $inspection = GoogleTokenVerifier::inspectConfiguration();

        SettingService::set('google_oauth_verified_at', $inspection['ok'] ? now()->toIso8601String() : null, 'auth');
        SettingService::set('google_oauth_last_error', $inspection['ok'] ? null : collect($inspection['checks'])
            ->reject(fn ($c) => $c['ok'])->pluck('message')->implode(' '), 'auth');
        SettingService::flush();

        ActivityLogger::log('auth.test', 'Ran Google OAuth configuration test');

        return back()->with(
            $inspection['ok'] ? 'status' : 'error',
            $inspection['ok']
                ? 'Configuration looks valid. Google sign-in can be enabled.'
                : 'Configuration has problems: '.collect($inspection['checks'])->reject(fn ($c) => $c['ok'])->pluck('message')->implode(' ')
        );
    }

    public function toggle(Request $request)
    {
        $enable = $request->boolean('enable');

        if ($enable) {
            $inspection = GoogleTokenVerifier::inspectConfiguration();
            if (! $inspection['ok']) {
                return back()->with('error', 'Fix the configuration before enabling Google sign-in: '
                    .collect($inspection['checks'])->reject(fn ($c) => $c['ok'])->pluck('message')->implode(' '));
            }
        }

        SettingService::set('google_login_enabled', $enable, 'auth', 'bool');
        SettingService::flush();
        ActivityLogger::log('auth.toggle', $enable ? 'Enabled Google sign-in' : 'Disabled Google sign-in');

        return back()->with('status', $enable ? 'Google sign-in enabled.' : 'Google sign-in disabled.');
    }

    public function reset()
    {
        SettingService::set('google_login_enabled', false, 'auth', 'bool');
        SettingService::set('google_client_id', null, 'auth');
        SettingService::set('google_android_client_id', null, 'auth');
        SettingService::setSecret('google_client_secret', null, 'auth');
        SettingService::set('google_oauth_verified_at', null, 'auth');
        SettingService::set('google_oauth_last_error', null, 'auth');
        SettingService::set('google_oauth_updated_at', now()->toIso8601String(), 'auth');
        SettingService::flush();
        ActivityLogger::log('auth.reset', 'Reset Google OAuth configuration');

        return back()->with('status', 'Google OAuth configuration cleared.');
    }
}
