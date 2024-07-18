<?php

namespace App\Policies;

use App\Models\Code;
use App\Models\Project;
use App\Models\User;

class CodesPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Code $code): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Project $project): bool
    {
        return $this->isUserInProjectOrTeam($user, null, $project) || in_array($user->email, $this->allowedEmails);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Code $code, Project $project): bool
    {
        return $this->isUserInProjectOrTeam($user, null, $project) || in_array($user->email, $this->allowedEmails);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Code $code): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Code $code): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Code $code): bool
    {
        //
    }
}
