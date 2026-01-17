<?php

namespace App\Traits;

use App\Models\Source;
use Illuminate\Support\Facades\File;

trait SourceExists
{
    /**
     * Validate that a source exists by given path,
     * either by its exists property or by checking the file system.
     * Does not validate whether the path is within allowed storage.
     *
     * @param  Source  $source  The source to validate
     * @return bool True if the source exists, false otherwise
     */
    private function sourceExists(Source $source): bool
    {
        // Check if the file exists in storage
        $path = $source->converted ? $source->converted->path : $source->upload_path;

        return File::exists($path);
    }
}
