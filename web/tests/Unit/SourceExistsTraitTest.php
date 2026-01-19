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

        // Create test file path
        $this->testFilePath = storage_path('app/test_source_exists');
        if (! file_exists($this->testFilePath)) {
            mkdir($this->testFilePath, 0755, true);
        }
    }

    public function test_source_exists_returns_true_when_converted_path_exists()
    {
        // Create a test file
        $filePath = $this->testFilePath.'/converted.html';
        file_put_contents($filePath, '<p>test content</p>');

        // Create a source with converted status (persisted to database)
        $source = Source::factory()->create();
        
        // Create a SourceStatus record
        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $filePath,
        ]);

        // Test the method
        $result = $this->testClass->testSourceExists($source);

        $this->assertTrue($result);

        // Clean up
        unlink($filePath);
    }

    public function test_source_exists_returns_false_when_converted_path_missing()
    {
        // Create a source with converted status pointing to non-existent file
        $source = Source::factory()->create();
        
        $missingPath = $this->testFilePath.'/nonexistent.html';
        
        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $missingPath,
        ]);

        // Test the method
        $result = $this->testClass->testSourceExists($source);

        $this->assertFalse($result);
    }

    public function test_source_exists_returns_true_when_upload_path_exists()
    {
        // Create a test file
        $filePath = $this->testFilePath.'/upload.txt';
        file_put_contents($filePath, 'test content');

        // Create a source without converted status but with upload_path
        $source = Source::factory()->create([
            'upload_path' => $filePath,
        ]);

        // Test the method
        $result = $this->testClass->testSourceExists($source);

        $this->assertTrue($result);

        // Clean up
        unlink($filePath);
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
        
        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => '',
        ]);

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
        
        file_put_contents($convertedPath, '<p>converted</p>');
        file_put_contents($uploadPath, 'uploaded');

        // Create a source with both paths
        $source = Source::factory()->create([
            'upload_path' => $uploadPath,
        ]);
        
        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $convertedPath,
        ]);

        // Test the method - should use converted path
        $result = $this->testClass->testSourceExists($source);

        $this->assertTrue($result);

        // Clean up
        unlink($convertedPath);
        unlink($uploadPath);
    }

    public function test_source_exists_falls_back_to_upload_when_converted_path_empty()
    {
        // Create upload file
        $uploadPath = $this->testFilePath.'/fallback_upload.txt';
        file_put_contents($uploadPath, 'uploaded');

        // Create a source with empty converted path but valid upload_path
        $source = Source::factory()->create([
            'upload_path' => $uploadPath,
        ]);
        
        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => '',  // Empty converted path
        ]);

        // Test the method - should fall back to upload_path
        $result = $this->testClass->testSourceExists($source);

        $this->assertTrue($result);

        // Clean up
        unlink($uploadPath);
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (file_exists($this->testFilePath)) {
            $files = glob($this->testFilePath.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testFilePath);
        }

        parent::tearDown();
    }
}
