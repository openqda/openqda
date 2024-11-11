<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeTeamOwnerRequest;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Laravel\Jetstream\Http\Controllers\Inertia\TeamController;
use Laravel\Jetstream\Jetstream;
use Throwable;

/**
 * Class AdditionalTeamController
 *
 * Handles additional team-related operations extending Jetstream's default TeamController.
 * Provides functionality for team ownership management and related project updates.
 */
class AdditionalTeamController extends TeamController
{
    /**
     * Transfer team ownership to another user and update related project ownership.
     *
     * This method performs the following operations:
     * 1. Validates the new owner exists and has necessary permissions
     * 2. Updates team ownership
     * 3. Updates related project ownership
     * 4. Ensures proper team member roles are maintained
     *
     * @param  ChangeTeamOwnerRequest  $request  Validated request containing user_id, project_id, and team_id
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If user is not authorized to change team ownership
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If models are not found
     */
    public function makeOwner(ChangeTeamOwnerRequest $request): RedirectResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                // Fetch required models
                $newOwner = User::findOrFail($request->input('userId'));
                $team = Team::findOrFail($request->input('teamId'));
                $project = Project::findOrFail($request->input('projectId'));

                // Additional validations
                $this->validateOwnershipTransfer($team, $newOwner);

                // Perform ownership transfer
                $this->transferTeamOwnership($team, $newOwner);
                $this->updateProjectOwnership($project, $newOwner->id);

                // Log the successful ownership transfer
                Log::info('Team ownership transferred', [
                    'team_id' => $team->id,
                    'old_owner_id' => auth()->id(),
                    'new_owner_id' => $newOwner->id,
                    'project_id' => $project->id,
                ]);

                return to_route('project.show', ['project' => $project->id])
                    ->with('message', 'Team ownership successfully transferred.');
            });
        } catch (Throwable $e) {
            Log::error('Team ownership transfer failed', [
                'error' => $e->getMessage(),
                'team_id' => $request->input('teamId'),
                'new_owner_id' => $request->input('userId'),
            ]);

            return to_route('project.show', ['project' => $request->input('projectId')])
                ->with('error', 'Failed to transfer team ownership. Please try again.');
        }
    }

    /**
     * Validates the ownership transfer request beyond basic authorization.
     *
     * @param  Team  $team  The team being transferred
     * @param  User  $newOwner  The prospective new owner
     *
     * @throws \InvalidArgumentException If validation fails
     */
    private function validateOwnershipTransfer(Team $team, User $newOwner): void
    {
        // Verify current user has permission
        Gate::authorize('changeOwner', $team);

        // Ensure new owner is not already the owner
        if ($team->owner->id === $newOwner->id) {
            throw new \InvalidArgumentException('User is already the team owner.');
        }

        // Ensure new owner is a team member
        if (! $team->hasUser($newOwner)) {
            throw new \InvalidArgumentException('New owner must be a team member.');
        }

        // Verify new owner is active and verified
        if (! $newOwner->hasVerifiedEmail()) {
            throw new \InvalidArgumentException('New owner must have a verified email address.');
        }
    }

    /**
     * Transfers team ownership to the new owner.
     *
     * @param  Team  $team  The team being transferred
     * @param  User  $newOwner  The new owner
     */
    private function transferTeamOwnership(Team $team, User $newOwner): void
    {
        // Remove new owner from team members (they'll be owner now)
        $team->users()->detach($newOwner);

        // Make current owner an admin if admin role exists
        if (! is_null(Jetstream::findRole('admin'))) {
            $team->users()->attach(
                auth()->user(),
                ['role' => 'admin']
            );
        }

        // Set new owner
        $team->owner()->associate($newOwner);
        $team->save();
    }

    /**
     * Updates project ownership to match team ownership.
     *
     * @param  Project  $project  The project to update
     * @param  int|string  $newOwnerId  The ID of the new owner
     */
    private function updateProjectOwnership(Project $project, $newOwnerId): void
    {
        $project->creating_user_id = $newOwnerId;
        $project->modifying_user_id = $newOwnerId;
        $project->save();
    }
}
