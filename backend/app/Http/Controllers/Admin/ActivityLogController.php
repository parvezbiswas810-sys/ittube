<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AdminActivityLog::with('admin')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($inner) => $inner
                ->where('action', 'like', '%'.$request->string('q').'%')
                ->orWhere('description', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('admin'), fn ($q) => $q->where('admin_id', $request->integer('admin')))
            ->orderByDesc('created_at')
            ->paginate(40)
            ->withQueryString();

        $admins = \App\Models\AdminUser::orderBy('name')->get(['id', 'name']);

        return view('admin.logs.index', compact('logs', 'admins'));
    }
}
