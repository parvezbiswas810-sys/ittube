<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CopyrightReport;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CopyrightController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user('sanctum');

        $data = $request->validate([
            'content_type' => ['required', 'in:video,short,audio,channel'],
            'content_id' => ['nullable', 'integer'],
            'content_url' => ['nullable', 'string', 'max:500'],
            'rights_holder' => ['required', 'string', 'max:150'],
            'reason' => ['required', 'in:copyright,unauthorized_music,unauthorized_video,impersonation,other'],
            'description' => ['nullable', 'string', 'max:2000'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt', 'max:8192'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_email' => [$user ? 'nullable' : 'required', 'email', 'max:150'],
        ]);

        if (! $data['content_id'] && ! $data['content_url']) {
            return ApiResponse::error('Provide the content id or the content URL.', [
                'content_id' => ['A content id or URL is required.'],
            ], 422);
        }

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = \App\Support\Media::storeDocument($request->file('evidence'), 'copyright');
        }

        $report = CopyrightReport::create([
            'reporter_id' => $user?->id,
            'content_type' => $data['content_type'],
            'content_id' => $data['content_id'] ?? null,
            'content_url' => $data['content_url'] ?? null,
            'rights_holder' => $data['rights_holder'],
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'evidence_path' => $evidencePath,
            'contact_name' => $data['contact_name'] ?? $user?->username,
            'contact_email' => $data['contact_email'] ?? $user?->email,
            'status' => 'pending',
        ]);

        return ApiResponse::success([
            'id' => $report->id,
            'status' => $report->status,
        ], 'Copyright report submitted. Our team will review it.', 201);
    }

    public function mine(Request $request)
    {
        $reports = CopyrightReport::where('reporter_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::success($reports->through(fn ($r) => [
            'id' => $r->id,
            'content_type' => $r->content_type,
            'content_id' => $r->content_id,
            'rights_holder' => $r->rights_holder,
            'reason' => $r->reason,
            'status' => $r->status,
            'admin_note' => $r->admin_note,
            'created_at' => $r->created_at?->toIso8601String(),
            'reviewed_at' => $r->reviewed_at?->toIso8601String(),
        ]));
    }
}
