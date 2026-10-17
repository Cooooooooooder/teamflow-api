<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $workspaces = $request->user()
            ->workspaces()
            ->orderByDesc('workspaces.created_at')
            ->get();

        return WorkspaceResource::collection($workspaces);
    }

    public function store(StoreWorkspaceRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $workspace = DB::transaction(function () use ($user, $validated) {
            $baseSlug = Str::slug($validated['name']);

            $slug = $baseSlug;

            while (Workspace::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . Str::lower(Str::random(6));
            }

            $workspace = Workspace::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'created_by' => $user->id,
            ]);

            $workspace->members()->attach($user->id, [
                'role' => 'owner',
            ]);

            return $user->workspaces()
                ->whereKey($workspace->id)
                ->firstOrFail();
        });

        return (new WorkspaceResource($workspace))
            ->response()
            ->setStatusCode(201);
    }
}
