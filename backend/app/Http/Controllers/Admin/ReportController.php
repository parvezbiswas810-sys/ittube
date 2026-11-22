<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use App\Models\Video;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = Report::with(['reporter.profile', 'admin'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->where('status', 'pending'))
            ->when($request->filled('type'), fn ($q) => $q->where('reportable_type', 'like', '%'.$request->string('type').'%'))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.reports.index', compact('reports'));
    }

    public function resolve(Request $request, Report $report)
    {
        $data = $request->validate([
            'status' => ['required', 'in:reviewed,dismissed,actioned'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'action' => ['nullable', 'in:none,remove_content,suspend_account'],
        ]);

        $action = $data['action'] ?? 'none';

        if ($action === 'remove_content') {
            if ($report->reportable_type === Video::class) {
                $report->reportable?->update(['status' => 'suspended']);
            } elseif ($report->reportable_type === Comment::class) {
                $report->reportable?->update(['status' => 'removed']);
            }
        }

        if ($action === 'suspend_account') {
            $target = $report->reportable;
            $userId = null;
            if ($target instanceof Video) {
                $userId = $target->user_id;
            } elseif ($target instanceof Comment) {
                $userId = $target->user_id;
            } elseif ($target instanceof User) {
                $userId = $target->id;
            }

            if ($userId) {
                User::where('id', $userId)->update(['status' => 'suspended', 'suspension_reason' => 'Suspended after moderation review.']);
                User::find($userId)?->tokens()->delete();
            }
        }

        $report->update([
            'status' => $data['status'],
            'admin_id' => auth('admin')->id(),
            'admin_note' => $data['admin_note'] ?? null,
            'resolved_at' => now(),
        ]);

        ActivityLogger::log('report.resolve', 'Resolved report #'.$report->id.' as '.$data['status'], $report);

        return back()->with('status', 'Report updated.');
    }
}
