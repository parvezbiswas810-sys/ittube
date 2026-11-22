<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ActivityLogger;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('videos')->orderBy('sort_order')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['icon'] = Media::storeImage($request->file('icon'), 'categories');
        $data['is_active'] = $request->boolean('is_active', true);

        $category = Category::create($data);
        ActivityLogger::log('category.create', 'Created category '.$category->name, $category);

        return back()->with('status', 'Category created successfully.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:categories,name,'.$category->id],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        if ($request->hasFile('icon')) {
            $data['icon'] = Media::storeImage($request->file('icon'), 'categories', $category->icon);
        }
        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);
        ActivityLogger::log('category.update', 'Updated category '.$category->name, $category);

        return back()->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        ActivityLogger::log('category.delete', 'Deleted category '.$category->name, $category);

        return back()->with('status', 'Category deleted.');
    }
}
