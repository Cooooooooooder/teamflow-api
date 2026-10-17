<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_workspace_and_become_its_owner(): void
    {
        $user = User::factory()->create();

        $workspace = Workspace::create([
            'name' => 'Development Team',
            'slug' => 'development-team',
            'created_by' => $user->id,
        ]);

        $workspace->members()->attach($user->id, [
            'role' => 'owner',
        ]);

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $this->assertTrue(
            $user->workspaces()
                ->whereKey($workspace->id)
                ->exists()
        );
    }

    public function test_same_user_cannot_be_added_twice_to_workspace(): void
    {
        $user = User::factory()->create();

        $workspace = Workspace::create([
            'name' => 'Development Team',
            'slug' => 'development-team',
            'created_by' => $user->id,
        ]);

        $workspace->members()->attach($user->id, [
            'role' => 'owner',
        ]);

        $this->expectException(QueryException::class);

        $workspace->members()->attach($user->id, [
            'role' => 'member',
        ]);
    }


    public function test_authenticated_user_can_create_a_workspace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(
            '/api/workspaces',
            ['name' => 'Engineering Team']
        );

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Engineering Team')
            ->assertJsonPath('data.slug', 'engineering-team')
            ->assertJsonPath('data.role', 'owner');

        $this->assertDatabaseHas('workspaces', [
            'name' => 'Engineering Team',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('workspace_members', [
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_guest_cannot_create_a_workspace(): void
    {
        $response = $this->postJson('/api/workspaces', [
            'name' => 'Engineering Team',
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_only_sees_their_own_workspaces(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $ownWorkspace = Workspace::create([
            'name' => 'My Team',
            'slug' => 'my-team',
            'created_by' => $user->id,
        ]);

        $ownWorkspace->members()->attach($user->id, [
            'role' => 'owner',
        ]);

        Workspace::create([
            'name' => 'Other Team',
            'slug' => 'other-team',
            'created_by' => $anotherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/workspaces');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownWorkspace->id);
    }
}
