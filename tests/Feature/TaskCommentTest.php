<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCommentTest extends TestCase
{
    use RefreshDatabase;

    private function createWorkspaceFor(User $owner): Workspace
    {
        $workspace = Workspace::create([
            'name' => 'Test Workspace',
            'slug' => 'test-workspace-' . uniqid(),
            'created_by' => $owner->id,
        ]);

        $workspace->members()->attach($owner->id, [
            'role' => 'owner',
        ]);

        return $workspace;
    }

    private function createTask(Workspace $workspace, User $owner): Task
    {
        return Task::create([
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'title' => 'Test Task',
            'status' => 'todo',
            'priority' => 'medium',
        ]);
    }

    public function test_workspace_member_can_view_task_comments(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->createWorkspaceFor($owner);
        $task = $this->createTask($workspace, $owner);

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
            'body' => 'This is my first comment.',
        ]);

        $response = $this->actingAs($owner)->getJson(
            "/api/tasks/{$task->id}/comments"
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'body' => 'This is my first comment.',
            ]);
    }


    public function test_workspace_member_can_create_task_comment(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->createWorkspaceFor($owner);
        $task = $this->createTask($workspace, $owner);

        $response = $this->actingAs($owner)->postJson(
            "/api/tasks/{$task->id}/comments",
            [
                'body' => 'The task is progressing well.',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'body' => 'The task is progressing well.',
                'user_id' => $owner->id,
            ]);

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'user_id' => $owner->id,
            'body' => 'The task is progressing well.',
        ]);
    }


    public function test_non_member_cannot_view_task_comments(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);
        $task = $this->createTask($workspace, $owner);

        $response = $this->actingAs($outsider)->getJson(
            "/api/tasks/{$task->id}/comments"
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'You are not a member of this workspace.',
            ]);
    }

    public function test_non_member_cannot_create_task_comment(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);
        $task = $this->createTask($workspace, $owner);

        $response = $this->actingAs($outsider)->postJson(
            "/api/tasks/{$task->id}/comments",
            [
                'body' => 'Unauthorized comment.',
            ]
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'You are not a member of this workspace.',
            ]);

        $this->assertDatabaseMissing('task_comments', [
            'task_id' => $task->id,
            'user_id' => $outsider->id,
            'body' => 'Unauthorized comment.',
        ]);
    }


    public function test_guest_cannot_view_task_comments(): void
    {
        $owner = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);
        $task = $this->createTask($workspace, $owner);

        $response = $this->getJson(
            "/api/tasks/{$task->id}/comments"
        );

        $response->assertUnauthorized();
    }

    public function test_guest_cannot_create_task_comment(): void
    {
        $owner = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);
        $task = $this->createTask($workspace, $owner);

        $response = $this->postJson(
            "/api/tasks/{$task->id}/comments",
            [
                'body' => 'Guest comment.',
            ]
        );

        $response->assertUnauthorized();

        $this->assertDatabaseMissing('task_comments', [
            'task_id' => $task->id,
            'body' => 'Guest comment.',
        ]);
    }
}
