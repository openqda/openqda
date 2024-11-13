<?php

namespace App\Policies;

use App\Enums\ModelType;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;

class SettingPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Usually true as the view will filter what they can actually see
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Setting $setting): bool
    {
        return match ($setting->model_type) {
            ModelType::User => $user->id === $setting->model_id,
            ModelType::Project => $this->isUserInProjectOrTeam(
                $user,
                null,
                Project::find($setting->model_id)
            ),
            default => false,
        };
    }

    public function create(User $user, ?Project $project = null): bool
    {
        if ($project) {
            return $this->isUserInProjectOrTeam($user, null, $project);
        }

        return true;
    }

    public function update(User $user, Setting $setting): bool
    {
        return match ($setting->model_type) {
            ModelType::User => $user->id === (int) $setting->model_id,
            ModelType::Project => $this->isUserInProjectOrTeam(
                $user,
                null,
                Project::find($setting->model_id)
            ),
            default => false
        };
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Setting $setting): bool
    {
        return $this->update($user, $setting);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Setting $setting): bool
    {
        return $this->update($user, $setting);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Setting $setting): bool
    {
        return $this->update($user, $setting);
    }
}
