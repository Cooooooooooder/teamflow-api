<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_notifications(): void
    {
        $user = User::factory()->create();

        $user->notify(
            new \App\Notifications\TeamFlowNotification(
                type: 'task_assigned',
                message: 'You have been assigned a task.',
                data: [
                    'task_id' => 1,
                    'workspace_id' => 1,
                ],
            )
        );

        $response = $this->actingAs($user)->getJson(
            '/api/notifications'
        );

        $response
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath(
                'data.0.data.type',
                'task_assigned'
            )
            ->assertJsonPath(
                'data.0.data.message',
                'You have been assigned a task.'
            );
    }

    public function test_guest_cannot_view_notifications(): void
    {
        $response = $this->getJson('/api/notifications');

        $response->assertUnauthorized();
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();

        $notification = $user->notify(
            new \App\Notifications\TeamFlowNotification(
                type: 'task_assigned',
                message: 'You have been assigned a task.',
                data: ['task_id' => 1],
            )
        );

        $notificationId = $user->notifications()->first()->id;

        $response = $this->actingAs($user)->patchJson(
            "/api/notifications/{$notificationId}/read"
        );

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Notification marked as read.',
            ]);

        $this->assertNotNull(
            $user->notifications()->find($notificationId)->read_at
        );
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();

        $owner->notify(
            new \App\Notifications\TeamFlowNotification(
                type: 'task_assigned',
                message: 'You have been assigned a task.',
                data: ['task_id' => 1],
            )
        );

        $notificationId = $owner->notifications()->first()->id;

        $response = $this->actingAs($anotherUser)->patchJson(
            "/api/notifications/{$notificationId}/read"
        );

        $response
            ->assertNotFound()
            ->assertJson([
                'message' => 'Notification not found.',
            ]);

        $this->assertNull(
            $owner->notifications()->find($notificationId)->read_at
        );
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 3; $i++) {
            $user->notify(
                new \App\Notifications\TeamFlowNotification(
                    type: 'task_assigned',
                    message: "Notification {$i}",
                    data: ['task_id' => $i],
                )
            );
        }

        $response = $this->actingAs($user)->patchJson(
            '/api/notifications/read-all'
        );

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'All notifications marked as read.',
            ]);

        $this->assertSame(
            0,
            $user->unreadNotifications()->count()
        );

        $this->assertSame(
            3,
            $user->notifications()->whereNotNull('read_at')->count()
        );
    }
}
