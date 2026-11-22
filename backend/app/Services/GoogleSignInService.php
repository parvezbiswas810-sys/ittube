<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;

/**
 * Verifies a Google ID token and returns the matching local account.
 *
 * Shared by the Android API (id_token from Google Play services) and the
 * browser flow (id_token from the OAuth code exchange), so both entry points
 * provision accounts in exactly the same way.
 */
class GoogleSignInService
{
    /**
     * @return array{ok: bool, error?: string, field?: string, status?: int, user?: User, token?: string, is_new?: bool}
     */
    public function signIn(string $idToken, ?string $ip = null, string $deviceName = 'android-app'): array
    {
        if (! SettingService::bool('google_login_enabled')) {
            return ['ok' => false, 'error' => 'Google sign-in is currently disabled.', 'status' => 403];
        }

        $verified = GoogleTokenVerifier::verify($idToken);

        if (! $verified['ok']) {
            return [
                'ok' => false,
                'error' => $verified['error'] ?? 'Google sign-in failed.',
                'field' => 'id_token',
                'status' => 401,
            ];
        }

        $claims = $verified['claims'];
        $googleId = (string) ($claims['sub'] ?? '');
        $email = strtolower((string) ($claims['email'] ?? ''));

        if ($googleId === '' || $email === '') {
            return ['ok' => false, 'error' => 'Google did not return a usable identity.', 'status' => 401];
        }

        if (isset($claims['email_verified']) && ! filter_var($claims['email_verified'], FILTER_VALIDATE_BOOLEAN)) {
            return ['ok' => false, 'error' => 'Your Google email address is not verified.', 'status' => 403];
        }

        $user = User::where('google_id', $googleId)->first() ?? User::where('email', $email)->first();

        if ($user && $user->google_id && $user->google_id !== $googleId) {
            return ['ok' => false, 'error' => 'This email is already linked to a different Google account.', 'status' => 409];
        }

        if ($user && $user->status === 'banned') {
            return ['ok' => false, 'error' => 'This account has been banned.', 'status' => 403];
        }

        if ($user && $user->status === 'suspended') {
            return ['ok' => false, 'error' => 'This account is suspended. '.($user->suspension_reason ?? ''), 'status' => 403];
        }

        $user = DB::transaction(function () use ($user, $googleId, $email, $claims, $ip) {
            if (! $user) {
                $username = $this->uniqueUsernameFromEmail($email);
                $user = User::create([
                    'username' => $username,
                    'email' => $email,
                    'google_id' => $googleId,
                    'google_avatar' => $claims['picture'] ?? null,
                    'auth_provider' => 'google',
                    'password' => null,
                    'role' => 'user',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);

                UserProfile::create([
                    'user_id' => $user->id,
                    'display_name' => $claims['name'] ?? $username,
                    'avatar' => null,
                ]);

                NotificationPreference::create(['user_id' => $user->id]);
            } else {
                $user->forceFill([
                    'google_id' => $googleId,
                    'google_avatar' => $claims['picture'] ?? $user->google_avatar,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'last_login_at' => now(),
                    'last_login_ip' => $ip,
                ])->save();

                if (! $user->profile) {
                    UserProfile::create([
                        'user_id' => $user->id,
                        'display_name' => $claims['name'] ?? $user->username,
                    ]);
                }
            }

            $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $ip])->save();

            return $user;
        });

        return [
            'ok' => true,
            'user' => $user,
            'token' => $user->createToken($deviceName)->plainTextToken,
            'is_new' => $user->wasRecentlyCreated,
        ];
    }

    protected function uniqueUsernameFromEmail(string $email): string
    {
        $base = preg_replace('/[^A-Za-z0-9_.]/', '', strstr($email, '@', true) ?: 'user') ?: 'user';
        $base = substr($base, 0, 24);
        $candidate = $base;
        $i = 1;

        while (User::withTrashed()->where('username', $candidate)->exists()) {
            $candidate = $base.$i;
            $i++;
        }

        return $candidate;
    }
}
