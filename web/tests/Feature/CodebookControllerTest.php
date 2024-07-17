<?php

namespace Tests\Feature;

use App\Models\Codebook;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodebookControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful codebook creation.
     *
     * @return void
     */
    public function test_store_codebook_successfully()
    {
        // Create a user and a project
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        // Acting as the created user
        $response = $this->actingAs($user)->post(route('codebook.store', $project->id), [
            'name' => 'New Codebook',
            'description' => 'This is a description',
            'sharedWithPublic' => true,
            'sharedWithTeams' => false,
        ]);

        // Assert the codebook was created successfully
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Codebook created successfully']);
        $this->assertDatabaseHas('codebooks', [
            'name' => 'New Codebook',
            'description' => 'This is a description',
            'project_id' => $project->id,
        ]);
    }

    /**
     * Test codebook creation validation failure.
     *
     * @return void
     */
    public function test_store_codebook_validation_failure()
    {
        // Create a user and a project
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        // Acting as the created user without required name
        $response = $this->actingAs($user)->post(route('codebook.store', $project->id), [
            // 'name' => 'New Codebook', // Name is missing
            'description' => 'This is a description',
            'sharedWithPublic' => true,
            'sharedWithTeams' => false,
        ], ['Accept' => 'application/json']);


        // Assert validation fails
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /**
     * Test unauthorized codebook creation.
     *
     * @return void
     */
    public function test_store_codebook_unauthorized()
    {
        // Create a user and a project
        $user = User::factory()->create();
        $project = Project::factory()->create();

        // Acting as the created user
        $response = $this->actingAs($user)->post(route('codebook.store', $project->id), [
            'name' => 'New Codebook',
            'description' => 'This is a description',
            'sharedWithPublic' => true,
            'sharedWithTeams' => false,
        ]);

        // Assert unauthorized
        $response->assertStatus(403);
    }

    public function test_destroy_codebook_successfully()
    {
        // Create a user and a project
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);


        // Acting as the created user
        $response = $this->actingAs($user)->delete(route('codebook.destroy', [$project->id, $codebook->id]), [], ['Accept' => 'application/json']);

        // Assert the codebook was deleted successfully
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Codebook deleted']);
        $this->assertDatabaseMissing('codebooks', ['id' => $codebook->id]);
    }

    public function test_destroy_codebook_unsuccessfully()
    {
        // Create a user and a project
        $user = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create(['project_id' => $project->id, 'creating_user_id' => $user->id]);


        // Acting as the another user
        $response = $this->actingAs($user2)->delete(route('codebook.destroy', [$project->id, $codebook->id]), [], ['Accept' => 'application/json']);

        // Assert the codebook was not deleted
        $response->assertStatus(403);
        $response->assertJson(['message' => "This action is unauthorized.",]);
    }
}
