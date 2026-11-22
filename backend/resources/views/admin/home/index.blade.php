@extends('admin.layout')
@section('title', 'Home page')
@section('page_title', 'Home page management')

@section('content')
    <div class="page-head">
        <div><h1>Home page sections</h1><p>The Android home feed is built from these sections, in order.</p></div>
        <div class="spacer"></div>
        <button class="btn btn-primary" data-modal-open="modal-create-section">New section</button>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Section</th><th>Type</th><th>Category</th><th>Limit</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($sections as $section)
                    <tr>
                        <td><div class="cell-title">{{ $section->title }}</div></td>
                        <td><span class="pill pill-blue">{{ ucfirst($section->type) }}</span></td>
                        <td class="cell-sub">{{ $section->category?->name ?? '—' }}</td>
                        <td class="cell-sub">{{ $section->item_limit }}</td>
                        <td class="cell-sub">{{ $section->sort_order }}</td>
                        <td><span class="pill {{ $section->is_active ? 'pill-green' : 'pill-gray' }}">{{ $section->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <button class="btn btn-ghost btn-sm" data-modal-open="edit-section-{{ $section->id }}">Edit</button>
                                @if ($section->type === 'custom')
                                    <button class="btn btn-soft btn-sm" data-modal-open="videos-section-{{ $section->id }}">Videos</button>
                                @endif
                                <form method="POST" action="{{ route('admin.home.destroy', $section) }}" data-confirm="Delete this section?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty"><h4>No sections</h4><p>Create your first home section.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal" id="modal-create-section">
        <div class="modal-box">
            <div class="modal-head"><h3>Create section</h3><button class="icon-btn" type="button" data-modal-close>✕</button></div>
            <form method="POST" action="{{ route('admin.home.store') }}">
                @csrf
                <div class="modal-body form-grid">
                    <div class="field"><label>Title</label><input name="title" required></div>
                    <div class="field">
                        <label>Type</label>
                        <select name="type">
                            @foreach (['featured', 'trending', 'recent', 'popular', 'category', 'custom', 'shorts'] as $type)
                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Category (for category sections)</label>
                        <select name="category_id">
                            <option value="">None</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-2">
                        <div class="field"><label>Item limit</label><input type="number" name="item_limit" value="12" min="1" max="50"></div>
                        <div class="field"><label>Sort order</label><input type="number" name="sort_order" value="0" min="0"></div>
                    </div>
                    <label class="switch"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                </div>
                <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Create</button></div>
            </form>
        </div>
    </div>

    @foreach ($sections as $section)
        <div class="modal" id="edit-section-{{ $section->id }}">
            <div class="modal-box">
                <div class="modal-head"><h3>Edit {{ $section->title }}</h3><button class="icon-btn" type="button" data-modal-close>✕</button></div>
                <form method="POST" action="{{ route('admin.home.update', $section) }}">
                    @csrf @method('PUT')
                    <div class="modal-body form-grid">
                        <div class="field"><label>Title</label><input name="title" value="{{ $section->title }}" required></div>
                        <div class="field">
                            <label>Type</label>
                            <select name="type">
                                @foreach (['featured', 'trending', 'recent', 'popular', 'category', 'custom', 'shorts'] as $type)
                                    <option value="{{ $type }}" @selected($section->type === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Category</label>
                            <select name="category_id">
                                <option value="">None</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($section->category_id === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-2">
                            <div class="field"><label>Item limit</label><input type="number" name="item_limit" value="{{ $section->item_limit }}" min="1" max="50"></div>
                            <div class="field"><label>Sort order</label><input type="number" name="sort_order" value="{{ $section->sort_order }}" min="0"></div>
                        </div>
                        <label class="switch"><input type="checkbox" name="is_active" value="1" @checked($section->is_active)> Active</label>
                    </div>
                    <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save</button></div>
                </form>
            </div>
        </div>
    @endforeach

    @foreach ($sections->where('type', 'custom') as $section)
        <div class="modal" id="videos-section-{{ $section->id }}">
            <div class="modal-box">
                <div class="modal-head"><h3>{{ $section->title }} videos</h3><button class="icon-btn" type="button" data-modal-close>✕</button></div>
                <form method="POST" action="{{ route('admin.home.videos', $section) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="field">
                            <label>Select videos (Ctrl/Cmd click for multiple)</label>
                            <select name="video_ids[]" multiple size="12" style="min-height:220px">
                                @foreach ($videos as $video)
                                    <option value="{{ $video->id }}" @selected($section->videos->contains($video->id))>{{ $video->title }} — {{ $video->channel?->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-foot"><button class="btn btn-ghost" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save videos</button></div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
