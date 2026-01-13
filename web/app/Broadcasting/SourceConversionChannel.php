<?php

namespace App\Broadcasting;

use App\Models\Project;
use App\Models\User;

class SourceConversionChannel
{
    /**
     * Create a new channel instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, Project $project): array|bool
    {
        Log:info('[conversion.{project}] User '.$user->id.' is attempting to join conversion broadcast for project '.$project->id);
        $userTeamIds = $user->teams->pluck('id');

        return $user->id === $project->creating_user_id || $userTeamIds->contains($project->team_id);
    }
}
