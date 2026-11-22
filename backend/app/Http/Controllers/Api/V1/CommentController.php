<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Report;
use App\Models\Video;
use App\Services\NotificationService;
use App\Services\SettingService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Counters;

class CommentController extends Controller
{
    public function index(Request $request, int $videoId)
    {
        $video = Video::find($videoId);
        if (! $video) {
            return ApiResponse::error('Video not found.', [], 404);
        }

        $comments = Comment::with(['user.profile', 'replies.user.profile'])
            ->where('video_id', $video->id)
            ->whereNull('parent_id')
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::success(CommentResource::collection($comments->getCollection()), 'Comments loaded', 200, [
            'current_page' => $comments->currentPage(),
            'per_page' => $comments->perPage(),
            'total' => $comments->total(),
            'last_page' => $comments->lastPage(),
            'has_more' => $comments->hasMorePages(),
        ]);
    }

    public function store(Request $request, int $videoId)
    {
        if (! SettingService::bool('comments_enabled', true)) {
            return ApiResponse::error('Comments are currently disabled.', [], 403);
        }

        $video = Video::published()->find($videoId);
        if (! $video) {
            return ApiResponse::error('Video not found.', [], 404);
        }

        if (! $video->allow_comments) {
            return ApiResponse::error('Comments are disabled for this video.', [], 403);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $comment = DB::transaction(function () use ($request, $video, $data) {
            $comment = Comment::create([
                'video_id' => $video->id,
                'user_id' => $request->user()->id,
                'parent_id' => $data['parent_id'] ?? null,
                'body' => strip_tags($data['body']),
                'status' => 'published',
            ]);

            $video->increment('comments_count');

            return $comment;
        });

        if ($comment->parent_id) {
            $parent = Comment::with('user')->find($comment->parent_id);
            if ($parent && $parent->user_id !== $request->user()->id) {
                NotificationService::send($parent->user_id, 'comment_reply', $request->user()->displayName().' replied to your comment', $comment->body, [
                    'video_id' => $video->id,
                    'comment_id' => $comment->id,
                ]);
            }
        }

        return ApiResponse::success(new CommentResource($comment->load('user.profile')), 'Comment posted successfully.', 201);
    }

    public function update(Request $request, int $id)
    {
        $comment = Comment::find($id);
        if (! $comment) {
            return ApiResponse::error('Comment not found.', [], 404);
        }

        if ($comment->user_id !== $request->user()->id) {
            return ApiResponse::error('You can only edit your own comments.', [], 403);
        }

        $data = $request->validate(['body' => ['required', 'string', 'min:1', 'max:2000']]);
        $comment->update(['body' => strip_tags($data['body'])]);

        return ApiResponse::success(new CommentResource($comment->load('user.profile')), 'Comment updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $comment = Comment::with('video')->find($id);
        if (! $comment) {
            return ApiResponse::error('Comment not found.', [], 404);
        }

        $canDelete = $comment->user_id === $request->user()->id
            || $comment->video?->user_id === $request->user()->id;

        if (! $canDelete) {
            return ApiResponse::error('You are not allowed to delete this comment.', [], 403);
        }

        $comment->delete();
        if ($comment->video) {
            Counters::decrement($comment->video, 'comments_count');
        }

        return ApiResponse::success(null, 'Comment deleted successfully.');
    }

    public function like(Request $request, int $id)
    {
        $comment = Comment::find($id);
        if (! $comment) {
            return ApiResponse::error('Comment not found.', [], 404);
        }

        $existing = \App\Models\CommentReaction::where('comment_id', $comment->id)->where('user_id', $request->user()->id)->first();

        if ($existing) {
            $existing->delete();
            Counters::decrement($comment, 'likes_count');
            $state = false;
        } else {
            \App\Models\CommentReaction::create(['comment_id' => $comment->id, 'user_id' => $request->user()->id, 'type' => 'like']);
            Counters::increment($comment, 'likes_count');
            $state = true;
        }

        return ApiResponse::success(['liked' => $state, 'likes_count' => $comment->fresh()->likes_count], 'Comment reaction saved.');
    }

    public function report(Request $request, int $id)
    {
        $comment = Comment::find($id);
        if (! $comment) {
            return ApiResponse::error('Comment not found.', [], 404);
        }

        $data = $request->validate([
            'reason' => ['required', 'in:spam,harassment,violence,sexual_content,copyright,misinformation,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
        ]);

        return ApiResponse::success(null, 'Comment reported.', 201);
    }
}
