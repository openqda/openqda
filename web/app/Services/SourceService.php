<?php

namespace App\Services;

use App\Models\Source;
use App\Models\Variable;
use Illuminate\Support\Facades\File;

class SourceService
{
    public function destroySource($id): bool
    {

        try {

            // Find the document
            $source = Source::findOrFail($id);

            // Delete the plain_text file
            if ($source->upload_path) {
                // Assuming it's a relative path from Laravel's base directory
                $plainTextFullPath = $source->upload_path;
                if (File::exists($plainTextFullPath)) {
                    File::delete($plainTextFullPath);
                }
            }

            // Delete the rich_text file
            if ($source->converted->path) {
                // Assuming it's an absolute path from the system's root
                if (File::exists($source->converted->path)) {
                    File::delete($source->converted->path);
                }
            }

            $source->sourceStatuses()->delete();
            // Delete all variables associated with the source
            Variable::where('source_id', $source->id)->delete();
            // Delete the database record
            $source->forceDelete();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
