<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\GoogleSignInService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Browser fallback for Google sign-in.
 *
 * The native Android flow needs an Android OAuth client (package + SHA-1) in
 * the same Cloud project as the Web client. When that is missing or the device
 * has no Play services, the app opens this endpoint in the system browser: the
 * code exchange happens server-side with the Web client, and the resulting
 * Sanctum token is handed back to the app through a custom-scheme deep link.
 */
class GoogleAuthController extends Controller
{
    public const APP_SCHEME = 'com.nexdicodelab.ittube';

    public function redirect(Request $request)
    {
        $clientId = (string) SettingService::get('google_client_id');
        $secret = (string) SettingService::secret('google_client_secret');

        if (! SettingService::bool('google_login_enabled')) {
            return $this->failure('Google sign-in is disabled.', 'Enable it in Admin → Authentication.');
        }

        if ($clientId === '' || $secret === '') {
            return $this->failure(
                'Google sign-in is not configured.',
                'Add both the Web client ID and the client secret in Admin → Authentication.'
            );
        }

        $state = Str::random(40);
        Cache::put($this->stateKey($state), true, now()->addMinutes(10));

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $this->callbackUrl(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
            'access_type' => 'online',
            'include_granted_scopes' => 'true',
        ]));
    }

    public function callback(Request $request, GoogleSignInService $signIn)
    {
        $state = (string) $request->query('state', '');

        // One-time state: a replayed or forged callback is rejected outright.
        if ($state === '' || ! Cache::pull($this->stateKey($state))) {
            return $this->failure('This sign-in link has expired.', 'Start the Google sign-in again from the app.');
        }

        if ($request->filled('error')) {
            return $this->failure('Google sign-in was cancelled.', 'You can close this window and try again.');
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return $this->failure('Google did not return an authorization code.', 'Start the sign-in again from the app.');
        }

        $clientId = (string) SettingService::get('google_client_id');
        $secret = (string) SettingService::secret('google_client_secret');

        $response = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->callbackUrl(),
        ]);

        $idToken = (string) ($response->json('id_token') ?? '');

        if (! $response->successful() || $idToken === '') {
            return $this->failure(
                'Google rejected the authorization code.',
                'The Web client ID and secret must belong to the same client. Try again from the app.'
            );
        }

        $result = $signIn->signIn($idToken, $request->ip(), 'google-web');

        if (! $result['ok']) {
            return $this->failure($result['error'], 'You can close this window and try again.');
        }

        return view('web.google-callback', [
            'deepLink' => self::APP_SCHEME.'://oauth?'.http_build_query([
                'token' => $result['token'],
                'status' => 'ok',
            ]),
            'error' => null,
        ]);
    }

    protected function callbackUrl(): string
    {
        return url('/auth/google/callback');
    }

    protected function stateKey(string $state): string
    {
        return 'google_web_state:'.$state;
    }

    protected function failure(string $title, string $hint)
    {
        return response()->view('web.google-callback', [
            'deepLink' => self::APP_SCHEME.'://oauth?status=error',
            'error' => $title.' '.$hint,
        ], 400);
    }
}
