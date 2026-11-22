<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $roles = AdminRole::with('permissions')->withCount('admins')->orderByDesc('is_super')->get();
        $permissions = AdminPermission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        $admins = AdminUser::with('role')->orderBy('name')->get();

        return view('admin.roles.index', compact('roles', 'permissions', 'admins'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:admin_permissions,id'],
        ]);

        $role = AdminRole::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'is_super' => false,
            'description' => $data['description'] ?? null,
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);
        ActivityLogger::log('role.create', 'Created role '.$role->name, $role);

        return back()->with('status', 'Role created.');
    }

    public function update(Request $request, AdminRole $role)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:admin_permissions,id'],
        ]);

        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        if (! $role->is_super) {
            $role->permissions()->sync($data['permissions'] ?? []);
        }

        ActivityLogger::log('role.update', 'Updated role '.$role->name, $role);

        return back()->with('status', 'Role updated.');
    }

    public function destroy(AdminRole $role)
    {
        if ($role->is_super) {
            return back()->withErrors(['role' => 'The Super Admin role cannot be deleted.']);
        }

        $role->delete();
        ActivityLogger::log('role.delete', 'Deleted role '.$role->name, $role);

        return back()->with('status', 'Role deleted.');
    }
}
