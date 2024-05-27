<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Source;
use App\Models\User;

class BasePolicy
{
    // Array of allowed "admin" emails
    protected array $allowedEmails;

    public function __construct()
    {
        $this->allowedEmails = config('app.admins');
    }

    /**
     * Helper function to check if the user is part of the project's team.
     */
    public function isUserInProjectOrTeam(User $user, ?Source $source = null, ?Project $project = null): bool
    {
        if ($project === null && $source !== null) {
            $project = $source->project;
        }
        $teamId = $project->team_id;

        // Check if user is the project creator
        if ($user->id === $project->creating_user_id) {
            return true;
        }

        // Check if user is in the team
        if ($teamId && $user->teams->contains($teamId)) {
            return true;
        }

        return false;
    }
}
