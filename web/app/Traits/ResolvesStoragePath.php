<?php

namespace App\Traits;

trait ResolvesStoragePath
{
    /**
     * Resolve a path that may be absolute (legacy) or relative (new format).
     * 
     * Relative paths should be in the format: storage/app/projects/{projectId}/sources/{filename}
     * Absolute paths are those starting with "/" (e.g., /var/www/storage/app/...)
     *
     * @param  string  $path  The path to resolve
     * @return string The resolved absolute path
     */
    protected function resolveStoragePath(string $path): string
    {
        // If path starts with /, it's an absolute path (legacy format)
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Otherwise, it's a relative path (new format)
        // Relative paths are stored as: storage/app/projects/...
        // We need to prepend the Laravel base path
        $basePath = base_path();
        
        // Remove any leading "storage/app/" since storage_path() already includes it
        if (str_starts_with($path, 'storage/app/')) {
            $path = substr($path, strlen('storage/app/'));
            return storage_path('app/' . $path);
        }
        
        // Fallback: return as-is appended to base path
        return $basePath . '/' . $path;
    }

    /**
     * Convert an absolute path to a relative path format.
     * This is used when storing new paths in the database.
     *
     * @param  string  $absolutePath  The absolute path to convert
     * @return string The relative path in format: storage/app/projects/...
     */
    protected function makeRelativePath(string $absolutePath): string
    {
        $storageAppPath = storage_path('app');
        
        // If the path contains storage/app, extract the relative portion
        if (str_contains($absolutePath, '/storage/app/')) {
            $position = strpos($absolutePath, '/storage/app/');
            return substr($absolutePath, $position + 1); // +1 to remove leading slash
        }
        
        // If it's already under storage_path('app'), make it relative
        if (str_starts_with($absolutePath, $storageAppPath)) {
            $relativePath = substr($absolutePath, strlen($storageAppPath) + 1);
            return 'storage/app/' . $relativePath;
        }
        
        // If we can't determine, return as-is (shouldn't happen in normal operation)
        return $absolutePath;
    }
}
