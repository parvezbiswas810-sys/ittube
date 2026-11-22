<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingService
{
    public const DEFAULTS = [
        ['key' => 'app_name', 'value' => 'IT TUBE', 'group' => 'general', 'type' => 'string'],
        ['key' => 'app_tagline', 'value' => 'Premium Video Streaming Platform', 'group' => 'general', 'type' => 'string'],
        ['key' => 'app_description', 'value' => 'IT TUBE by Nexdi Code Lab - a premium futuristic video streaming platform.', 'group' => 'general', 'type' => 'text'],
        ['key' => 'app_logo', 'value' => null, 'group' => 'general', 'type' => 'file'],
        ['key' => 'contact_email', 'value' => 'support@adsxuit.com', 'group' => 'general', 'type' => 'string'],
        ['key' => 'support_url', 'value' => 'https://api.adsxuit.com', 'group' => 'general', 'type' => 'string'],
        ['key' => 'terms_url', 'value' => 'https://api.adsxuit.com/terms', 'group' => 'legal', 'type' => 'string'],
        ['key' => 'privacy_url', 'value' => 'https://api.adsxuit.com/privacy', 'group' => 'legal', 'type' => 'string'],
        ['key' => 'registration_enabled', 'value' => '1', 'group' => 'features', 'type' => 'bool'],
        ['key' => 'email_verification_required', 'value' => '0', 'group' => 'features', 'type' => 'bool'],
        ['key' => 'uploads_enabled', 'value' => '1', 'group' => 'features', 'type' => 'bool'],
        ['key' => 'comments_enabled', 'value' => '1', 'group' => 'features', 'type' => 'bool'],
        ['key' => 'shorts_enabled', 'value' => '1', 'group' => 'features', 'type' => 'bool'],
        ['key' => 'upload_approval_required', 'value' => '0', 'group' => 'features', 'type' => 'bool'],
        ['key' => 'maintenance_mode', 'value' => '0', 'group' => 'features', 'type' => 'bool'],
        ['key' => 'maintenance_message', 'value' => 'IT TUBE is under maintenance. Please check back shortly.', 'group' => 'features', 'type' => 'text'],
        ['key' => 'max_upload_size_mb', 'value' => '512', 'group' => 'uploads', 'type' => 'int'],
        ['key' => 'allowed_video_formats', 'value' => 'mp4,mov,mkv,webm,avi', 'group' => 'uploads', 'type' => 'string'],
        ['key' => 'allowed_image_formats', 'value' => 'jpg,jpeg,png,webp', 'group' => 'uploads', 'type' => 'string'],
        ['key' => 'max_video_duration', 'value' => '0', 'group' => 'uploads', 'type' => 'int'],
        ['key' => 'app_version', 'value' => '1.0.0', 'group' => 'app', 'type' => 'string'],
        ['key' => 'app_min_version', 'value' => '1.0.0', 'group' => 'app', 'type' => 'string'],
        ['key' => 'app_force_update', 'value' => '0', 'group' => 'app', 'type' => 'bool'],
        ['key' => 'app_announcement', 'value' => null, 'group' => 'app', 'type' => 'text'],
        ['key' => 'android_store_url', 'value' => null, 'group' => 'app', 'type' => 'string'],
        ['key' => 'google_signin_enabled', 'value' => '0', 'group' => 'features', 'type' => 'bool'],
        ['key' => 'primary_color', 'value' => '#0866F5', 'group' => 'appearance', 'type' => 'string'],
        ['key' => 'accent_color', 'value' => '#00C7F2', 'group' => 'appearance', 'type' => 'string'],
        ['key' => 'google_login_enabled', 'value' => '0', 'group' => 'auth', 'type' => 'bool'],
        ['key' => 'google_client_id', 'value' => null, 'group' => 'auth', 'type' => 'string'],
        ['key' => 'google_android_client_id', 'value' => null, 'group' => 'auth', 'type' => 'string'],
        ['key' => 'google_client_secret', 'value' => null, 'group' => 'auth', 'type' => 'secret'],
        ['key' => 'google_oauth_verified_at', 'value' => null, 'group' => 'auth', 'type' => 'string'],
        ['key' => 'google_oauth_updated_at', 'value' => null, 'group' => 'auth', 'type' => 'string'],
        ['key' => 'google_oauth_last_error', 'value' => null, 'group' => 'auth', 'type' => 'text'],
        ['key' => 'guest_mode_enabled', 'value' => '1', 'group' => 'auth', 'type' => 'bool'],
        ['key' => 'downloads_enabled', 'value' => '0', 'group' => 'features', 'type' => 'bool'],
        ['key' => 'live_stream_enabled', 'value' => '0', 'group' => 'features', 'type' => 'bool'],
        ['key' => 'stream_provider', 'value' => null, 'group' => 'streaming', 'type' => 'string'],
        ['key' => 'stream_rtmp_url', 'value' => null, 'group' => 'streaming', 'type' => 'string'],
        ['key' => 'stream_playback_base', 'value' => null, 'group' => 'streaming', 'type' => 'string'],
        ['key' => 'stream_provider_key', 'value' => null, 'group' => 'streaming', 'type' => 'secret'],
        ['key' => 'ads_enabled', 'value' => '0', 'group' => 'ads', 'type' => 'bool'],
        ['key' => 'ads_provider', 'value' => null, 'group' => 'ads', 'type' => 'string'],
        ['key' => 'ads_client_tag', 'value' => null, 'group' => 'ads', 'type' => 'string'],
        ['key' => 'monetization_enabled', 'value' => '0', 'group' => 'monetization', 'type' => 'bool'],
        ['key' => 'monetization_min_subscribers', 'value' => '1000', 'group' => 'monetization', 'type' => 'int'],
        ['key' => 'monetization_min_views', 'value' => '10000', 'group' => 'monetization', 'type' => 'int'],
    ];

    protected static ?array $cache = null;

    public static function all(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        try {
            static::$cache = AppSetting::query()->get()->mapWithKeys(fn ($s) => [$s->key => $s->castedValue()])->all();
        } catch (\Throwable $e) {
            static::$cache = [];
        }

        return static::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_array($value)) {
            $value = json_encode($value);
        }

        AppSetting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group, 'type' => $type]);
        static::$cache = null;
    }

    public static function secret(string $key): ?string
    {
        $value = static::get($key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function setSecret(string $key, ?string $value, string $group = 'auth'): void
    {
        static::set($key, $value === null || $value === '' ? null : Crypt::encryptString($value), $group, 'secret');
    }

    public static function hasSecret(string $key): bool
    {
        return static::secret($key) !== null;
    }

    public static function ensureDefaults(): void
    {
        try {
            $existing = AppSetting::query()->pluck('key')->all();
        } catch (\Throwable $e) {
            return;
        }

        foreach (static::DEFAULTS as $setting) {
            if (! in_array($setting['key'], $existing, true)) {
                AppSetting::create([
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                ]);
            }
        }

        static::$cache = null;
    }

    public static function flush(): void
    {
        static::$cache = null;
        Cache::forget('ittube.settings');
    }
}
