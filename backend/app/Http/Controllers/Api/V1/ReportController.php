<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use App\Models\Video;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:video,short,channel,comment,user'],
            'id' => ['required', 'integer'],
            'reason' => ['required', 'in:spam,harassment,violence,sexual_content,copyright,misinformation,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $map = [
            'video' => Video::class,
            'short' => Video::class,
            'channel' => Channel::class,
            'comment' => Comment::class,
            'user' => User::class,
        ];

        $modelClass = $map[$data['type']];
        $target = $modelClass::find($data['id']);

        if (! $target) {
            return ApiResponse::error('Reported content was not found.', [], 404);
        }

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $modelClass,
            'reportable_id' => $target->id,
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
        ]);

        return ApiResponse::success(new ReportResource($report), 'Report submitted successfully.', 201);
    }

    public function mine(Request $request)
    {
        $reports = Report::where('reporter_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::success(ReportResource::collection($reports->getCollection()), 'Your reports', 200, [
            'current_page' => $reports->currentPage(),
            'per_page' => $reports->perPage(),
            'total' => $reports->total(),
            'last_page' => $reports->lastPage(),
            'has_more' => $reports->hasMorePages(),
        ]);
    }
}
