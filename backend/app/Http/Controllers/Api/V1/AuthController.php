<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Channel;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\SettingService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        if (! SettingService::bool('registration_enabled', true)) {
            return ApiResponse::error('Registration is currently disabled.', [], 403);
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9_.]+$/', 'unique:users,username'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'display_name' => ['nullable', 'string', 'max:80'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'terms_accepted' => ['accepted'],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);

        $user = DB::transaction(function () use ($request, $data) {
            $user = User::create([
                'username' => $data['username'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'role' => 'creator',
                'status' => 'active',
                'email_verified_at' => SettingService::bool('email_verification_required') ? null : now(),
            ]);

            $avatar = $request->hasFile('avatar')
                ? \App\Support\Media::storeImage($request->file('avatar'), 'avatars')
                : null;

            UserProfile::create([
                'user_id' => $user->id,
                'display_name' => $data['display_name'] ?? $data['username'],
                'avatar' => $avatar,
            ]);

            NotificationPreference::create(['user_id' => $user->id]);

            return $user;
        });

        $token = $user->createToken($data['device_name'] ?? 'android-app')->plainTextToken;
        $user->load(['profile', 'channel']);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Account created successfully.', 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $data['login'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return ApiResponse::error('Invalid credentials.', ['login' => ['The provided credentials are incorrect.']], 401);
        }

        if ($user->status === 'banned') {
            return ApiResponse::error('This account has been banned.', [], 403);
        }

        if ($user->status === 'suspended') {
            return ApiResponse::error('This account is suspended. '.($user->suspension_reason ?? ''), [], 403);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $token = $user->createToken($data['device_name'] ?? 'android-app')->plainTextToken;
        $user->load(['profile', 'channel']);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Logged in successfully.');
    }

    public function google(Request $request)
    {
        if (! SettingService::bool('google_login_enabled')) {
            return ApiResponse::error('Google sign-in is currently disabled.', [], 403);
        }

        $data = $request->validate([
            'id_token' => ['required', 'string', 'min:50', 'max:4096'],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);

        $verified = \App\Services\GoogleTokenVerifier::verify($data['id_token']);

        if (! $verified['ok']) {
            return ApiResponse::error($verified['error'] ?? 'Google sign-in failed.', ['id_token' => [$verified['error'] ?? 'Invalid token.']], 401);
        }

        $claims = $verified['claims'];
        $googleId = (string) ($claims['sub'] ?? '');
        $email = strtolower((string) ($claims['email'] ?? ''));

        if ($googleId === '' || $email === '') {
            return ApiResponse::error('Google did not return a usable identity.', [], 401);
        }

        if (isset($claims['email_verified']) && ! filter_var($claims['email_verified'], FILTER_VALIDATE_BOOLEAN)) {
            return ApiResponse::error('Your Google email address is not verified.', [], 403);
        }

        $user = User::where('google_id', $googleId)->first() ?? User::where('email', $email)->first();

        if ($user && $user->google_id && $user->google_id !== $googleId) {
            return ApiResponse::error('This email is already linked to a different Google account.', [], 409);
        }

        if ($user && $user->status === 'banned') {
            return ApiResponse::error('This account has been banned.', [], 403);
        }

        if ($user && $user->status === 'suspended') {
            return ApiResponse::error('This account is suspended. '.($user->suspension_reason ?? ''), [], 403);
        }

        $user = DB::transaction(function () use ($user, $googleId, $email, $claims, $request) {
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
                    'last_login_ip' => $request->ip(),
                ])->save();

                if (! $user->profile) {
                    UserProfile::create([
                        'user_id' => $user->id,
                        'display_name' => $claims['name'] ?? $user->username,
                    ]);
                }
            }

            $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

            return $user;
        });

        $token = $user->createToken($data['device_name'] ?? 'android-app')->plainTextToken;
        $user->load(['profile', 'channel']);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
            'is_new_user' => $user->wasRecentlyCreated,
        ], 'Signed in with Google.');
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

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['profile', 'channel.category']);

        return ApiResponse::success(new UserResource($user));
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', strtolower($data['email']))->first();

        if ($user) {
            $token = Password::broker('users')->createToken($user);
            $link = rtrim(config('app.url'), '/').'/reset-password/'.$token.'?email='.urlencode($user->email);

            try {
                \Illuminate\Support\Facades\Mail::raw(
                    "Hello {$user->username},\n\nUse this link or code to reset your IT TUBE password:\n\n{$link}\n\nReset code: {$token}\n\nIf you did not request this, ignore this email.",
                    fn ($message) => $message->to($user->email)->subject('IT TUBE - Password Reset')
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return ApiResponse::success(
            ['debug_token' => config('app.debug') && $user ? Password::broker('users')->createToken($user) : null],
            'If the email exists, a password reset link has been sent.'
        );
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        $status = Password::broker('users')->reset(
            ['email' => strtolower($data['email']), 'password' => $data['password'], 'password_confirmation' => $request->input('password_confirmation'), 'token' => $data['token']],
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error('Unable to reset password.', ['token' => [__($status)]], 422);
        }

        return ApiResponse::success(null, 'Password reset successfully. Please log in.');
    }

    public function sendVerification(Request $request)
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return ApiResponse::success(null, 'Email is already verified.');
        }

        $link = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'api.email.verify',
            now()->addHours(24),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Verify your IT TUBE email: {$link}",
                fn ($message) => $message->to($user->email)->subject('IT TUBE - Verify your email')
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return ApiResponse::success(['verification_url' => config('app.debug') ? $link : null], 'Verification link sent.');
    }

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = User::find($id);

        if (! $user || ! hash_equals(sha1($user->email), $hash)) {
            return ApiResponse::error('Invalid verification link.', [], 422);
        }

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return ApiResponse::success(null, 'Email verified successfully.');
    }

    public function deleteAccount(Request $request)
    {
        $data = $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($data['password'], $request->user()->password)) {
            return ApiResponse::error('Password is incorrect.', ['password' => ['Incorrect password.']], 422);
        }

        $user = $request->user();
        $user->forceFill(['deletion_requested_at' => now()])->save();
        $user->tokens()->delete();

        return ApiResponse::success(null, 'Account deletion requested. Your account will be reviewed and removed.');
    }

    protected function uniqueHandle(string $base): string
    {
        $handle = Str::slug($base, '') ?: 'creator';
        $candidate = $handle;
        $i = 1;
        while (Channel::withTrashed()->where('handle', $candidate)->exists()) {
            $candidate = $handle.$i++;
        }

        return $candidate;
    }
}
