# Storage Path Migration Guide

## Overview

OpenQDA now stores file paths in the database using a relative format instead of absolute paths. This change makes the application more portable and prevents issues when migrating to servers with different directory structures.

## What Changed

### Previous Behavior (Legacy)
- File paths were stored as absolute paths (e.g., `/var/www/storage/app/projects/123/sources/file.txt`)
- This caused problems when:
  - Migrating to a new server with a different base path
  - Moving the application to a different directory
  - Using different environments (development, staging, production) with different paths

### Current Behavior (New)
- New file paths are stored as relative paths (e.g., `storage/app/projects/123/sources/file.txt`)
- The application automatically resolves these to absolute paths when needed
- **Backwards Compatible**: Legacy absolute paths continue to work seamlessly

## Path Resolution Logic

### Detection Strategy
The system determines path type based on the first character:
- **Absolute path** (legacy): Starts with `/` (e.g., `/var/www/storage/...`)
- **Relative path** (new): Does not start with `/` (e.g., `storage/app/...`)

### Resolution Process
When the application needs to access a file:

1. **For absolute paths**: Used directly (legacy compatibility)
2. **For relative paths**: Converted to absolute by prepending the Laravel base path

## Technical Implementation

### Core Components

#### `ResolvesStoragePath` Trait
Location: `web/app/Traits/ResolvesStoragePath.php`

Provides two key methods:
- `resolveStoragePath(string $path): string` - Converts any path to absolute
- `makeRelativePath(string $absolutePath): string` - Converts absolute to relative

#### `ValidatesStoragePath` Trait
Location: `web/app/Traits/ValidatesStoragePath.php`

Enhanced to:
- Use `ResolvesStoragePath` for path resolution
- Validate both absolute and relative paths
- Ensure resolved paths are within the allowed storage directory

### Affected Operations

All source file operations now support both path formats:

1. **Upload** (`SourceController::store`): Stores new files with relative paths
2. **Fetch** (`SourceController::fetchDocument`): Reads files using either path format
3. **Update** (`SourceController::update`): Updates files using either path format
4. **Download** (`SourceController::download`): Serves files using either path format
5. **Delete** (`SourceController::destroy`): Removes files using either path format
6. **Conversion Jobs**: Store converted files with relative paths
   - `ConvertFileToHtmlJob`: RTF/Markdown conversion
   - `TranscriptionJob`: Audio transcription

## Migration Strategy

### For Existing Installations

**Good News**: No migration is required! 

- Existing sources with absolute paths continue to work
- New uploads automatically use relative paths
- The system handles both formats transparently

### For New Installations

- All file paths are stored as relative paths from the start
- No special configuration needed

## Database Schema

The following columns store file paths:

### `sources` Table
- `upload_path`: Path to the original uploaded file
  - Legacy: `/var/www/storage/app/projects/123/sources/uuid.txt`
  - New: `storage/app/projects/123/sources/uuid.txt`

### `source_statuses` Table
- `path`: Path to the converted HTML file
  - Legacy: `/var/www/storage/app/projects/123/sources/uuid.html`
  - New: `storage/app/projects/123/sources/uuid.html`

## Testing

Comprehensive test coverage ensures both path formats work correctly:

```php
// Test that new sources use relative paths
test_store_saves_relative_paths_for_new_sources()

// Test legacy absolute paths still work
test_fetch_document_with_absolute_path_legacy()
test_destroy_source_with_absolute_paths_legacy()

// Test relative paths work correctly
test_fetch_document_with_relative_path()
test_download_source_with_relative_path()
test_destroy_source_with_relative_paths()
```

## Security Considerations

The path validation system ensures:

1. **Path Traversal Protection**: All paths are validated to be within `storage/app/projects`
2. **Symbolic Link Resolution**: Paths are resolved using `realpath()` to prevent symlink attacks
3. **Whitelist Validation**: Only paths under the allowed storage directory are accepted

These security measures apply to both absolute and relative paths.

## Configuration

No configuration changes are required. The system uses Laravel's built-in `storage_path()` helper, which respects the application's base path.

### Environment Variables

The existing configuration works without modification:
- `APP_BASE_PATH`: Laravel's base path (automatically set)
- No new environment variables needed

## Troubleshooting

### Issue: Files not found after migration

**Symptom**: 404 errors when accessing source files

**Solution**: 
1. Verify file permissions on the storage directory
2. Check that the storage path matches Laravel's configured path
3. Ensure `storage/app/projects` directory exists

### Issue: Path validation failures

**Symptom**: "Invalid file path" errors

**Solution**:
1. Check file exists at the expected location
2. Verify storage directory permissions
3. Ensure paths don't contain symbolic links to locations outside storage

## Example Usage

### Creating a Source with Relative Path

```php
use App\Traits\ResolvesStoragePath;

class YourController extends Controller
{
    use ResolvesStoragePath;

    public function store($file, $projectId)
    {
        // Store file
        $relativePath = $file->storeAs("projects/{$projectId}/sources", $filename);
        $absolutePath = storage_path("app/{$relativePath}");
        
        // Convert to relative for database
        $relativeDatabasePath = $this->makeRelativePath($absolutePath);
        
        // Save to database
        Source::create([
            'upload_path' => $relativeDatabasePath, // Relative path
            // ...
        ]);
    }
}
```

### Reading a Source (Works with Both Formats)

```php
use App\Traits\ValidatesStoragePath;

class YourController extends Controller
{
    use ValidatesStoragePath;

    public function read($source)
    {
        // Path can be relative or absolute
        $validatedPath = $this->validateStoragePath($source->upload_path);
        
        if ($validatedPath === null) {
            abort(400, 'Invalid file path');
        }
        
        // Read file
        $content = file_get_contents($validatedPath);
    }
}
```

## Benefits

1. **Portability**: Application can be moved without database updates
2. **Environment Flexibility**: Same database works across dev/staging/production
3. **Backwards Compatible**: No migration needed for existing installations
4. **Cleaner Paths**: Shorter, more readable database entries
5. **Docker Friendly**: Easier to manage in containerized environments

## Future Considerations

### Batch Migration Script (Optional)

For installations that want to convert all legacy paths to relative format, a migration script can be created:

```bash
php artisan migrate:storage-paths
```

**Note**: This is purely optional and cosmetic. The application works correctly with mixed path formats.

## References

- Laravel Storage Documentation: https://laravel.com/docs/11.x/filesystem
- Issue: https://github.com/openqda/openqda/issues/[issue-number]
- Pull Request: https://github.com/openqda/openqda/pull/[pr-number]
