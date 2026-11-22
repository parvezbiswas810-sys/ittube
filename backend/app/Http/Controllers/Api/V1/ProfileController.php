<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\UserProfile;
use App\Support\ApiResponse;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return ApiResponse::success(new UserResource($request->user()->load(['profile', 'channel.category'])));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'country' => ['nullable', 'string', 'max:80'],
            'gender' => ['nullable', 'in:male,female,other'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'privacy' => ['nullable', 'array'],
            'privacy.show_history' => ['nullable', 'boolean'],
            'privacy.show_subscriptions' => ['nullable', 'boolean'],
        ]);

        $profile = UserProfile::firstOrCreate(['user_id' => $user->id]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = Media::storeImage($request->file('avatar'), 'avatars', $profile->avatar);
        }

        $profile->fill($data)->save();

        return ApiResponse::success(new UserResource($user->fresh(['profile', 'channel'])), 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            return ApiResponse::error('Current password is incorrect.', ['current_password' => ['Incorrect password.']], 422);
        }

        $request->user()->forceFill(['password' => $data['password']])->save();
        $request->user()->tokens()->where('id', '!=', $request->user()->currentAccessToken()?->id)->delete();

        return ApiResponse::success(null, 'Password updated successfully.');
    }
}
