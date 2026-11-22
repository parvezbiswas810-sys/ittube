<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::query()
            ->where(fn ($q) => $q->where('user_id', $request->user()->id)->orWhereNull('user_id'))
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        $unread = Notification::query()
            ->where(fn ($q) => $q->where('user_id', $request->user()->id)->orWhereNull('user_id'))
            ->where('is_read', false)
            ->count();

        return ApiResponse::success([
            'unread_count' => $unread,
            'notifications' => NotificationResource::collection($notifications->getCollection()),
        ], 'Notifications loaded', 200, [
            'current_page' => $notifications->currentPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'last_page' => $notifications->lastPage(),
            'has_more' => $notifications->hasMorePages(),
        ]);
    }

    public function read(Request $request, int $id)
    {
        $notification = Notification::where('id', $id)
            ->where(fn ($q) => $q->where('user_id', $request->user()->id)->orWhereNull('user_id'))
            ->first();

        if (! $notification) {
            return ApiResponse::error('Notification not found.', [], 404);
        }

        $notification->update(['is_read' => true, 'read_at' => now()]);

        return ApiResponse::success(new NotificationResource($notification), 'Notification marked as read.');
    }

    public function readAll(Request $request)
    {
        $count = Notification::query()
            ->where(fn ($q) => $q->where('user_id', $request->user()->id)->orWhereNull('user_id'))
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return ApiResponse::success(['updated' => $count], 'All notifications marked as read.');
    }

    public function destroy(Request $request, int $id)
    {
        $deleted = Notification::where('id', $id)->where('user_id', $request->user()->id)->delete();

        return $deleted
            ? ApiResponse::success(null, 'Notification deleted.')
            : ApiResponse::error('Notification not found.', [], 404);
    }

    public function preferences(Request $request)
    {
        $preferences = NotificationPreference::firstOrCreate(['user_id' => $request->user()->id]);

        return ApiResponse::success($preferences);
    }

    public function updatePreferences(Request $request)
    {
        $data = $request->validate([
            'new_subscriber' => ['nullable', 'boolean'],
            'new_upload' => ['nullable', 'boolean'],
            'comment_reply' => ['nullable', 'boolean'],
            'channel_activity' => ['nullable', 'boolean'],
            'admin_announcement' => ['nullable', 'boolean'],
            'history_paused' => ['nullable', 'boolean'],
        ]);

        $preferences = NotificationPreference::firstOrCreate(['user_id' => $request->user()->id]);
        $preferences->update($data);

        return ApiResponse::success($preferences->fresh(), 'Notification preferences updated.');
    }
}
