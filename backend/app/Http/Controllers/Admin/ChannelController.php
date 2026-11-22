<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Channel;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function index(Request $request)
    {
        $channels = Channel::with(['user', 'category'])
            ->withCount(['videos', 'subscriptions'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('handle', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('subscribers_count')
            ->paginate(20)
            ->withQueryString();

        return view('admin.channels.index', compact('channels'));
    }

    public function show(Channel $channel)
    {
        $channel->load(['user.profile', 'category', 'links']);
        $videos = $channel->videos()->latest()->limit(12)->get();
        $categories = Category::active()->orderBy('name')->get();

        return view('admin.channels.show', compact('channel', 'videos', 'categories'));
    }

    public function update(Request $request, Channel $channel)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'handle' => ['required', 'string', 'max:60', 'alpha_dash', 'unique:channels,handle,'.$channel->id],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_verified' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $data['is_verified'] = $request->boolean('is_verified');
        $data['is_featured'] = $request->boolean('is_featured');

        $channel->update($data);
        ActivityLogger::log('channel.update', 'Updated channel @'.$channel->handle, $channel);

        return back()->with('status', 'Channel updated successfully.');
    }

    public function status(Request $request, Channel $channel)
    {
        $data = $request->validate(['status' => ['required', 'in:active,suspended']]);
        $channel->update(['status' => $data['status']]);

        ActivityLogger::log('channel.status', 'Set channel @'.$channel->handle.' to '.$data['status'], $channel);

        return back()->with('status', 'Channel status updated.');
    }

    public function feature(Channel $channel)
    {
        $channel->update(['is_featured' => ! $channel->is_featured]);
        ActivityLogger::log('channel.feature', ($channel->is_featured ? 'Featured' : 'Unfeatured').' channel @'.$channel->handle, $channel);

        return back()->with('status', 'Channel updated.');
    }
}
