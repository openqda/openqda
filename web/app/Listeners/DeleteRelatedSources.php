<?php

namespace App\Listeners;

use App\Events\ProjectDeleting;
use App\Models\Codebook;
use App\Models\Selection;
use App\Models\Source;
use Illuminate\Support\Facades\DB;

class DeleteRelatedSources
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
    public function handle(ProjectDeleting $event)
    {

        DB::transaction(function () use ($event) {

            $projectId = $event->project->id;

            // Get the project's codebook
            $codebooks = Codebook::where('project_id', $projectId)->get();

            // Deleted codebooks and codes
            if ($codebooks->count() > 0) {
                foreach ($codebooks as $codebook) {
                    $codes = $codebook->codes()->get();

                    foreach ($codes as $code) {
                        // Delete all associated selections
                        Selection::where('project_id', $projectId)->delete();
                        Selection::where('code_id', $code->id)->delete();

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

            if ($event->project->team) {
                $event->project->team->delete();
            }
        });
    }
}
