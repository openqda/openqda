<?php

namespace App\Actions\Jetstream;

use App\Models\Project;
use App\Models\Team;
use Laravel\Jetstream\Contracts\DeletesTeams;

class DeleteTeam implements DeletesTeams
{
    /**
     * Delete the given team.
     */
    public function delete(Team $team): void
    {
        // Find the project associated with this team and dissociate
        $project = Project::where('team_id', $team->id)->first();

        if ($project) {
            $project->team()->dissociate()->save();
        }

        ray($team->id);
        ray($team);

        // Then delete the team
        $team->purge();
    }
}
