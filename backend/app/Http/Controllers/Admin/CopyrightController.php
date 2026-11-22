<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CopyrightReport;
use App\Models\Video;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class CopyrightController extends Controller
{
    public function index(Request $request)
    {
        $reports = CopyrightReport::with(['reporter.profile', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->whereIn('status', ['pending', 'review']))
            ->when($request->filled('type'), fn ($q) => $q->where('content_type', $request->string('type')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($w) => $w->where('rights_holder', 'like', $term)
                    ->orWhere('contact_email', 'like', $term)
                    ->orWhere('description', 'like', $term));
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'pending' => CopyrightReport::whereIn('status', ['pending', 'review'])->count(),
            'approved' => CopyrightReport::where('status', 'approved')->count(),
            'rejected' => CopyrightReport::where('status', 'rejected')->count(),
            'disputed' => CopyrightReport::where('status', 'disputed')->count(),
        ];

        return view('admin.copyright.index', compact('reports', 'counts'));
    }

    public function show(CopyrightReport $report)
    {
        $report->load(['reporter.profile', 'reviewer']);

        $related = CopyrightReport::where('content_type', $report->content_type)
            ->where('content_id', $report->content_id)
            ->where('id', '!=', $report->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.copyright.show', compact('report', 'related'));
    }

    public function resolve(Request $request, CopyrightReport $report)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,review,approved,rejected,disputed'],
            'action' => ['nullable', 'in:none,restrict,remove,suspend_account'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $action = $data['action'] ?? 'none';
        $video = $report->content_type === 'video' ? Video::find($report->content_id) : null;

        if ($action === 'restrict' && $video) {
            $video->update(['status' => 'restricted']);
        }

        if ($action === 'remove' && $video) {
            $video->update(['status' => 'suspended']);
        }

        if ($action === 'suspend_account' && $video) {
            $video->user?->forceFill(['status' => 'suspended', 'suspension_reason' => 'Copyright violation'])->save();
        }

        $report->update([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? $report->admin_note,
            'reviewed_by' => auth('admin')->id(),
            'reviewed_at' => now(),
        ]);

        ActivityLogger::log('copyright.resolve', 'Reviewed copyright report #'.$report->id.' as '.$data['status'].($action !== 'none' ? ' ('.$action.')' : ''));

        return back()->with('status', 'Copyright report updated.');
    }
}
