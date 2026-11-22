<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdPlacement;
use App\Models\MonetizationProfile;
use App\Services\ActivityLogger;
use App\Services\SettingService;
use Illuminate\Http\Request;

class PlatformController extends Controller
{
    public function index()
    {
        SettingService::ensureDefaults();

        $state = [
            'live_stream_enabled' => SettingService::bool('live_stream_enabled'),
            'stream_provider' => SettingService::get('stream_provider'),
            'stream_rtmp_url' => SettingService::get('stream_rtmp_url'),
            'stream_playback_base' => SettingService::get('stream_playback_base'),
            'has_provider_key' => SettingService::hasSecret('stream_provider_key'),
            'downloads_enabled' => SettingService::bool('downloads_enabled'),
            'ads_enabled' => SettingService::bool('ads_enabled'),
            'ads_provider' => SettingService::get('ads_provider'),
            'ads_client_tag' => SettingService::get('ads_client_tag'),
            'monetization_enabled' => SettingService::bool('monetization_enabled'),
            'monetization_min_subscribers' => (int) SettingService::get('monetization_min_subscribers', 1000),
            'monetization_min_views' => (int) SettingService::get('monetization_min_views', 10000),
        ];

        $placements = AdPlacement::orderBy('position')->orderBy('id')->get();
        $monetized = MonetizationProfile::with('user')->orderByDesc('updated_at')->limit(50)->get();

        return view('admin.platform.index', compact('state', 'placements', 'monetized'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'stream_provider' => ['nullable', 'string', 'max:60'],
            'stream_rtmp_url' => ['nullable', 'string', 'max:255'],
            'stream_playback_base' => ['nullable', 'string', 'max:255'],
            'stream_provider_key' => ['nullable', 'string', 'max:255'],
            'ads_provider' => ['nullable', 'string', 'max:60'],
            'ads_client_tag' => ['nullable', 'string', 'max:255'],
            'monetization_min_subscribers' => ['nullable', 'integer', 'min:0'],
            'monetization_min_views' => ['nullable', 'integer', 'min:0'],
            'live_stream_enabled' => ['nullable', 'boolean'],
            'downloads_enabled' => ['nullable', 'boolean'],
            'ads_enabled' => ['nullable', 'boolean'],
            'monetization_enabled' => ['nullable', 'boolean'],
        ]);

        foreach (['stream_provider', 'stream_rtmp_url', 'stream_playback_base', 'ads_provider', 'ads_client_tag'] as $key) {
            SettingService::set($key, $data[$key] ?? null, $key === 'ads_provider' || $key === 'ads_client_tag' ? 'ads' : 'streaming');
        }

        if (! empty($data['stream_provider_key'])) {
            SettingService::setSecret('stream_provider_key', $data['stream_provider_key'], 'streaming');
        }

        foreach (['live_stream_enabled', 'downloads_enabled', 'ads_enabled', 'monetization_enabled'] as $key) {
            SettingService::set($key, $request->boolean($key), $key === 'monetization_enabled' ? 'monetization' : ($key === 'ads_enabled' ? 'ads' : 'features'), 'bool');
        }

        SettingService::set('monetization_min_subscribers', (int) ($data['monetization_min_subscribers'] ?? 1000), 'monetization', 'int');
        SettingService::set('monetization_min_views', (int) ($data['monetization_min_views'] ?? 10000), 'monetization', 'int');
        SettingService::flush();

        ActivityLogger::log('platform.update', 'Updated streaming, ads and monetization configuration');

        return back()->with('status', 'Platform configuration saved.');
    }

    public function storePlacement(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'position' => ['required', 'in:preroll,midroll,postroll'],
            'offset_seconds' => ['nullable', 'integer', 'min:0'],
            'ad_tag_url' => ['nullable', 'url', 'max:500'],
            'provider' => ['nullable', 'string', 'max:40'],
        ]);

        $placement = AdPlacement::create($data + ['is_active' => false]);
        ActivityLogger::log('ads.placement.create', 'Created ad placement #'.$placement->id);

        return back()->with('status', 'Ad placement created.');
    }

    public function togglePlacement(AdPlacement $placement)
    {
        $placement->update(['is_active' => ! $placement->is_active]);
        ActivityLogger::log('ads.placement.toggle', 'Toggled ad placement #'.$placement->id);

        return back()->with('status', 'Ad placement updated.');
    }

    public function destroyPlacement(AdPlacement $placement)
    {
        $placement->delete();
        ActivityLogger::log('ads.placement.delete', 'Deleted ad placement #'.$placement->id);

        return back()->with('status', 'Ad placement removed.');
    }

    public function updateMonetization(Request $request, MonetizationProfile $profile)
    {
        $data = $request->validate([
            'status' => ['required', 'in:ineligible,review,eligible,enabled,suspended'],
            'ads_enabled' => ['nullable', 'boolean'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $profile->update([
            'status' => $data['status'],
            'ads_enabled' => (bool) ($data['ads_enabled'] ?? false),
            'admin_note' => $data['admin_note'] ?? $profile->admin_note,
        ]);

        ActivityLogger::log('monetization.update', 'Set monetization status for user #'.$profile->user_id.' to '.$data['status']);

        return back()->with('status', 'Creator monetization status updated.');
    }
}
