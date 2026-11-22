<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Report;
use App\Models\Video;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use App\Support\Media;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $videos = Video::with(['channel', 'user', 'category'])
            ->where('type', 'video')
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('visibility'), fn ($q) => $q->where('visibility', $request->string('visibility')))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('admin.videos.index', compact('videos', 'categories'));
    }

    public function shorts(Request $request)
    {
        $videos = Video::with(['channel', 'user'])
            ->where('type', 'short')
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(24)
            ->withQueryString();

        return view('admin.shorts.index', compact('videos'));
    }

    public function show(Video $video)
    {
        $video->load(['channel', 'user.profile', 'category', 'tags']);
        $reports = Report::where('reportable_type', Video::class)->where('reportable_id', $video->id)->latest()->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.videos.show', compact('video', 'reports', 'categories'));
    }

    public function update(Request $request, Video $video)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['required', 'in:'.implode(',', Video::STATUSES)],
            'allow_download' => ['nullable', 'boolean'],
            'visibility' => ['required', 'in:'.implode(',', Video::VISIBILITIES)],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = Media::storeImage($request->file('thumbnail'), 'thumbnails/'.date('Y/m'), $video->thumbnail);
        }

        if ($data['status'] === 'published' && ! $video->published_at) {
            $data['published_at'] = now();
        }

        $wasPublished = $video->status === 'published';
        $video->update($data);

        if ($data['status'] === 'published' && ! $wasPublished) {
            NotificationService::notifySubscribers($video->channel, $video);
        }

        ActivityLogger::log('video.update', 'Updated video #'.$video->id.' ('.$video->title.')', $video);

        return back()->with('status', 'Video updated successfully.');
    }

    public function status(Request $request, Video $video)
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Video::STATUSES)],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $wasPublished = $video->status === 'published';
        $video->update([
            'status' => $data['status'],
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'published_at' => $data['status'] === 'published' ? ($video->published_at ?? now()) : $video->published_at,
        ]);

        if ($data['status'] === 'published' && ! $wasPublished) {
            NotificationService::notifySubscribers($video->channel, $video);
        }

        ActivityLogger::log('video.status', 'Set video #'.$video->id.' status to '.$data['status'], $video);

        return back()->with('status', 'Video status updated.');
    }

    public function feature(Video $video)
    {
        $video->update(['is_featured' => ! $video->is_featured]);
        ActivityLogger::log('video.feature', ($video->is_featured ? 'Featured' : 'Unfeatured').' video #'.$video->id, $video);

        return back()->with('status', 'Video updated.');
    }

    public function destroy(Video $video)
    {
        $video->delete();
        ActivityLogger::log('video.delete', 'Removed video #'.$video->id.' ('.$video->title.')', $video);

        return redirect()->route('admin.videos.index')->with('status', 'Video removed.');
    }
}
