<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Notifications\TeamFlowNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskNotificationTest extends TestCase
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

    public function test_assignee_receives_notification_when_task_is_created(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $assignee = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        $workspace->members()->attach($assignee->id, [
            'role' => 'member',
        ]);

        $response = $this->actingAs($owner)->postJson(
            "/api/workspaces/{$workspace->id}/tasks",
            [
                'title' => 'Prepare project plan',
                'assigned_to' => $assignee->id,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Prepare project plan');

        $taskId = $response->json('data.id');

        Notification::assertSentTo(
            $assignee,
            TeamFlowNotification::class,
            function (
                TeamFlowNotification $notification,
                array $channels
            ) use ($taskId, $workspace): bool {
                return in_array('database', $channels)
                    && $notification->type === 'task_assigned'
                    && $notification->data['task_id'] === $taskId
                    && $notification->data['workspace_id'] === $workspace->id;
            }
        );

        Notification::assertNotSentTo(
            $owner,
            TeamFlowNotification::class
        );
    }

    public function test_task_without_assignee_does_not_send_notification(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $workspace = $this->createWorkspaceFor($owner);

        $response = $this->actingAs($owner)->postJson(
            "/api/workspaces/{$workspace->id}/tasks",
            [
                'title' => 'Unassigned task',
            ]
        );

        $response->assertCreated();

        Notification::assertNothingSent();
    }

    public function test_user_does_not_receive_notification_when_assigning_task_to_themselves(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $workspace = $this->createWorkspaceFor($owner);

        $response = $this->actingAs($owner)->postJson(
            "/api/workspaces/{$workspace->id}/tasks",
            [
                'title' => 'My own task',
                'assigned_to' => $owner->id,
            ]
        );

        $response->assertCreated();

        Notification::assertNothingSent();
    }
}
