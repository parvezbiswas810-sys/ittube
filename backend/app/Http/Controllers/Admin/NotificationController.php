<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::with('user')
            ->orderByDesc('created_at')
            ->paginate(25);

        $users = User::where('status', 'active')->orderBy('username')->limit(200)->get(['id', 'username']);

        return view('admin.notifications.index', compact('notifications', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:2000'],
            'target' => ['required', 'in:all,selected'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        if ($data['target'] === 'all') {
            Notification::create([
                'user_id' => null,
                'type' => 'admin_announcement',
                'title' => $data['title'],
                'body' => $data['body'] ?? null,
            ]);
            $count = User::where('status', 'active')->count();
        } else {
            $ids = $data['user_ids'] ?? [];
            foreach ($ids as $id) {
                NotificationService::send($id, 'admin_announcement', $data['title'], $data['body'] ?? null);
            }
            $count = count($ids);
        }

        ActivityLogger::log('notification.send', 'Sent announcement "'.$data['title'].'" to '.$count.' user(s)');

        return back()->with('status', 'Announcement sent to '.$count.' user(s).');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();
        ActivityLogger::log('notification.delete', 'Deleted notification #'.$notification->id);

        return back()->with('status', 'Notification deleted.');
    }
}
