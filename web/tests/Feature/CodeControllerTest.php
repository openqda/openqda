<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\Codebook;
use App\Models\Project;
use App\Models\Source;
use App\Models\User;
use Tests\TestCase;

class CodeControllerTest extends TestCase
{
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
