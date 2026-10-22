<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTaskRequest;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\UpdateTaskRequest;
use App\Notifications\TeamFlowNotification;

class TaskController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        // Check whether the authenticated user belongs to this workspace.
        $isMember = $workspace->members()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'message' => 'You are not a member of this workspace.',
            ], 403);
        }

        // Retrieve only tasks belonging to this workspace.
        $tasks = $workspace->tasks()
            ->with([
                'creator:id,name,email',
                'assignee:id,name,email',
            ])
            ->latest()
            ->paginate(10);

        return response()->json($tasks);
    }


    public function store(StoreTaskRequest $request, Workspace $workspace)
    {
        // Ensure the authenticated user belongs to this workspace.
        $isMember = $workspace->members()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'message' => 'You are not a member of this workspace.',
            ], 403);
        }

        // Create the task using only validated data.
        $validated = $request->validated();

        $task = $workspace->tasks()->create([
            'created_by' => $request->user()->id,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'todo',
            'priority' => $validated['priority'] ?? 'medium',
            'due_date' => $validated['due_date'] ?? null,
        ]);

        $task->load([
            'creator:id,name,email',
            'assignee:id,name,email',
        ]);


        if (
            $task->assignee &&
            $task->assignee->id !== $request->user()->id
        ) {
            $task->assignee->notify(
                new TeamFlowNotification(
                    type: 'task_assigned',
                    message: 'You have been assigned a new task: ' . $task->title,
                    data: [
                        'task_id' => $task->id,
                        'workspace_id' => $workspace->id,
                        'task_title' => $task->title,
                    ],
                )
            );
        }


        return response()->json([
            'message' => 'Task created successfully.',
            'data' => $task,
        ], 201);
    }


    public function update(
        UpdateTaskRequest $request,
        Workspace $workspace,
        Task $task
    ) {
        // Check whether the authenticated user belongs to the workspace.
        $isMember = $workspace->members()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'message' => 'You are not a member of this workspace.',
            ], 403);
        }

        // Prevent updating a task that belongs to another workspace.
        if ($task->workspace_id !== $workspace->id) {
            return response()->json([
                'message' => 'The task does not belong to this workspace.',
            ], 404);
        }

        // Update only the fields validated by UpdateTaskRequest.
        $task->update($request->validated());

        $task->load([
            'creator:id,name,email',
            'assignee:id,name,email',
        ]);

        return response()->json([
            'message' => 'Task updated successfully.',
            'data' => $task,
        ]);
    }


    public function destroy(Request $request, Workspace $workspace, Task $task)
    {
        $isMember = $workspace->members()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'message' => 'You are not a member of this workspace.',
            ], 403);
        }

        if ($task->workspace_id !== $workspace->id) {
            return response()->json([
                'message' => 'The task does not belong to this workspace.',
            ], 404);
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }
}
