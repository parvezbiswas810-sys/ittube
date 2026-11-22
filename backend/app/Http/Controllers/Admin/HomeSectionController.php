<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Video;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class HomeSectionController extends Controller
{
    public function index()
    {
        $sections = HomeSection::with('category')->orderBy('sort_order')->get();
        $categories = Category::active()->orderBy('name')->get();
        $videos = Video::with('channel')->where('status', 'published')->orderByDesc('published_at')->limit(60)->get();

        return view('admin.home.index', compact('sections', 'categories', 'videos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:featured,trending,recent,popular,category,custom,shorts'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['item_limit'] = $data['item_limit'] ?? 12;

        $section = HomeSection::create($data);
        ActivityLogger::log('home_section.create', 'Created home section '.$section->title, $section);

        return back()->with('status', 'Home section created.');
    }

    public function update(Request $request, HomeSection $section)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:featured,trending,recent,popular,category,custom,shorts'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $section->update($data);
        ActivityLogger::log('home_section.update', 'Updated home section '.$section->title, $section);

        return back()->with('status', 'Home section updated.');
    }

    public function destroy(HomeSection $section)
    {
        $section->delete();
        ActivityLogger::log('home_section.delete', 'Deleted home section '.$section->title, $section);

        return back()->with('status', 'Home section deleted.');
    }

    public function attachVideos(Request $request, HomeSection $section)
    {
        $data = $request->validate([
            'video_ids' => ['nullable', 'array'],
            'video_ids.*' => ['integer', 'exists:videos,id'],
        ]);

        $ids = $data['video_ids'] ?? [];
        $section->videos()->sync(collect($ids)->mapWithKeys(fn ($id, $index) => [$id => ['position' => $index]])->all());
        ActivityLogger::log('home_section.videos', 'Updated videos in section '.$section->title, $section);

        return back()->with('status', 'Section videos updated.');
    }
}
