<?php

namespace App\Policies;

use App\Models\Codebook;
use App\Models\Project;
use App\Models\User;

class CodebookPolicy extends BasePolicy
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
    public function view(User $user, Codebook $codebook): bool
    {

    }

    /**
     * Determine whether the user can create models.
     * In case a codebook is being created, the user must be part of the project's team or have an allowed email.
     * In case the codebook is imported, the same above rules apply.
     */
    public function create(User $user, Project $project): bool
    {
        return $this->isUserInProjectOrTeam($user, null, $project) || in_array($user->email, $this->allowedEmails);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Codebook $codebook): bool
    {

        return $this->isUserInProjectOrTeam($user, null, $codebook->project) || in_array($user->email, $this->allowedEmails);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Codebook $codebook): bool
    {
        return $this->isUserInProjectOrTeam($user, null, $codebook->project) || in_array($user->email, $this->allowedEmails);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Codebook $codebook): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Codebook $codebook): bool
    {
        //
    }
}
