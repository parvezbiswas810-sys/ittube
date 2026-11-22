@extends('admin.layout')
@section('title', 'Categories')
@section('page_title', 'Category management')

@section('content')
    <div class="page-head">
        <div><h1>Categories</h1><p>These categories power the Android app explore and upload screens.</p></div>
        <div class="spacer"></div>
        <button class="btn btn-primary" data-modal-open="modal-create-category">New category</button>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Category</th><th>Slug</th><th>Videos</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>
                            <div class="row-user">
                                @if ($category->icon_url)
                                    <img src="{{ $category->icon_url }}" class="avatar" style="object-fit:cover" alt="">
                                @else
                                    <span class="avatar">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                                @endif
                                <div>
                                    <div class="cell-title">{{ $category->name }}</div>
                                    <div class="cell-sub">{{ \Illuminate\Support\Str::limit($category->description, 60) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="cell-sub mono">{{ $category->slug }}</td>
                        <td class="cell-sub">{{ $category->videos_count }}</td>
                        <td class="cell-sub">{{ $category->sort_order }}</td>
                        <td><span class="pill {{ $category->is_active ? 'pill-green' : 'pill-gray' }}">{{ $category->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td>
                            <button class="btn btn-ghost btn-sm" data-modal-open="modal-edit-{{ $category->id }}">Edit</button>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="Delete this category?" style="display:inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty"><h4>No categories</h4><p>Create your first category.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal" id="modal-create-category">
        <div class="modal-box">
            <div class="modal-head"><h3>Create category</h3><button class="icon-btn" type="button" data-modal-close>✕</button></div>
            <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body form-grid">
                    <div class="field"><label>Name</label><input name="name" required></div>
                    <div class="field"><label>Description</label><textarea name="description"></textarea></div>
                    <div class="grid grid-2">
                        <div class="field"><label>Sort order</label><input type="number" name="sort_order" value="0" min="0"></div>
                        <div class="field"><label>Icon</label><input type="file" name="icon" accept="image/*"></div>
                    </div>
                    <label class="switch"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                </div>
                <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Create</button></div>
            </form>
        </div>
    </div>

    @foreach ($categories as $category)
        <div class="modal" id="modal-edit-{{ $category->id }}">
            <div class="modal-box">
                <div class="modal-head"><h3>Edit {{ $category->name }}</h3><button class="icon-btn" type="button" data-modal-close>✕</button></div>
                <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="modal-body form-grid">
                        <div class="field"><label>Name</label><input name="name" value="{{ $category->name }}" required></div>
                        <div class="field"><label>Description</label><textarea name="description">{{ $category->description }}</textarea></div>
                        <div class="grid grid-2">
                            <div class="field"><label>Sort order</label><input type="number" name="sort_order" value="{{ $category->sort_order }}" min="0"></div>
                            <div class="field"><label>Replace icon</label><input type="file" name="icon" accept="image/*"></div>
                        </div>
                        <label class="switch"><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Active</label>
                    </div>
                    <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save</button></div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
