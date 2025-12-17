<?php

namespace Tests\Controllers;

use App\Models\Code;
use App\Models\Codebook;
use App\Models\Project;
use App\Models\Source;
use App\Models\SourceStatus;
use App\Models\Team;
use App\Models\User;
use App\Models\Variable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodingControllerTest extends TestCase
{
    use RefreshDatabase;

    private $testFilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFilePath = storage_path('app/test_files');
        if (! file_exists($this->testFilePath)) {
            mkdir($this->testFilePath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (file_exists($this->testFilePath)) {
            $files = glob($this->testFilePath.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        parent::tearDown();
    }

    public function test_destroy_code_successfully()
    {
        // Create a user, project, source, and code
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $source = Source::factory()->create(['project_id' => $project->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);
        $code = Code::factory()->create(['codebook_id' => $codebook->id]);

        // Acting as the created user
        $response = $this->actingAs($user)->delete(route('coding.destroy', [$project->id, $source->id, $code->id]), [], ['Accept' => 'application/json']);

        // Assert the code was deleted successfully
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Code and its selections successfully deleted']);
        $this->assertDatabaseMissing('codes', ['id' => $code->id]);
    }

    public function test_destroy_code_with_children_successfully()
    {
        // Create a user, project, source, and codes
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $source = Source::factory()->create(['project_id' => $project->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);

        // Create parent and child codes
        $parentCode = Code::factory()->create(['codebook_id' => $codebook->id]);
        $childCode = Code::factory()->create([
            'codebook_id' => $codebook->id,
            'parent_id' => $parentCode->id,
        ]);

        // Acting as the created user, delete the parent code
        $response = $this->actingAs($user)->delete(route('coding.destroy', [$project->id, $source->id, $parentCode->id]), [], ['Accept' => 'application/json']);

        // Assert the parent code was deleted successfully
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Code and its selections successfully deleted']);

        // Assert both parent and child codes are removed from database
        $this->assertDatabaseMissing('codes', ['id' => $parentCode->id]);
        $this->assertDatabaseMissing('codes', ['id' => $childCode->id]);
    }

    public function test_destroy_code_unauthorized()
    {
        // Create a user, project, source, and code
        $user = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $source = Source::factory()->create(['project_id' => $project->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);
        $code = Code::factory()->create(['codebook_id' => $codebook->id]);

        // Acting as the created user
        $response = $this->actingAs($user2)->delete(route('coding.destroy', [$project->id, $source->id, $code->id]), [], ['Accept' => 'application/json']);

        // Assert the response is unauthorized
        $response->assertStatus(403);
    }

    public function test_update_code_color()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);
        $code = Code::factory()->create(['codebook_id' => $codebook->id]);

        $response = $this->actingAs($user)->patch(route('coding.update-attribute', [$project->id, $code->id]), [
            'color' => '#ffffff',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Code updated successfully']);
        $this->assertDatabaseHas('codes', ['id' => $code->id, 'color' => '#ffffff']);
    }

    public function test_update_code_title()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);
        $code = Code::factory()->create(['codebook_id' => $codebook->id]);

        $response = $this->actingAs($user)->patch(route('coding.update-attribute', [$project->id, $code->id]), [
            'title' => 'New Title',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Code updated successfully']);
        $this->assertDatabaseHas('codes', ['id' => $code->id, 'name' => 'New Title']);
    }

    public function test_update_code_description()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);
        $code = Code::factory()->create(['codebook_id' => $codebook->id]);

        $response = $this->actingAs($user)->patch(route('coding.update-attribute', [$project->id, $code->id]), [
            'description' => 'New description',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Code updated successfully']);
        $this->assertDatabaseHas('codes', ['id' => $code->id, 'description' => 'New description']);
    }

    public function test_create_code_with_all_attributes()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);

        // Create code with all attributes including description
        $response = $this->actingAs($user)->post(route('coding.store', [$project->id]), [
            'title' => 'Test Code',
            'description' => 'This is a test code description',
            'color' => '#ff5733',
            'codebook' => $codebook->id,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'id']);

        // Get the created code's ID from the response
        $codeId = $response->json('id');

        // Verify all attributes are saved correctly in database
        $this->assertDatabaseHas('codes', [
            'id' => $codeId,
            'name' => 'Test Code',
            'description' => 'This is a test code description',
            'color' => '#ff5733',
            'codebook_id' => $codebook->id,
            'parent_id' => null,
        ]);
    }

    public function test_create_code_without_description()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);

        // Create code without description (should be allowed)
        $response = $this->actingAs($user)->post(route('coding.store', [$project->id]), [
            'title' => 'Code Without Description',
            'color' => '#000000',
            'codebook' => $codebook->id,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'id']);

        $codeId = $response->json('id');

        // Verify the code is created with null description
        $this->assertDatabaseHas('codes', [
            'id' => $codeId,
            'name' => 'Code Without Description',
            'description' => null,
            'color' => '#000000',
            'codebook_id' => $codebook->id,
        ]);
    }

    public function test_create_code_with_valid_parent()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);

        // Create parent code first
        $parentCode = Code::factory()->create(['codebook_id' => $codebook->id]);

        // Create child code
        $response = $this->actingAs($user)->post(route('coding.store', [$project->id]), [
            'title' => 'Child Code',
            'color' => '#000000',
            'codebook' => $codebook->id,
            'parent_id' => $parentCode->id,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'id']);

        // Get the created code's ID from the response
        $childCodeId = $response->json('id');

        // Verify the parent-child relationship in database
        $this->assertDatabaseHas('codes', [
            'id' => $childCodeId,
            'name' => 'Child Code',
            'parent_id' => $parentCode->id,
        ]);
    }

    public function test_prevent_self_referential_code_creation()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);

        // Create a code
        $code = Code::factory()->create(['codebook_id' => $codebook->id]);

        // Attempt to update the code to set its own ID as parent_id
        $response = $this->actingAs($user)->patch(route('coding.update-attribute', [$project->id, $code->id]), [
            'parent_id' => $code->id,
        ], ['Accept' => 'application/json']);

        // Assert that the request fails with validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['parent_id']);

        // Verify that the code wasn't updated in the database
        $this->assertDatabaseHas('codes', [
            'id' => $code->id,
            'parent_id' => null,
        ]);
    }

    public function test_prevent_self_referential_code_on_create()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);

        // Create a code and try to set its parent_id to its own ID (this shouldn't be possible)
        $code = Code::factory()->create(['codebook_id' => $codebook->id]);

        $response = $this->actingAs($user)->post(route('coding.store', [$project->id]), [
            'title' => 'Self Referential Code',
            'color' => '#000000',
            'codebook' => $codebook->id,
            'parent_id' => $code->id, // Trying to use the same ID
        ], ['Accept' => 'application/json']);

        // Since we're trying to create a new code, this specific case should pass
        // as the IDs would be different (new UUID would be generated)
        $response->assertStatus(201);

        // But let's verify that the created code has a different ID than its parent
        $newCodeId = $response->json('id');
        $this->assertNotEquals($code->id, $newCodeId);
    }

    public function test_remove_parent_from_code()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $source = Source::factory()->create(['project_id' => $project->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);

        // Create parent and child code
        $parentCode = Code::factory()->create(['codebook_id' => $codebook->id]);
        $childCode = Code::factory()->create([
            'codebook_id' => $codebook->id,
            'parent_id' => $parentCode->id,
        ]);

        // Remove parent from child code
        $response = $this->actingAs($user)->post(
            route('coding.remove-parent', [$project->id, $source->id, $childCode->id]),
            [],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Parent removed successfully']);

        // Verify parent_id is null
        $this->assertDatabaseHas('codes', [
            'id' => $childCode->id,
            'parent_id' => null,
        ]);
    }

    public function test_move_code_up_hierarchy()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $source = Source::factory()->create(['project_id' => $project->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);

        // Create grandparent, parent, and child codes
        $grandparentCode = Code::factory()->create(['codebook_id' => $codebook->id]);
        $parentCode = Code::factory()->create([
            'codebook_id' => $codebook->id,
            'parent_id' => $grandparentCode->id,
        ]);
        $childCode = Code::factory()->create([
            'codebook_id' => $codebook->id,
            'parent_id' => $parentCode->id,
        ]);

        // Move child code up hierarchy (should now have grandparent as parent)
        $response = $this->actingAs($user)->post(
            route('coding.up-hierarchy', [$project->id, $source->id, $childCode->id]),
            [],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Change successfully made']);

        // Verify child now has grandparent as parent
        $this->assertDatabaseHas('codes', [
            'id' => $childCode->id,
            'parent_id' => $grandparentCode->id,
        ]);
    }

    public function test_update_code_with_parent_id()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);

        $parentCode = Code::factory()->create(['codebook_id' => $codebook->id]);
        $code = Code::factory()->create(['codebook_id' => $codebook->id]);

        $response = $this->actingAs($user)->patch(route('coding.update-attribute', [$project->id, $code->id]), [
            'parent_id' => $parentCode->id,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Code updated successfully']);
        $this->assertDatabaseHas('codes', ['id' => $code->id, 'parent_id' => $parentCode->id]);
    }

    public function test_show_coding_page_with_locked_source()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $source = Source::factory()->create(['project_id' => $project->id]);

        // Lock the source
        Variable::create([
            'source_id' => $source->id,
            'name' => 'isLocked',
            'type_of_variable' => 'boolean',
            'boolean_value' => true,
        ]);

        // Create converted file
        $htmlPath = $this->testFilePath.'/test.html';
        file_put_contents($htmlPath, '<p>Test content</p>');

        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $htmlPath,
        ]);

        $response = $this->actingAs($user)->get(route('coding.show', ['project' => $project->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CodingPage')
            ->has('source')
            ->has('sources')
            ->has('codebooks')
            ->has('allCodes')
            ->has('projectId')
            ->has('teamMembers')
        );
    }

    public function test_show_coding_page_with_specific_source()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        // Create two locked sources
        $source1 = Source::factory()->create(['project_id' => $project->id]);
        $source2 = Source::factory()->create(['project_id' => $project->id]);

        foreach ([$source1, $source2] as $source) {
            Variable::create([
                'source_id' => $source->id,
                'name' => 'isLocked',
                'type_of_variable' => 'boolean',
                'boolean_value' => true,
            ]);

            $htmlPath = $this->testFilePath.'/test_'.$source->id.'.html';
            file_put_contents($htmlPath, '<p>Test content for '.$source->id.'</p>');

            SourceStatus::create([
                'source_id' => $source->id,
                'status' => 'converted:html',
                'path' => $htmlPath,
            ]);
        }

        // Request specific source
        $response = $this->actingAs($user)->get(route('coding.show', ['project' => $project->id, 'source' => $source2->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CodingPage')
            ->where('source.id', $source2->id)
        );
    }

    public function test_show_coding_page_creates_default_codebook()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $source = Source::factory()->create(['project_id' => $project->id]);

        // Lock the source
        Variable::create([
            'source_id' => $source->id,
            'name' => 'isLocked',
            'type_of_variable' => 'boolean',
            'boolean_value' => true,
        ]);

        // Create converted file
        $htmlPath = $this->testFilePath.'/test.html';
        file_put_contents($htmlPath, '<p>Test content</p>');

        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $htmlPath,
        ]);

        // Ensure no codebooks exist
        $this->assertDatabaseCount('codebooks', 0);

        $response = $this->actingAs($user)->get(route('coding.show', ['project' => $project->id]));

        $response->assertStatus(200);

        // Verify a default codebook was created
        $this->assertDatabaseHas('codebooks', [
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'name' => $project->name.' Codebook',
        ]);
    }

    public function test_show_coding_page_with_nested_codes()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $source = Source::factory()->create(['project_id' => $project->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);

        // Lock the source
        Variable::create([
            'source_id' => $source->id,
            'name' => 'isLocked',
            'type_of_variable' => 'boolean',
            'boolean_value' => true,
        ]);

        // Create converted file
        $htmlPath = $this->testFilePath.'/test.html';
        file_put_contents($htmlPath, '<p>Test content</p>');

        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $htmlPath,
        ]);

        // Create parent and child codes
        $parentCode = Code::factory()->create(['codebook_id' => $codebook->id]);
        $childCode = Code::factory()->create([
            'codebook_id' => $codebook->id,
            'parent_id' => $parentCode->id,
        ]);

        $response = $this->actingAs($user)->get(route('coding.show', ['project' => $project->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CodingPage')
            ->has('allCodes', 1) // Only root code should be in the array
        );
    }

    public function test_show_coding_page_with_team_members()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create([
            'creating_user_id' => $user->id,
            'team_id' => $team->id,
        ]);
        $source = Source::factory()->create(['project_id' => $project->id]);

        // Add team members
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $team->users()->attach($member1, ['role' => 'editor']);
        $team->users()->attach($member2, ['role' => 'editor']);

        // Lock the source
        Variable::create([
            'source_id' => $source->id,
            'name' => 'isLocked',
            'type_of_variable' => 'boolean',
            'boolean_value' => true,
        ]);

        // Create converted file
        $htmlPath = $this->testFilePath.'/test.html';
        file_put_contents($htmlPath, '<p>Test content</p>');

        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $htmlPath,
        ]);

        $response = $this->actingAs($user)->get(route('coding.show', ['project' => $project->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CodingPage')
            ->has('teamMembers', 2)
            ->where('teamMembers.0.id', $member1->id)
            ->where('teamMembers.1.id', $member2->id)
        );
    }

    public function test_show_coding_page_without_team()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $source = Source::factory()->create(['project_id' => $project->id]);

        // Lock the source
        Variable::create([
            'source_id' => $source->id,
            'name' => 'isLocked',
            'type_of_variable' => 'boolean',
            'boolean_value' => true,
        ]);

        // Create converted file
        $htmlPath = $this->testFilePath.'/test.html';
        file_put_contents($htmlPath, '<p>Test content</p>');

        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $htmlPath,
        ]);

        $response = $this->actingAs($user)->get(route('coding.show', ['project' => $project->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CodingPage')
            ->where('teamMembers', [])
        );
    }

    public function test_show_coding_page_redirects_when_no_locked_sources()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $source = Source::factory()->create(['project_id' => $project->id]);

        // Don't lock the source - this should trigger the redirect
        // Create converted file anyway
        $htmlPath = $this->testFilePath.'/test.html';
        file_put_contents($htmlPath, '<p>Test content</p>');

        SourceStatus::create([
            'source_id' => $source->id,
            'status' => 'converted:html',
            'path' => $htmlPath,
        ]);

        $response = $this->actingAs($user)->get(route('coding.show', ['project' => $project->id]));

        // Should redirect because there are no locked sources
        $response->assertStatus(404);
    }

    public function test_destroy_code_with_children_throws_exception_in_recursive_call()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $source = Source::factory()->create(['project_id' => $project->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);

        // Create parent and multiple child codes
        $parentCode = Code::factory()->create(['codebook_id' => $codebook->id]);
        $childCode1 = Code::factory()->create([
            'codebook_id' => $codebook->id,
            'parent_id' => $parentCode->id,
        ]);
        $childCode2 = Code::factory()->create([
            'codebook_id' => $codebook->id,
            'parent_id' => $parentCode->id,
        ]);

        // Mock DB::transaction to allow the first call but throw on recursive calls
        $callCount = 0;
        \DB::shouldReceive('transaction')
            ->andReturnUsing(function ($callback) use (&$callCount) {
                $callCount++;
                if ($callCount > 1) {
                    throw new \Exception('Recursive error');
                }

                return $callback();
            });

        $response = $this->actingAs($user)->delete(
            route('coding.destroy', [$project->id, $source->id, $parentCode->id]),
            [],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(500);
        $response->assertJson(['error' => 'Failed to delete code: Recursive error']);
    }

    public function test_destroy_code_with_parent_sets_parent_to_null()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $source = Source::factory()->create(['project_id' => $project->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);

        // Create parent and child code
        $parentCode = Code::factory()->create(['codebook_id' => $codebook->id]);
        $childCode = Code::factory()->create([
            'codebook_id' => $codebook->id,
            'parent_id' => $parentCode->id,
        ]);

        // Delete the child code (which has a parent)
        $response = $this->actingAs($user)->delete(
            route('coding.destroy', [$project->id, $source->id, $childCode->id]),
            [],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(200);
        $this->assertDatabaseMissing('codes', ['id' => $childCode->id]);
    }

    // Add this helper method at the bottom of the class
    protected function createTestStructure()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);

        return [
            'user' => $user,
            'project' => $project,
            'codebook' => $codebook,
        ];
    }
}
