<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleTokenVerifier
{
    public const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    /**
     * Verifies a Google ID token. Signature verification is delegated to Google's
     * tokeninfo endpoint; issuer, audience and expiry are also checked locally so a
     * misconfigured client id can never authenticate a token from another app.
     *
     * @return array{ok: bool, claims?: array, error?: string}
     */
    public static function verify(string $idToken): array
    {
        $claims = static::decodePayload($idToken);

        if (! $claims) {
            return ['ok' => false, 'error' => 'The Google token is malformed.'];
        }

        $audience = static::allowedAudiences();
        if (! $audience) {
            return ['ok' => false, 'error' => 'Google sign-in is not configured on the server.'];
        }

        if (! in_array($claims['iss'] ?? '', self::ISSUERS, true)) {
            return ['ok' => false, 'error' => 'The Google token has an unexpected issuer.'];
        }

        if (! in_array($claims['aud'] ?? '', $audience, true)) {
            return ['ok' => false, 'error' => 'The Google token was issued for a different application.'];
        }

        if (($claims['exp'] ?? 0) < time()) {
            return ['ok' => false, 'error' => 'The Google token has expired. Please sign in again.'];
        }

        try {
            $response = Http::timeout(10)->acceptJson()
                ->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Could not reach Google to verify the token. Try again.'];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'error' => 'Google rejected the token signature.'];
        }

        $verified = $response->json();
        foreach (['sub', 'aud', 'exp'] as $key) {
            if (($verified[$key] ?? null) != ($claims[$key] ?? null)) {
                return ['ok' => false, 'error' => 'The Google token could not be verified.'];
            }
        }

        return ['ok' => true, 'claims' => $verified];
    }

    public static function allowedAudiences(): array
    {
        return array_values(array_filter([
            SettingService::get('google_client_id'),
            SettingService::get('google_android_client_id'),
        ]));
    }

    public static function decodePayload(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = strtr($parts[1], '-_', '+/');
        $decoded = base64_decode(str_pad($payload, (int) (ceil(strlen($payload) / 4) * 4), '=', STR_PAD_RIGHT), true);

        if ($decoded === false) {
            return null;
        }

        $json = json_decode($decoded, true);

        return is_array($json) ? $json : null;
    }

    /**
     * Configuration check used by the admin panel. It never contacts Google with a
     * secret and never reveals the stored secret value.
     *
     * @return array{ok: bool, checks: array<string, array{ok: bool, message: string}>}
     */
    public static function inspectConfiguration(): array
    {
        $clientId = (string) SettingService::get('google_client_id');
        $androidId = (string) SettingService::get('google_android_client_id');
        $checks = [];

        $checks['web_client_id'] = [
            'ok' => $clientId !== '' && str_ends_with($clientId, '.apps.googleusercontent.com'),
            'message' => $clientId === ''
                ? 'Add the Web OAuth Client ID from Google Cloud Console (APIs & Services > Credentials).'
                : (str_ends_with($clientId, '.apps.googleusercontent.com')
                    ? 'Web client ID format looks valid.'
                    : 'A Web client ID must end with .apps.googleusercontent.com'),
        ];

        $checks['android_client_id'] = [
            'ok' => $androidId === '' || str_ends_with($androidId, '.apps.googleusercontent.com'),
            'message' => $androidId === ''
                ? 'Optional: the Android OAuth client ID (needed for the SHA-1 fingerprint entry).'
                : 'Android client ID format looks valid.',
        ];

        $checks['google_reachable'] = [
            'ok' => static::googleReachable(),
            'message' => 'Server can reach accounts.google.com.',
        ];

        $checks['firebase'] = [
            'ok' => (bool) env('FIREBASE_CREDENTIALS'),
            'message' => env('FIREBASE_CREDENTIALS')
                ? 'Firebase credentials are present (push notifications available).'
                : 'Firebase is not configured on the server; push notifications stay disabled until google-services.json / credentials are supplied.',
        ];

        $ok = $checks['web_client_id']['ok'] && $checks['google_reachable']['ok'];

        return ['ok' => $ok, 'checks' => $checks];
    }

    protected static function googleReachable(): bool
    {
        try {
            $response = Http::timeout(6)->get('https://accounts.google.com/.well-known/openid-configuration');

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
