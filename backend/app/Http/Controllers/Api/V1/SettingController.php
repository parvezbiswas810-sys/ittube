<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomeSection;
use App\Services\SettingService;
use App\Support\ApiResponse;
use App\Support\Media;

class SettingController extends Controller
{
    public function publicSettings()
    {
        return ApiResponse::success([
            'app_name' => SettingService::get('app_name', 'IT TUBE'),
            'tagline' => SettingService::get('app_tagline'),
            'description' => SettingService::get('app_description'),
            'logo_url' => Media::url(SettingService::get('app_logo')),
            'logo_asset' => 'assets/logo.png',
            'google_login_enabled' => SettingService::bool('google_login_enabled'),
            'google_client_id' => SettingService::get('google_client_id'),
            'guest_mode_enabled' => SettingService::bool('guest_mode_enabled', true),
            'contact_email' => SettingService::get('contact_email'),
            'support_url' => SettingService::get('support_url'),
            'terms_url' => SettingService::get('terms_url'),
            'privacy_url' => SettingService::get('privacy_url'),
            'version' => SettingService::get('app_version'),
            'colors' => [
                'primary' => SettingService::get('primary_color', '#0866F5'),
                'accent' => SettingService::get('accent_color', '#00C7F2'),
            ],
        ]);
    }

    public function appConfig()
    {
        SettingService::ensureDefaults();

        return ApiResponse::success([
            'app_name' => SettingService::get('app_name', 'IT TUBE'),
            'version' => SettingService::get('app_version', '1.0.0'),
            'min_version' => SettingService::get('app_min_version', '1.0.0'),
            'force_update' => SettingService::bool('app_force_update'),
            'store_url' => SettingService::get('android_store_url'),
            'announcement' => SettingService::get('app_announcement'),
            'logo_url' => Media::url(SettingService::get('app_logo')),
            'auth' => [
                'google_login_enabled' => SettingService::bool('google_login_enabled'),
                'google_client_id' => SettingService::get('google_client_id'),
                'guest_mode_enabled' => SettingService::bool('guest_mode_enabled', true),
                'email_login_enabled' => SettingService::bool('email_login_enabled', true),
                'registration_enabled' => SettingService::bool('registration_enabled', true),
            ],
            'google_login_enabled' => SettingService::bool('google_login_enabled'),
            'google_client_id' => SettingService::get('google_client_id'),
            'guest_mode_enabled' => SettingService::bool('guest_mode_enabled', true),
            'downloads_enabled' => SettingService::bool('downloads_enabled'),
            'live_stream_enabled' => SettingService::bool('live_stream_enabled'),
            'ads_enabled' => SettingService::bool('ads_enabled'),
            'monetization_enabled' => SettingService::bool('monetization_enabled'),
            'maintenance' => [
                'enabled' => SettingService::bool('maintenance_mode'),
                'message' => SettingService::get('maintenance_message'),
            ],
            'features' => [
                'registration' => SettingService::bool('registration_enabled', true),
                'uploads' => SettingService::bool('uploads_enabled', true),
                'comments' => SettingService::bool('comments_enabled', true),
                'shorts' => SettingService::bool('shorts_enabled', true),
                'google_signin' => SettingService::bool('google_login_enabled'),
                'google_signin_enabled' => SettingService::bool('google_login_enabled'),
                'guest_mode' => SettingService::bool('guest_mode_enabled', true),
                'downloads' => SettingService::bool('downloads_enabled'),
                'live_stream' => SettingService::bool('live_stream_enabled'),
                'ads' => SettingService::bool('ads_enabled'),
                'monetization' => SettingService::bool('monetization_enabled'),
                'upload_approval' => SettingService::bool('upload_approval_required'),
                'email_verification' => SettingService::bool('email_verification_required'),
            ],
            'uploads' => [
                'max_size_mb' => (int) SettingService::get('max_upload_size_mb', 512),
                'formats' => explode(',', (string) SettingService::get('allowed_video_formats', 'mp4,mov,mkv,webm')),
                'max_duration' => (int) SettingService::get('max_video_duration', 0),
            ],
            'links' => [
                'terms' => SettingService::get('terms_url'),
                'privacy' => SettingService::get('privacy_url'),
                'support' => SettingService::get('support_url'),
            ],
            'home_sections' => HomeSection::where('is_active', true)->orderBy('sort_order')->get()->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'type' => $s->type,
                'category_id' => $s->category_id,
                'limit' => $s->item_limit,
            ]),
            'categories' => Category::active()->orderBy('sort_order')->get()->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'icon_url' => $c->icon_url,
            ]),
        ]);
    }
}
