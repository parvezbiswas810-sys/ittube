<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\ActivityLogger;
use App\Services\SettingService;
use App\Support\Media;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        // Secrets are managed on their dedicated pages and never rendered in bulk forms.
        $settings = AppSetting::where('type', '!=', 'secret')
            ->orderBy('group')->orderBy('key')->get()->groupBy('group');

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $input = $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable', 'string', 'max:5000'],
            'app_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
        ]);

        $submitted = $input['settings'] ?? [];
        $boolFields = (array) $request->input('bool_fields', []);

        foreach (AppSetting::all() as $setting) {
            if (in_array($setting->type, ['file', 'secret'], true)) {
                continue;
            }

            if ($setting->type === 'bool') {
                if (isset($submitted[$setting->key])) {
                    $value = '1';
                } elseif (in_array($setting->key, $boolFields, true)) {
                    $value = '0';
                } else {
                    continue;
                }
            } elseif (array_key_exists($setting->key, $submitted)) {
                $value = $submitted[$setting->key];
            } else {
                continue;
            }

            SettingService::set($setting->key, $value, $setting->group, $setting->type);
        }

        if ($request->hasFile('app_logo')) {
            $path = Media::storeImage($request->file('app_logo'), 'branding', SettingService::get('app_logo'));
            SettingService::set('app_logo', $path, 'general', 'file');
        }

        SettingService::flush();
        ActivityLogger::log('settings.update', 'Updated application settings');

        return back()->with('status', 'Settings saved successfully.');
    }
}
