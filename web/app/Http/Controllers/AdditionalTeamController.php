<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\Http\Controllers\Inertia\TeamController;
use Laravel\Jetstream\Jetstream;

class AdditionalTeamController extends TeamController
{
    /**
     * Make a user an owner of a team and a project
     */
    public function makeOwner(Request $request)
    {

        // Accessing data sent in the request body
        $teamId = $request->input('teamId');
        $userId = $request->input('userId');
        $newOwner = User::findOrFail($userId);
        $projectId = $request->input('projectId');
        $team = Team::findOrFail($teamId);
        Gate::forUser(auth()->user())->authorize('changeOwner', $team);

        // change team owner
        $team->users()->detach($newOwner);

        if (! is_null(Jetstream::findRole('admin'))) {
            $team->users()->attach(
                auth()->user(), ['role' => 'admin']
            );
        }
        $team->owner()->associate($newOwner);
        $team->save();

        // change project owner and modifying user
        $project = Project::findOrFail($projectId);
        $project->creating_user_id = $userId;
        $project->modifying_user_id = $userId;
        $project->save();

        // make sure the new owner is a member of the team
        if (! $team->hasUser($newOwner)) {
            $team->users()->attach($newOwner);
        }

        return to_route('project.show', ['project' => $projectId])->with('message', 'New owner successfully set.');

    }
}
