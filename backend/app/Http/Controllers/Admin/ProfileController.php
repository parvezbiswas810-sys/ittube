<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        return view('admin.profile.index', ['admin' => auth('admin')->user()->load('role')]);
    }

    public function update(Request $request)
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120', Rule::unique('admin_users', 'email')->ignore($admin->id)],
        ]);

        $admin->update($data);
        ActivityLogger::log('profile.update', 'Updated own admin profile', $admin);

        return back()->with('status', 'Profile updated.');
    }

    public function password(Request $request)
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $admin->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $admin->update(['password' => $data['password']]);
        ActivityLogger::log('profile.password', 'Changed own admin password', $admin);

        return back()->with('status', 'Password changed successfully.');
    }
}
