<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceMemberTest extends TestCase
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

    public function test_workspace_member_can_view_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        $workspace->members()->attach($member->id, [
            'role' => 'member',
        ]);

        $response = $this->actingAs($owner)->getJson(
            "/api/workspaces/{$workspace->id}/members"
        );

        $response->assertOk()
            ->assertJsonFragment([
                'email' => $owner->email,
                'role' => 'owner',
            ])
            ->assertJsonFragment([
                'email' => $member->email,
                'role' => 'member',
            ]);
    }

    public function test_owner_can_add_a_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        $response = $this->actingAs($owner)->postJson(
            "/api/workspaces/{$workspace->id}/members",
            ['email' => $member->email]
        );

        $response->assertCreated()
            ->assertJsonPath('data.email', $member->email)
            ->assertJsonPath('data.role', 'member');

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);
    }

    public function test_owner_cannot_add_the_same_member_twice(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        $workspace->members()->attach($member->id, [
            'role' => 'member',
        ]);

        $response = $this->actingAs($owner)->postJson(
            "/api/workspaces/{$workspace->id}/members",
            ['email' => $member->email]
        );

        $response->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'This user is already a workspace member.'
            );
    }

    public function test_regular_member_cannot_add_other_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $newMember = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        $workspace->members()->attach($member->id, [
            'role' => 'member',
        ]);

        $response = $this->actingAs($member)->postJson(
            "/api/workspaces/{$workspace->id}/members",
            ['email' => $newMember->email]
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $newMember->id,
        ]);
    }

    public function test_non_member_cannot_view_workspace_members(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        $response = $this->actingAs($outsider)->getJson(
            "/api/workspaces/{$workspace->id}/members"
        );

        $response->assertForbidden();
    }

    public function test_owner_can_remove_a_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $workspace = $this->createWorkspaceFor($owner);

        $workspace->members()->attach($member->id, [
            'role' => 'member',
        ]);

        $response = $this->actingAs($owner)->deleteJson(
            "/api/workspaces/{$workspace->id}/members/{$member->id}"
        );

        $response->assertOk()
            ->assertJsonPath(
                'message',
                'Member removed successfully.'
            );

        $this->assertDatabaseMissing('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_owner_cannot_remove_themselves(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->createWorkspaceFor($owner);

        $response = $this->actingAs($owner)->deleteJson(
            "/api/workspaces/{$workspace->id}/members/{$owner->id}"
        );

        $response->assertUnprocessable();

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
    }

    public function test_guest_cannot_view_workspace_members(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->createWorkspaceFor($owner);

        $response = $this->getJson(
            "/api/workspaces/{$workspace->id}/members"
        );

        $response->assertUnauthorized();
    }
}
