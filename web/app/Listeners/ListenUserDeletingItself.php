<?php

namespace App\Listeners;

use App\Models\Codebook;
use App\Models\Selection;
use App\Models\Source;
use Illuminate\Support\Facades\DB;

class ListenUserDeletingItself
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        // take all user project
        // check whether it has a team or not
        // if it has a team, check if the user is the owner of the team
        // User should not be the owner at this point, since we "crash" the app before
        // if the user is not the owner, reassign everything to the owner

        DB::transaction(function () use ($event) {

            $projects = $event->user->allRelatedProjects(true);

            foreach ($projects as $project) {
                if ($project->team_id) {
                    $team = $project->team;
                    $userIsNotOwner = $team->user_id !== $event->user->id;

                    if ($userIsNotOwner) {
                        $newOwnerId = $team->user_id;
                        $project->codebooks()->update(['creating_user_id' => $newOwnerId]);
                        $project->sources()->update(['creating_user_id' => $newOwnerId]);
                        $project->selections()->update(['creating_user_id' => $newOwnerId]);
                    } else {
                        // NOT HAPPENING HERE
                        // User is the owner of the team
                    }
                } else {
                    $projectId = $project->id;

                    // Get the project's codebook
                    $codebooks = Codebook::where('project_id', $projectId)->get();

                    // Delete all selections associated with the project
                    Selection::where('project_id', $projectId)->delete();
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
                        }

                    }

                    $sources = Source::where('project_id', $projectId)->get();

                    foreach ($sources as $source) {
                        // Assuming you've moved the delete logic to a service
                        app('App\Services\SourceService')->destroySource($source->id);
                    }
                    $project->forceDelete();

                }
            }
        });
    }
}
