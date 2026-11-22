@extends('admin.layout')
@section('title', 'Admin roles')
@section('page_title', 'Roles & permissions')

@section('content')
    <div class="page-head">
        <div><h1>Admin roles</h1><p>Control which admin sections each team member can access.</p></div>
        <div class="spacer"></div>
        <button class="btn btn-primary" data-modal-open="modal-create-role">New role</button>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Role</th><th>Admins</th><th>Permissions</th><th>Actions</th></tr></thead>
                <tbody>
                @foreach ($roles as $role)
                    <tr>
                        <td>
                            <div class="cell-title">{{ $role->name }} @if($role->is_super)<span class="pill pill-blue">Super</span>@endif</div>
                            <div class="cell-sub">{{ $role->description }}</div>
                        </td>
                        <td class="cell-sub">{{ $role->admins_count }}</td>
                        <td class="cell-sub">{{ $role->is_super ? 'All permissions' : $role->permissions->count().' permissions' }}</td>
                        <td>
                            <div style="display:flex;gap:6px">
                                <button class="btn btn-ghost btn-sm" data-modal-open="edit-role-{{ $role->id }}">Edit</button>
                                @unless ($role->is_super)
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" data-confirm="Delete this role?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>Admin accounts</h3></div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th></tr></thead>
                <tbody>
                @foreach ($admins as $admin)
                    <tr>
                        <td><div class="row-user"><span class="avatar">{{ strtoupper(substr($admin->name, 0, 1)) }}</span><div class="cell-title">{{ $admin->name }}</div></div></td>
                        <td class="cell-sub">{{ $admin->email }}</td>
                        <td><span class="pill pill-blue">{{ $admin->role?->name ?? 'No role' }}</span></td>
                        <td><span class="pill {{ $admin->status === 'active' ? 'pill-green' : 'pill-gray' }}">{{ ucfirst($admin->status) }}</span></td>
                        <td class="cell-sub">{{ $admin->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-pad">
            <p class="cell-sub">Create additional admin accounts with <span class="mono">php artisan ittube:create-admin email@example.com</span> and assign roles here.</p>
        </div>
    </div>

    <div class="modal" id="modal-create-role">
        <div class="modal-box">
            <div class="modal-head"><h3>Create role</h3><button class="icon-btn" type="button" data-modal-close>✕</button></div>
            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf
                <div class="modal-body form-grid">
                    <div class="field"><label>Role name</label><input name="name" required></div>
                    <div class="field"><label>Description</label><input name="description"></div>
                    <div class="field">
                        <label>Permissions</label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                            @foreach ($permissions as $group => $items)
                                @foreach ($items as $permission)
                                    <label class="switch"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}"> {{ $permission->name }}</label>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Create role</button></div>
            </form>
        </div>
    </div>

    @foreach ($roles as $role)
        <div class="modal" id="edit-role-{{ $role->id }}">
            <div class="modal-box">
                <div class="modal-head"><h3>Edit {{ $role->name }}</h3><button class="icon-btn" type="button" data-modal-close>✕</button></div>
                <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                    @csrf @method('PUT')
                    <div class="modal-body form-grid">
                        <div class="field"><label>Role name</label><input name="name" value="{{ $role->name }}" required></div>
                        <div class="field"><label>Description</label><input name="description" value="{{ $role->description }}"></div>
                        <div class="field">
                            <label>Permissions</label>
                            @if ($role->is_super)
                                <div class="alert alert-success" style="margin:0">Super Admin always has every permission.</div>
                            @else
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                                    @foreach ($permissions as $group => $items)
                                        @foreach ($items as $permission)
                                            <label class="switch"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($role->permissions->contains($permission->id))> {{ $permission->name }}</label>
                                        @endforeach
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save role</button></div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
