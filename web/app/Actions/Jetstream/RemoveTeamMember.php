<?php

namespace App\Actions\Jetstream;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\RemovesTeamMembers;
use Laravel\Jetstream\Events\TeamMemberRemoved;
use OwenIt\Auditing\Models\Audit;

class RemoveTeamMember implements RemovesTeamMembers
{
    /**
     * Remove the team member from the given team.
     */
    public function remove(User $user, Team $team, User $teamMember): void
    {
        $this->authorize($user, $team, $teamMember);

        $this->ensureUserDoesNotOwnTeam($teamMember, $team);
        $this->handleUserData($teamMember, $team);
        $team->removeUser($teamMember);

        $audit = new Audit([
            'user_type' => 'App\Models\User',
            'user_id' => auth()->id(),
            'event' => 'team updated',
            'auditable_id' => $team->id,
            'auditable_type' => get_class($team),
            'new_values' => ['message' => $teamMember->name.' was removed from '.$team->name],
        ]);

        $audit->save();

        TeamMemberRemoved::dispatch($team, $teamMember);
    }

    /**
     * Authorize that the user can remove the team member.
     */
    protected function authorize(User $user, Team $team, User $teamMember): void
    {
        if (! Gate::forUser($user)->check('removeTeamMember', $team) &&
            $user->id !== $teamMember->id) {
            throw new AuthorizationException;
        }
    }

    /**
     * Ensure that the currently authenticated user does not own the team.
     */
    protected function ensureUserDoesNotOwnTeam(User $teamMember, Team $team): void
    {
        if ($teamMember->id === $team->owner->id) {
            throw ValidationException::withMessages([
                'team' => [__('You may not leave a team that you created.')],
            ])->errorBag('removeTeamMember');
        }
    }

    /**
     * Handle the user's data.
     */
    protected function handleUserData(User $user, Team $team): void
    {

        // Use whereHas to filter projects where the user is part of the team
        // or where the user is the creator
        $projects = Project::whereHas('team', function (Builder $query) use ($team) {
            $query->where('id', $team->id);
        })->get();

        foreach ($projects as $project) {
            if ($project->team_id) {
                ray('Project is going to be reassigned to the team owner. Project: '.$project->id);
                $team = $project->team;
                $userIsNotOwner = $team->user_id !== $user->id;

                if ($userIsNotOwner) {
                    $newOwnerId = $team->user_id;
                    $project->codebooks()->update(['creating_user_id' => $newOwnerId]);
                    $project->sources()->update(['creating_user_id' => $newOwnerId]);
                    $project->selections()->update(['creating_user_id' => $newOwnerId]);
                }
            }
        }
    }
}
