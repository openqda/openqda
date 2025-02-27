<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Selection;
use App\Models\User;

class SelectionPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Selection $selection): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Selection $selection): bool
    {
        return $this->isUserInProjectOrTeam($user, null, Project::findOrFail($selection->project_id)) || in_array($user->email, $this->allowedEmails);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Selection $selection): bool
    {
        return $this->isUserInProjectOrTeam($user, null, Project::findOrFail($selection->project_id)) || in_array($user->email, $this->allowedEmails);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Selection $selection): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Selection $selection): bool
    {
        return false;
    }
}
