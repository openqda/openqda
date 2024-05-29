<?php

namespace App\Actions\Jetstream;

use App\Models\Codebook;
use App\Models\Project;
use App\Models\Selection;
use App\Models\Source;
use App\Models\Team;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\Contracts\DeletesTeams;
use Laravel\Jetstream\Contracts\DeletesUsers;
use Illuminate\Http\Request;

class DeleteUser implements DeletesUsers
{
    /**
     * The team deleter implementation.
     *
     * @var \Laravel\Jetstream\Contracts\DeletesTeams
     */
    protected $deletesTeams;

    /**
     * Create a new action instance.
     */
    public function __construct(DeletesTeams $deletesTeams)
    {
        $this->deletesTeams = $deletesTeams;
    }

    /**
     * Delete the given user.
     *
     * @throws Exception
     */
    public function delete(User $user, Request $request): void
    {
        // Check if the provided password is correct
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('The password is incorrect.'),
            ]);
        }


        // Check if the user owns any non-personal teams that have users
        if ($user->ownedTeams()->where('id', '!=', $user->personalTeam()->id)->has('users')->exists()) {
            throw new Exception('User owns teams with users');
        }

        DB::transaction(function () use ($user) {
            $this->handleUserData($user);

            $this->deleteTeams($user);
            $user->deleteProfilePhoto();

            $user->tokens->each->delete();

            $user->delete();
        });

    }

    /**
     * Handle the user's data.
     */
    protected function handleUserData(User $user): void
    {
        $teamIds = $user->teams->pluck('id');

        // Use whereHas to filter projects where the user is part of the team
        // or where the user is the creator
        // or where the user edited the project
        $projects = Project::whereHas('team', function (Builder $query) use ($teamIds) {
            $query->whereIn('id', $teamIds);
        })
            ->orWhere('creating_user_id', $user->id)
            ->orWhere('modifying_user_id', $user->id)
            ->withTrashed()
            ->get();

        foreach ($projects as $project) {
            /**
             * If the project has a team, check if the user is the owner of the team.
             * Check also if the team is trashed, if it is, just delete the project.
             * We check for team_id and team because sometimes the id is there, but the team is trashed.
             */
            if ($project->team_id && $project->team) {

                $team = $project->team;
                $userIsNotOwner = $team->user_id !== $user->id;

                if ($userIsNotOwner) {
                    $newOwnerId = $team->user_id;
                    if ($project->modifying_user_id === $user->id) {
                        $project->modifying_user_id = $newOwnerId;
                        $project->save();
                    }

                    /**
                     * Reassign only the codebooks, sources and selections created by the user
                     * Also reassign the modifying user of the sources and selections
                     */
                    $codebooks = $project->codebooks()->where('creating_user_id', $user->id)->get();
                    $codebooks->each(function ($codebook) use ($newOwnerId) {
                        $codebook->creating_user_id = $newOwnerId;
                        $codebook->save();
                    });

                    $createdSources = $project->sources()->where('creating_user_id', $user->id)->get();
                    $createdSources->each(function ($source) use ($newOwnerId) {
                        $source->creating_user_id = $newOwnerId;
                        $source->save();
                    });
                    $modifiedSources = $project->sources()->where('modifying_user_id', $user->id)->get();
                    $modifiedSources->each(function ($source) use ($newOwnerId) {
                        $source->modifying_user_id = $newOwnerId;
                        $source->save();
                    });

                    $createdSelections = $project->selections()->where('creating_user_id', $user->id)->get();
                    $createdSelections->each(function ($selection) use ($newOwnerId) {
                        $selection->creating_user_id = $newOwnerId;
                        $selection->save();
                    });
                    $modifiedSelections = $project->selections()->where('modifying_user_id', $user->id)->get();
                    $modifiedSelections->each(function ($selection) use ($newOwnerId) {
                        $selection->modifying_user_id = $newOwnerId;
                        $selection->save();
                    });

                } else {
                    // User is owner of the team
                    // if the team is empty, just delete the team and the project
                    $this->deletesTeams->delete($team);
                    $this->deleteProject($project);
                }
            } else {
                $this->deleteProject($project);
            }

        }
        /**
         * I placed this here because before it would trigger a foreign key constraint error
         */
        $this->deleteCodebooks($user->id);
    }

    protected function deleteCodebooks($userId): void
    {
        Codebook::where('creating_user_id', $userId)->delete();
    }

    /**
     * Delete the teams and team associations attached to the user.
     */
    protected function deleteTeams(User $user): void
    {
        $user->teams()->detach();

        $user->ownedTeams->each(function (Team $team) {
            $this->deletesTeams->delete($team);
        });
    }

    private function deleteProject(mixed $project): void
    {
        $projectId = $project->id;

        // Get the project's codebook
        $codebooks = Codebook::where('project_id', $projectId)->get();

        // Deleted codebooks and codes
        if ($codebooks->count() > 0) {
            foreach ($codebooks as $codebook) {
                $codes = $codebook->codes()->get();

                foreach ($codes as $code) {
                    // Delete all associated selections
                    Selection::where('project_id', $projectId)->delete();

                    // Delete the code itself
                    $code->delete();
                }
                $codebook->delete();

            }

        }
        $sources = Source::where('project_id', $projectId)->get();

        foreach ($sources as $source) {
            // Assuming you've moved the delete logic to a service
            app('App\Services\SourceService')->destroySource($source->id);
        }
        $project->conditionallyPreventEvent = true;
        $project->forceDelete();
    }
}
