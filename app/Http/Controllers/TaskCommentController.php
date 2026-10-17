<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function index(Request $request, Task $task): JsonResponse
    {
        $workspace = $task->workspace;

        $isMember = $workspace->members()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'message' => 'You are not a member of this workspace.',
            ], 403);
        }

        $comments = $task->comments()
            ->with('user:id,name,email')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Comments retrieved successfully.',
            'data' => $comments,
        ]);
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $workspace = $task->workspace;

        $isMember = $workspace->members()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'message' => 'You are not a member of this workspace.',
            ], 403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        $comment->load('user:id,name,email');

        return response()->json([
            'message' => 'Comment created successfully.',
            'data' => $comment,
        ], 201);
    }


    public function destroy(
        Request $request,
        Task $task,
        TaskComment $comment
    ): JsonResponse {
        // Ensure the comment belongs to this task.
        if ($comment->task_id !== $task->id) {
            return response()->json([
                'message' => 'Comment not found for this task.',
            ], 404);
        }

        $user = $request->user();
        $workspace = $task->workspace;

        // Find the user's membership in this workspace.
        $membership = $workspace->members()
            ->where('users.id', $user->id)
            ->first();

        if (! $membership) {
            return response()->json([
                'message' => 'You are not a member of this workspace.',
            ], 403);
        }

        $isOwner = $comment->user_id === $user->id;

        $role = $membership->pivot->role;

        $isAdmin = in_array($role, ['owner', 'admin'], true);

        if (! $isOwner && ! $isAdmin) {
            return response()->json([
                'message' => 'You are not allowed to delete this comment.',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully.',
        ]);
    }
}
