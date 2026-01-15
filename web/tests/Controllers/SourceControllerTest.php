<?php

namespace Tests\Feature;

use App\Jobs\ConvertFileToHtmlJob;
use App\Jobs\TranscriptionJob;
use App\Models\Project;
use App\Models\Source;
use App\Models\SourceStatus;
use App\Models\User;
use App\Models\Variable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function test_index_transforms_source_metadata()
    {
        $htmlPath = $this->testFilePath.'/meta.html';
        file_put_contents($htmlPath, '<p>ok</p>');

        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        Variable::create([
            'source_id' => $source->id,
            'name' => 'isLocked',
            'type_of_variable' => 'boolean',
            'boolean_value' => true,
        ]);

        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $htmlPath,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('source.index', ['project' => $this->project->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('PreparationPage')
            ->has('sources', 1)
            ->where('sources.0.name', $source->name)
            ->where('sources.0.user', $this->user->name)
            ->where('sources.0.userPicture', $this->user->profile_photo_url)
            ->where('sources.0.date', $source->created_at->toDateString())
            ->where('sources.0.converted', true)
            ->where('sources.0.variables.isLocked', 1)
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
        $fileName = Str::random(8).'.txt';
        $filePath = "{$projectPath}/{$fileName}";
        file_put_contents($filePath, Str::random(24));

        // Act as the authenticated user and make the request
        $response = $this->actingAs($this->user)
            ->post(route('source.store'), [
                'file' => new \Illuminate\Http\UploadedFile($filePath, $fileName, null, null, true),
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
        $file = UploadedFile::fake()->create(Str::random(6).'.docx', 100);

        $response = $this->actingAs($this->user)
            ->postJson(route('source.store'), [  // Use postJson to automatically set headers for JSON response
                'file' => $file,
                'projectId' => $this->project->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
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
        file_put_contents($htmlPath, '<p>'.Str::random(12).'</p>');

        $sourceStatus = SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $htmlPath,
        ]);

        $content = ['editorContent' => '<p>'.Str::random(14).'</p>'];

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

        $newName = 'Source '.Str::random(6);

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

        file_put_contents($uploadPath, Str::random(32));
        file_put_contents($htmlPath, '<p>'.Str::random(16).'</p>');

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
        file_put_contents($htmlPath, '<p>'.Str::random(10).'</p>');

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
        $content = Str::random(24);
        $filePath = $this->testFilePath.'/download_test.txt';
        file_put_contents($filePath, $content);
        $source->upload_path = $filePath;
        $source->save();

        $response = $this->actingAs($this->user)
            ->post(route('sources.download', ['sourceId' => $source->id]));

        $response->assertStatus(200);
        $this->assertEqualsIgnoringCase('text/plain; charset=UTF-8', $response->headers->get('content-type'));
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

    public function test_store_rejects_unsupported_file_type()
    {
        $file = UploadedFile::fake()->create(Str::random(6).'.pdf', 10, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->postJson(route('source.store'), [
                'file' => $file,
                'projectId' => $this->project->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_store_txt_converts_to_html()
    {
        $firstLine = fake()->sentence();
        $content = $firstLine."\n".Str::random(12);
        $file = UploadedFile::fake()->createWithContent(Str::random(6).'.txt', $content);

        $response = $this->actingAs($this->user)
            ->post(route('source.store'), [
                'file' => $file,
                'projectId' => $this->project->id,
            ]);

        $response->assertStatus(200);
        $newDoc = $response->json('newDocument');
        $this->assertNotEmpty($newDoc['id']);
        $this->assertTrue($newDoc['converted']);
        $this->assertStringContainsString($firstLine, $newDoc['content']);
    }

    public function test_store_keyword_replaces_content_with_layout()
    {
        $keyword = 'Llanfair­pwllgwyngyll­gogery­chwyrn­drobwll­llan­tysilio­gogo­goch';
        config(['app.layoutBaseHtml' => '<html><body>blank</body></html>']);

        $file = UploadedFile::fake()->createWithContent(Str::random(6).'.txt', $keyword);

        $response = $this->actingAs($this->user)
            ->post(route('source.store'), [
                'file' => $file,
                'projectId' => $this->project->id,
            ]);

        $response->assertStatus(200);
        $newDoc = $response->json('newDocument');
        $this->assertEquals('<html><body>blank</body></html>', $newDoc['content']);
        $this->assertTrue($newDoc['converted']);
    }

    public function test_store_rtf_dispatches_conversion_job_in_production()
    {
        Queue::fake();
        $originalEnv = $this->app['env'];
        $this->app->instance('env', 'production');

        $rtf = UploadedFile::fake()->create(Str::random(6).'.rtf', 8, 'application/rtf');

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->actingAs($this->user)
            ->post(route('source.store'), [
                'file' => $rtf,
                'projectId' => $this->project->id,
            ]);

        $response->assertStatus(302);
        Queue::assertPushed(ConvertFileToHtmlJob::class);

        $this->app->instance('env', $originalEnv);
    }

    public function test_convert_txt_to_html_converts_non_utf_content()
    {
        $controller = app(\App\Http\Controllers\SourceController::class);
        $dir = storage_path('app/projects/'.$this->project->id.'/sources');
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir.'/latin1.txt';
        $latin1 = iconv('UTF-8', 'ISO-8859-1', "caf\xC3\xA9");
        file_put_contents($path, $latin1);

        $html = $controller->convertTxtToHtml($path, $this->project->id);

        $this->assertStringContainsString('caf', $html);
        $this->assertFileExists($dir.'/latin1.html');
    }

    public function test_convert_txt_to_html_handles_missing_file()
    {
        $controller = app(\App\Http\Controllers\SourceController::class);
        $missingPath = $this->testFilePath.'/missing.txt';

        $previousHandler = set_error_handler(fn () => true);
        $html = $controller->convertTxtToHtml($missingPath, $this->project->id);
        if ($previousHandler !== null) {
            set_error_handler($previousHandler);
        } else {
            restore_error_handler();
        }

        $this->assertSame('Error reading file.', $html);
        $this->assertFileDoesNotExist($this->testFilePath.'/missing.html');
    }

    public function test_get_html_content_from_admin_panel_updates_converted_path()
    {
        Queue::fake();
        $originalEnv = $this->app['env'];
        $this->app->instance('env', 'production');

        $rtfPath = $this->testFilePath.'/admin_panel.rtf';
        file_put_contents($rtfPath, '{\rtf1 admin test}');

        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'upload_path' => $rtfPath,
        ]);
        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $this->testFilePath.'/admin_panel_old.html',
        ]);

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->actingAs($this->user)
            ->post("/projects/{$this->project->id}/sources/{$source->id}/gethtmlcontent");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Conversion in progress']);

        $this->assertStringEndsWith('.html', $source->fresh()->converted->path);

        $this->app->instance('env', $originalEnv);
    }

    public function test_fetch_document_returns_404_when_not_converted()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/files/{$source->id}");

        $response->assertStatus(404);
    }

    public function test_fetch_document_returns_400_for_invalid_path()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $invalidPath = storage_path('app/invalid/outside.html');
        if (! file_exists(dirname($invalidPath))) {
            mkdir(dirname($invalidPath), 0755, true);
        }
        file_put_contents($invalidPath, '<p>bad</p>');

        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $invalidPath,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/files/{$source->id}");

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid file path']);

        if (file_exists($invalidPath)) {
            unlink($invalidPath);
        }
    }

    public function test_update_errors_when_converted_missing()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('source.update'), [
                'id' => $source->id,
                'content' => ['editorContent' => '<p>content</p>'],
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => false, 'message' => 'Source file not found']);
    }

    public function test_update_errors_when_path_is_invalid()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $invalidPath = base_path('outside.html');
        file_put_contents($invalidPath, '<p>bad</p>');

        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $invalidPath,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('source.update'), [
                'id' => $source->id,
                'content' => ['editorContent' => '<p>content</p>'],
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => false, 'message' => 'Invalid file path']);

        if (file_exists($invalidPath)) {
            unlink($invalidPath);
        }
    }

    public function test_destroy_denies_when_user_not_in_project()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->delete('/files/'.$source->id);

        $response->assertStatus(403);
        $response->assertJson(['success' => false, 'message' => 'Not allowed']);
    }

    public function test_download_records_audit_event()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $filePath = $this->testFilePath.'/audit_download.txt';
        file_put_contents($filePath, Str::random(32));
        $source->update(['upload_path' => $filePath, 'name' => Str::random(8).'.txt']);

        $this->actingAs($this->user)
            ->post(route('sources.download', ['sourceId' => $source->id]));

        $this->assertDatabaseHas('audits', [
            'user_id' => $this->user->id,
            'event' => 'source.downloaded',
            'auditable_id' => $source->id,
        ]);
    }

    public function test_retry_conversion_dispatches_job_in_production_mode()
    {
        Queue::fake();
        $originalEnv = $this->app['env'];
        $this->app->instance('env', 'production');

        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'upload_path' => $this->testFilePath.'/convert_me.rtf',
        ]);
        file_put_contents($source->upload_path, '{\rtf1 test}');
        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $this->testFilePath.'/convert_me.html',
        ]);

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->actingAs($this->user)
            ->post("/projects/{$this->project->id}/sources/{$source->id}/gethtmlcontent");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Conversion in progress']);

        Queue::assertPushed(ConvertFileToHtmlJob::class);

        $this->app->instance('env', $originalEnv);
    }

    public function test_retry_conversion_denies_for_unauthorized_user()
    {
        Queue::fake();
        $unauthorized = User::factory()->create();

        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'upload_path' => $this->testFilePath.'/deny.rtf',
        ]);
        file_put_contents($source->upload_path, '{\rtf1 deny}');

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->actingAs($unauthorized)
            ->post("/projects/{$this->project->id}/sources/{$source->id}/gethtmlcontent");

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Not authorized to view this source']);

        Queue::assertNothingPushed();
    }

    public function test_transcribe_uploads_audio_and_dispatches_job()
    {
        Queue::fake();

        $audio = UploadedFile::fake()->create(Str::random(6).'.mp3', 20, 'audio/mpeg');

        $response = $this->actingAs($this->user)
            ->post(route('source.transcribe'), [
                'file' => $audio,
                'project_id' => $this->project->id,
                'model' => fake()->word(),
                'language' => 'en',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'newDocument' => ['id', 'name', 'type', 'user', 'content', 'converted']]);

        Queue::assertPushed(TranscriptionJob::class);
    }

    public function test_retry_transcription_when_failed_requeues()
    {
        Queue::fake();

        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'upload_path' => $this->testFilePath.'/audio.mp3',
        ]);
        file_put_contents($source->upload_path, 'audio');

        Variable::create([
            'source_id' => $source->id,
            'name' => 'transcription_job_status',
            'type_of_variable' => 'text',
            'text_value' => 'failed',
        ]);

        $response = $this->actingAs($this->user)
            ->post("/projects/{$this->project->id}/sources/{$source->id}/retrytranscription");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Conversion restarted', 'status' => 'restarted']);

        Queue::assertPushed(TranscriptionJob::class);
    }

    public function test_retry_transcription_when_running_returns_status()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'upload_path' => $this->testFilePath.'/audio_running.mp3',
        ]);
        file_put_contents($source->upload_path, 'audio');

        Variable::create([
            'source_id' => $source->id,
            'name' => 'transcription_job_status',
            'type_of_variable' => 'text',
            'text_value' => 'running',
        ]);

        $response = $this->actingAs($this->user)
            ->post("/projects/{$this->project->id}/sources/{$source->id}/retrytranscription");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Conversion is still running', 'status' => 'running']);
    }

    public function test_retry_transcription_when_job_missing_returns_finished()
    {
        $source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'upload_path' => $this->testFilePath.'/audio_missing.mp3',
        ]);
        file_put_contents($source->upload_path, 'audio');

        $response = $this->actingAs($this->user)
            ->post("/projects/{$this->project->id}/sources/{$source->id}/retrytranscription");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Conversion has finished', 'status' => 'finished']);
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
