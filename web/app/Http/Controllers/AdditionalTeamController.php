<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeTeamOwnerRequest;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\Http\Controllers\Inertia\TeamController;
use Laravel\Jetstream\Jetstream;

/**
 * Class AdditionalTeamController
 *
 * @see TeamController
 * This controller is an extension of the Jetstream TeamController and adds additional functionality
 */
class AdditionalTeamController extends TeamController
{
    /**
     * Make a user an owner of a team and a project
     *
     * @return RedirectResponse
     */
    public function makeOwner(ChangeTeamOwnerRequest $request)
    {

        $userId = $request->input('userId');
        $newOwner = User::findOrFail($userId);

        $projectId = $request->input('projectId');
        $teamId = $request->input('teamId');
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
