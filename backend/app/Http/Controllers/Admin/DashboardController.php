<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\Report;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'creators' => Channel::count(),
            'videos' => Video::where('type', 'video')->count(),
            'shorts' => Video::where('type', 'short')->count(),
            'views' => (int) Video::sum('views_count'),
            'comments' => Comment::count(),
            'subscriptions' => Subscription::count(),
            'reports' => Report::where('status', 'pending')->count(),
            'pending' => Video::where('status', 'pending')->count(),
            'new_users' => User::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));

        $userGrowth = $days->map(fn ($day) => User::whereDate('created_at', $day)->count())->values();
        $uploadGrowth = $days->map(fn ($day) => Video::whereDate('created_at', $day)->count())->values();
        $viewsGrowth = $days->map(fn ($day) => (int) DB::table('video_views')->whereDate('viewed_at', $day)->count())->values();

        $latestVideos = Video::with(['channel', 'user'])->orderByDesc('created_at')->limit(6)->get();
        $newUsers = User::with('profile')->orderByDesc('created_at')->limit(6)->get();
        $pendingReports = Report::with('reporter')->where('status', 'pending')->orderByDesc('created_at')->limit(6)->get();
        $recentActivity = AdminActivityLog::with('admin')->orderByDesc('created_at')->limit(8)->get();

        $categoryBreakdown = \App\Models\Category::withCount('videos')->orderByDesc('videos_count')->limit(6)->get();

        return view('admin.dashboard', compact(
            'stats', 'days', 'userGrowth', 'uploadGrowth', 'viewsGrowth',
            'latestVideos', 'newUsers', 'pendingReports', 'recentActivity', 'categoryBreakdown'
        ));
    }
}
