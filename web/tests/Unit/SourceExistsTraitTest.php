<?php

namespace Tests\Unit;

use App\Models\Source;
use App\Models\SourceStatus;
use App\Traits\SourceExists;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SourceExistsTraitTest extends TestCase
{
    use RefreshDatabase;

    private $testClass;

    private string $testFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an anonymous class that uses the trait
        $this->testClass = new class
        {
            use SourceExists;

            public function testSourceExists(Source $source): bool
            {
                return $this->sourceExists($source);
            }
        };

        // Create test file path with unique identifier
        $this->testFilePath = storage_path('app/test_source_exists_'.uniqid());
        File::makeDirectory($this->testFilePath, 0755, true);
    }

    /**
     * Helper method to create a SourceStatus record for a source
     */
    private function createSourceStatus(Source $source, string $path): SourceStatus
    {
        return SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $path,
        ]);
    }

    public function test_source_exists_returns_true_when_converted_path_exists()
    {
        // Create a test file
        $filePath = $this->testFilePath.'/converted.html';
        File::put($filePath, '<p>test content</p>');

        // Create a source with converted status (persisted to database)
        $source = Source::factory()->create();
        
        // Create a SourceStatus record
        $this->createSourceStatus($source, $filePath);

        // Test the method
        $result = $this->testClass->testSourceExists($source);

        $this->assertTrue($result);
    }

    public function test_source_exists_returns_false_when_converted_path_missing()
    {
        // Create a source with converted status pointing to non-existent file
        $source = Source::factory()->create();
        
        $missingPath = $this->testFilePath.'/nonexistent.html';
        
        $this->createSourceStatus($source, $missingPath);

        // Test the method
        $result = $this->testClass->testSourceExists($source);

        $this->assertFalse($result);
    }

    public function test_source_exists_returns_true_when_upload_path_exists()
    {
        // Create a test file
        $filePath = $this->testFilePath.'/upload.txt';
        File::put($filePath, 'test content');

        // Create a source without converted status but with upload_path
        $source = Source::factory()->create([
            'upload_path' => $filePath,
        ]);

        // Test the method
        $result = $this->testClass->testSourceExists($source);

        $this->assertTrue($result);
    }

    public function test_source_exists_returns_false_when_upload_path_missing()
    {
        // Create a source with upload_path pointing to non-existent file
        $missingPath = $this->testFilePath.'/missing_upload.txt';
        
        $source = Source::factory()->create([
            'upload_path' => $missingPath,
        ]);

        // Test the method
        $result = $this->testClass->testSourceExists($source);

        $this->assertFalse($result);
    }

    public function test_source_exists_returns_false_when_both_paths_are_null()
    {
        // Create a source with null paths
        $source = Source::factory()->create([
            'upload_path' => null,
        ]);

        // Test the method
        $result = $this->testClass->testSourceExists($source);

        $this->assertFalse($result);
    }

    public function test_source_exists_returns_false_when_converted_path_is_empty_string()
    {
        // Create a source with empty converted path
        $source = Source::factory()->create();
        
        $this->createSourceStatus($source, '');

        // Test the method
        $result = $this->testClass->testSourceExists($source);

        $this->assertFalse($result);
    }

    public function test_source_exists_returns_false_when_upload_path_is_empty_string()
    {
        // Create a source with empty upload_path
        $source = Source::factory()->create([
            'upload_path' => '',
        ]);

        // Test the method
        $result = $this->testClass->testSourceExists($source);

        $this->assertFalse($result);
    }

    public function test_source_exists_prefers_converted_path_over_upload_path()
    {
        // Create two test files
        $convertedPath = $this->testFilePath.'/converted_priority.html';
        $uploadPath = $this->testFilePath.'/upload_priority.txt';
        
        File::put($convertedPath, '<p>converted</p>');
        File::put($uploadPath, 'uploaded');

        // Create a source with both paths
        $source = Source::factory()->create([
            'upload_path' => $uploadPath,
        ]);
        
        $this->createSourceStatus($source, $convertedPath);

        // Test the method - should use converted path
        $result = $this->testClass->testSourceExists($source);

        $this->assertTrue($result);
    }

    public function test_source_exists_falls_back_to_upload_when_converted_path_empty()
    {
        // Create upload file
        $uploadPath = $this->testFilePath.'/fallback_upload.txt';
        File::put($uploadPath, 'uploaded');

        // Create a source with empty converted path but valid upload_path
        $source = Source::factory()->create([
            'upload_path' => $uploadPath,
        ]);
        
        $this->createSourceStatus($source, '');  // Empty converted path

        // Test the method - should fall back to upload_path
        $result = $this->testClass->testSourceExists($source);

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        // Clean up test directory recursively
        if (File::exists($this->testFilePath)) {
            File::deleteDirectory($this->testFilePath);
        }

        parent::tearDown();
    }
}
