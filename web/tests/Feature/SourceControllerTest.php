<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Source;
use App\Models\SourceStatus;
use App\Models\User;
use App\Models\Variable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SourceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected string $testFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and project for testing
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        // Create test file path for storage
        $this->testFilePath = storage_path('app/projects/'.$this->project->id.'/sources');
        if (! file_exists($this->testFilePath)) {
            mkdir($this->testFilePath, 0755, true);
        }

        Storage::fake('local');
    }

    public function test_index_displays_project_sources()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('source.index', ['project' => $this->project->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('PreparationPage')
            ->has('sources')
            ->where('projectId', strval($this->project->id))
        );
    }

    public function test_store_creates_new_source()
    {
        // Define the storage path where the controller will look for the file
        $projectPath = storage_path("app/projects/{$this->project->id}/sources");
        if (! file_exists($projectPath)) {
            mkdir($projectPath, 0755, true);  // Create directory if it doesn't exist
        }

        // Create a real file in the expected storage location
        $filePath = "{$projectPath}/document.txt";
        file_put_contents($filePath, 'Test file content');  // Write some content to the file

        // Act as the authenticated user and make the request
        $response = $this->actingAs($this->user)
            ->post(route('source.store'), [
                'file' => new \Illuminate\Http\UploadedFile($filePath, 'document.txt', null, null, true),
                'projectId' => $this->project->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'newDocument' => [
                'id',
                'name',
                'type',
                'user',
                'content',
                'converted',
            ],
        ]);

        // Assert that the database has the new source record
        $this->assertDatabaseHas('sources', [
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        // Clean up the file after the test
        unlink($filePath);
    }

    public function test_store_validates_file_type()
    {
        $file = UploadedFile::fake()->create('document.docx', 100);

        $response = $this->actingAs($this->user)
            ->postJson(route('source.store'), [  // Use postJson to automatically set headers for JSON response
                'file' => $file,
                'projectId' => $this->project->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);  // Check that 'file' has validation errors
    }

    public function test_lock_and_code_successfully_locks_source()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('source.code', ['sourceId' => $source->id]));

        $response->assertStatus(302);
        $response->assertRedirect(route('coding.show', [
            'project' => $source->project_id,
            'source' => $source,
        ]));

        $this->assertDatabaseHas('variables', [
            'source_id' => $source->id,
            'name' => 'isLocked',
            'boolean_value' => true,
        ]);
    }

    public function test_unlock_source_successfully()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        // First lock the source
        Variable::create([
            'source_id' => $source->id,
            'name' => 'isLocked',
            'type_of_variable' => 'boolean',
            'boolean_value' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('source.unlock', ['sourceId' => $source->id]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('variables', [
            'source_id' => $source->id,
            'name' => 'isLocked',
            'boolean_value' => false,
        ]);
    }

    public function test_update_source_content()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        // Create the file on disk so path validation passes
        $htmlPath = $this->testFilePath.'/test.html';
        file_put_contents($htmlPath, '<p>Initial content</p>');

        $sourceStatus = SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $htmlPath,
        ]);

        $content = ['editorContent' => '<p>Updated content</p>'];

        $response = $this->actingAs($this->user)
            ->post(route('source.update'), [
                'id' => $source->id,
                'content' => $content,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals($this->user->id, $source->fresh()->modifying_user_id);
    }

    public function test_rename_source()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $newName = 'New Source Name';

        $response = $this->actingAs($this->user)
            ->post(route('source.rename', ['source' => $source->id]), [
                'name' => $newName,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Source renamed successfully',
        ]);

        $this->assertEquals($newName, $source->fresh()->name);
    }

    public function test_destroy_source()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        // Create associated files
        $uploadPath = $this->testFilePath.'/original.txt';
        $htmlPath = $this->testFilePath.'/converted.html';

        file_put_contents($uploadPath, 'test content');
        file_put_contents($htmlPath, '<p>test content</p>');

        $source->upload_path = $uploadPath;
        $source->save();

        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $htmlPath,
        ]);

        $response = $this->actingAs($this->user)
            ->delete('/files/'.$source->id);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertSoftDeleted('sources', ['id' => $source->id]);

        $this->assertFalse(file_exists($uploadPath));
        $this->assertFalse(file_exists($htmlPath));
    }

    public function test_fetch_document()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $htmlPath = $this->testFilePath.'/document.html';
        file_put_contents($htmlPath, '<p>Test content</p>');

        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $htmlPath,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/files/{$source->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'name',
            'content',
            'variables',
        ]);
    }

    public function test_download_source()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        // Create a test file
        $content = 'Test file content';
        $filePath = $this->testFilePath.'/download_test.txt';
        file_put_contents($filePath, $content);
        $source->upload_path = $filePath;
        $source->save();

        $response = $this->actingAs($this->user)
            ->post(route('sources.download', ['sourceId' => $source->id]));

        $response->assertStatus(200);
        expect($response->headers->get('content-type'))->toBe('text/plain; charset=UTF-8');
    }

    public function test_unauthorized_user_cannot_access_source()
    {
        $unauthorizedUser = User::factory()->create();
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->get("/files/{$source->id}");

        $response->assertStatus(403);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (file_exists($this->testFilePath)) {
            $files = glob($this->testFilePath.'/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->testFilePath);
        }

        parent::tearDown();
    }
}
