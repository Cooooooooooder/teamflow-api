<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
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

    public function test_workspace_member_can_view_tasks(): void
    {
        $owner = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        Task::create([
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'title' => 'Prepare project plan',
            'description' => 'Prepare the initial project plan.',
            'status' => 'todo',
            'priority' => 'high',
        ]);

        $response = $this->actingAs($owner)->getJson(
            "/api/workspaces/{$workspace->id}/tasks"
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'Prepare project plan',
            ]);
    }

    public function test_non_member_cannot_view_workspace_tasks(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        $response = $this->actingAs($outsider)->getJson(
            "/api/workspaces/{$workspace->id}/tasks"
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'You are not a member of this workspace.',
            ]);
    }

    public function test_guest_cannot_view_workspace_tasks(): void
    {
        $owner = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        $response = $this->getJson(
            "/api/workspaces/{$workspace->id}/tasks"
        );

        $response->assertUnauthorized();
    }

    public function test_workspace_tasks_are_isolated(): void
    {
        $firstOwner = User::factory()->create();
        $secondOwner = User::factory()->create();

        $firstWorkspace = $this->createWorkspaceFor($firstOwner);
        $secondWorkspace = $this->createWorkspaceFor($secondOwner);

        Task::create([
            'workspace_id' => $firstWorkspace->id,
            'created_by' => $firstOwner->id,
            'title' => 'Current workspace task',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        Task::create([
            'workspace_id' => $secondWorkspace->id,
            'created_by' => $secondOwner->id,
            'title' => 'Other workspace task',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($firstOwner)->getJson(
            "/api/workspaces/{$firstWorkspace->id}/tasks"
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'Current workspace task',
            ])
            ->assertJsonMissing([
                'title' => 'Other workspace task',
            ]);
    }


    public function test_workspace_member_can_create_task(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->createWorkspaceFor($owner);

        $response = $this->actingAs($owner)->postJson(
            "/api/workspaces/{$workspace->id}/tasks",
            [
                'title' => 'Build task management',
                'description' => 'Implement task creation.',
                'status' => 'todo',
                'priority' => 'high',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Task created successfully.')
            ->assertJsonPath('data.title', 'Build task management')
            ->assertJsonPath('data.created_by', $owner->id)
            ->assertJsonPath('data.workspace_id', $workspace->id);

        $this->assertDatabaseHas('tasks', [
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'title' => 'Build task management',
            'status' => 'todo',
            'priority' => 'high',
        ]);
    }

    public function test_non_member_cannot_create_task(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        $response = $this->actingAs($outsider)->postJson(
            "/api/workspaces/{$workspace->id}/tasks",
            [
                'title' => 'Unauthorized task',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('tasks', [
            'workspace_id' => $workspace->id,
            'title' => 'Unauthorized task',
        ]);
    }

    public function test_guest_cannot_create_task(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->createWorkspaceFor($owner);

        $response = $this->postJson(
            "/api/workspaces/{$workspace->id}/tasks",
            [
                'title' => 'Guest task',
            ]
        );

        $response->assertUnauthorized();

        $this->assertDatabaseMissing('tasks', [
            'workspace_id' => $workspace->id,
            'title' => 'Guest task',
        ]);
    }

    public function test_task_cannot_be_assigned_to_non_member(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        $response = $this->actingAs($owner)->postJson(
            "/api/workspaces/{$workspace->id}/tasks",
            [
                'title' => 'Task with invalid assignee',
                'assigned_to' => $outsider->id,
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['assigned_to']);

        $this->assertDatabaseMissing('tasks', [
            'workspace_id' => $workspace->id,
            'title' => 'Task with invalid assignee',
        ]);
    }


    public function test_workspace_member_can_update_task(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->createWorkspaceFor($owner);

        $task = Task::create([
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'title' => 'Old task title',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($owner)->patchJson(
            "/api/workspaces/{$workspace->id}/tasks/{$task->id}",
            [
                'title' => 'Updated task title',
                'status' => 'in_progress',
                'priority' => 'high',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Task updated successfully.')
            ->assertJsonPath('data.title', 'Updated task title')
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated task title',
            'status' => 'in_progress',
            'priority' => 'high',
        ]);
    }

    public function test_non_member_cannot_update_task(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        $task = Task::create([
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'title' => 'Original task',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($outsider)->patchJson(
            "/api/workspaces/{$workspace->id}/tasks/{$task->id}",
            [
                'title' => 'Unauthorized change',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Original task',
        ]);
    }

    public function test_task_cannot_be_updated_through_another_workspace(): void
    {
        $firstOwner = User::factory()->create();
        $secondOwner = User::factory()->create();

        $firstWorkspace = $this->createWorkspaceFor($firstOwner);
        $secondWorkspace = $this->createWorkspaceFor($secondOwner);

        $task = Task::create([
            'workspace_id' => $secondWorkspace->id,
            'created_by' => $secondOwner->id,
            'title' => 'Second workspace task',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($firstOwner)->patchJson(
            "/api/workspaces/{$firstWorkspace->id}/tasks/{$task->id}",
            [
                'title' => 'Changed from another workspace',
            ]
        );

        $response->assertNotFound();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'workspace_id' => $secondWorkspace->id,
            'title' => 'Second workspace task',
        ]);
    }


    public function test_workspace_member_can_delete_task(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->createWorkspaceFor($owner);

        $task = Task::create([
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'title' => 'Task to delete',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($owner)->deleteJson(
            "/api/workspaces/{$workspace->id}/tasks/{$task->id}"
        );

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Task deleted successfully.',
            ]);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_non_member_cannot_delete_task(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        $task = Task::create([
            'workspace_id' => $workspace->id,
            'created_by' => $owner->id,
            'title' => 'Protected task',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($outsider)->deleteJson(
            "/api/workspaces/{$workspace->id}/tasks/{$task->id}"
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Protected task',
        ]);
    }

    public function test_task_cannot_be_deleted_through_another_workspace(): void
    {
        $firstOwner = User::factory()->create();
        $secondOwner = User::factory()->create();

        $firstWorkspace = $this->createWorkspaceFor($firstOwner);
        $secondWorkspace = $this->createWorkspaceFor($secondOwner);

        $task = Task::create([
            'workspace_id' => $secondWorkspace->id,
            'created_by' => $secondOwner->id,
            'title' => 'Task in second workspace',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($firstOwner)->deleteJson(
            "/api/workspaces/{$firstWorkspace->id}/tasks/{$task->id}"
        );

        $response->assertNotFound();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'workspace_id' => $secondWorkspace->id,
            'title' => 'Task in second workspace',
        ]);
    }
}
