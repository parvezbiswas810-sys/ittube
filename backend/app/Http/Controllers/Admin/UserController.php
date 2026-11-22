<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\Media;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['profile', 'channel'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($inner) => $inner
                ->where('username', 'like', '%'.$request->string('q').'%')
                ->orWhere('email', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['profile', 'channel', 'videos' => fn ($q) => $q->latest()->limit(12)]);
        $reports = Report::where('reportable_type', User::class)->where('reportable_id', $user->id)->latest()->limit(10)->get();

        return view('admin.users.show', compact('user', 'reports'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'role' => ['required', 'in:user,creator'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email,'.$user->id],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user->update(['email' => strtolower($data['email']), 'role' => $data['role']]);

        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $profile->display_name = $data['display_name'] ?? $profile->display_name;
        $profile->bio = $data['bio'] ?? $profile->bio;
        if ($request->hasFile('avatar')) {
            $profile->avatar = Media::storeImage($request->file('avatar'), 'avatars', $profile->avatar);
        }
        $profile->save();

        ActivityLogger::log('user.update', 'Updated user '.$user->username, $user);

        return back()->with('status', 'User updated successfully.');
    }

    public function status(Request $request, User $user)
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,suspended,banned'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update([
            'status' => $data['status'],
            'suspension_reason' => $data['status'] === 'active' ? null : ($data['reason'] ?? null),
        ]);

        if ($data['status'] !== 'active') {
            $user->tokens()->delete();
        }

        ActivityLogger::log('user.status', 'Set user '.$user->username.' status to '.$data['status'], $user);

        return back()->with('status', 'User status updated.');
    }

    public function destroy(User $user)
    {
        $username = $user->username;
        $user->tokens()->delete();
        $user->delete();

        ActivityLogger::log('user.delete', 'Deleted user '.$username, $user);

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }
}
