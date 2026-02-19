<?php

namespace App\Traits;

trait ValidatesStoragePath
{
    use ResolvesStoragePath;

    /**
     * Validate that a file path is within the allowed storage directory.
     *
     * @param  string  $path  The path to validate (can be absolute or relative)
     * @return string|null The validated real path, or null if invalid
     */
    private function validateStoragePath(string $path): ?string
    {
        $allowedDirectory = storage_path('app/projects');
        $allowedRealPath = realpath($allowedDirectory);

        // If the allowed directory doesn't exist, return null for security
        if ($allowedRealPath === false) {
            return null;
        }

        // Resolve the path (handles both absolute and relative paths)
        $resolvedPath = $this->resolveStoragePath($path);
        $realPath = realpath($resolvedPath);

        // If realpath returns false, the file doesn't exist or the path is invalid
        if ($realPath === false) {
            return null;
        }

        // Ensure the resolved path is within the allowed storage directory
        // Add trailing separator to prevent prefix matching (e.g., /projects2)
        if (! str_starts_with($realPath, $allowedRealPath.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $realPath;
    }
}
