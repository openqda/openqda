<?php

namespace App\Listeners;

use App\Events\SourceDeleting;
use App\Models\Code;
use Illuminate\Support\Facades\Storage;

class DeleteSourceFiles
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }


    public function handle(SourceDeleting $event)
    {
        $source = $event->source;


        $codes = Code::where('project_id', $source->project_id)->get();

        foreach ($codes as $code) {
            // Delete all associated selections from that code
            $code->selectionsForSource($source->id)->delete();
        }


        if ($source->plain_text_path) {
            Storage::delete($source->plain_text_path);
        }

        if ($source->rich_text_path) {
            Storage::delete($source->rich_text_path);
        }
    }

}
