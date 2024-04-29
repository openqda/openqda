<?php

namespace App\Listeners;

use App\Events\CodebookDeleting;

class DeleteRelatedCodes
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(CodebookDeleting $event): void
    {
        $codebook = $event->codebook;
        $codes = $codebook->codes()->get();

        foreach ($codes as $code) {
            // Delete all associated selections
            $code->selections()->delete(); //<-- why this didn't work?

            // Delete the code itself
            $code->delete();
        }

        //
    }
}
