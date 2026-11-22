<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use App\Support\Counters;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $comments = Comment::with(['user.profile', 'video'])
            ->when($request->filled('q'), fn ($q) => $q->where('body', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->boolean('reported'), fn ($q) => $q->whereIn('id', \App\Models\Report::where('reportable_type', Comment::class)->select('reportable_id')))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.comments.index', compact('comments'));
    }

    public function status(Request $request, Comment $comment)
    {
        $data = $request->validate(['status' => ['required', 'in:published,pending,removed']]);
        $comment->update(['status' => $data['status']]);
        ActivityLogger::log('comment.status', 'Set comment #'.$comment->id.' to '.$data['status'], $comment);

        return back()->with('status', 'Comment status updated.');
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();
        if ($comment->video && $comment->video->comments_count > 0) {
            Counters::decrement($comment->video, 'comments_count');
        }
        ActivityLogger::log('comment.delete', 'Deleted comment #'.$comment->id, $comment);

        return back()->with('status', 'Comment deleted.');
    }
}
