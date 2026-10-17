<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;

class WorkspaceMemberController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $isMember = $workspace->members()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'message' => 'You are not a member of this workspace.',
            ], 403);
        }

        $members = $workspace->members()
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('users.name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
            ]);

        return response()->json([
            'data' => $members,
        ]);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $isOwner = $workspace->members()
            ->wherePivot('role', 'owner')
            ->where('users.id', $request->user()->id)
            ->exists();

        if (! $isOwner) {
            return response()->json([
                'message' => 'Only the workspace owner can add members.',
            ], 403);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        $alreadyMember = $workspace->members()
            ->where('users.id', $user->id)
            ->exists();

        if ($alreadyMember) {
            return response()->json([
                'message' => 'This user is already a workspace member.',
            ], 422);
        }

        $workspace->members()->attach($user->id, [
            'role' => 'member',
        ]);

        return response()->json([
            'message' => 'Member added successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'member',
            ],
        ], 201);
    }

    public function destroy(
        Request $request,
        Workspace $workspace,
        User $user
    ) {
        $isOwner = $workspace->members()
            ->wherePivot('role', 'owner')
            ->where('users.id', $request->user()->id)
            ->exists();

        if (! $isOwner) {
            return response()->json([
                'message' => 'Only the workspace owner can remove members.',
            ], 403);
        }

        if ((int) $workspace->created_by === (int) $user->id) {
            return response()->json([
                'message' => 'The workspace owner cannot be removed.',
            ], 422);
        }

        $isMember = $workspace->members()
            ->where('users.id', $user->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'message' => 'This user is not a member of the workspace.',
            ], 404);
        }

        $workspace->members()->detach($user->id);
                             
        return response()->json([
            'message' => 'Member removed successfully.',
        ]);
    }
}
