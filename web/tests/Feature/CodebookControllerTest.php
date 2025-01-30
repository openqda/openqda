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
        $response->assertJson(['message' => 'This action is unauthorized.']);
    }

    /**
     * Test updating code order while preserving other properties.
     *
     * @return void
     */
    public function test_update_code_order_successfully()
    {
        // Create a user and a project
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        // Create a codebook with existing properties
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'properties' => [
                'sharedWithPublic' => true,
                'sharedWithTeams' => false,
                'code_order' => [
                    ['id' => 'code-1'],
                    ['id' => 'code-2'],
                ],
            ],
        ]);

        // New code order to be set
        $newCodeOrder = [
            ['id' => 'code-2'],
            ['id' => 'code-1'],
            ['id' => 'code-3'],
        ];

        // Acting as the created user
        $response = $this->actingAs($user)
            ->patch(route('codebook-codes.update-order', [
                'project' => $project->id,
                'codebook' => $codebook->id,
            ]), [
                'code_order' => $newCodeOrder,
            ]);

        // Assert the response is successful
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Code order updated successfully',
            'code_order' => $newCodeOrder,
        ]);

        // Refresh the codebook from database
        $codebook->refresh();

        // Assert that other properties were preserved
        $this->assertTrue($codebook->properties['sharedWithPublic']);
        $this->assertFalse($codebook->properties['sharedWithTeams']);

        // Assert that the code order was updated
        $this->assertEquals($newCodeOrder, $codebook->properties['code_order']);
    }

    /**
     * Test updating code order with invalid data.
     *
     * @return void
     */
    public function test_update_code_order_invalid_data()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'properties' => [
                'sharedWithPublic' => true,
                'sharedWithTeams' => false,
                'code_order' => [['id' => 'code-1']],
            ],
        ]);

        // Send invalid code order (string instead of array)
        $response = $this->actingAs($user)
            ->patch(route('codebook-codes.update-order', [
                'project' => $project->id,
                'codebook' => $codebook->id,
            ]), [
                'code_order' => 'invalid-data',
            ]);

        // Assert the response indicates validation failure
        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'Invalid code order format. Expected an array, got string',
        ]);

        // Refresh the codebook from database
        $codebook->refresh();

        // Assert original properties were preserved
        $this->assertEquals([['id' => 'code-1']], $codebook->properties['code_order']);
        $this->assertTrue($codebook->properties['sharedWithPublic']);
        $this->assertFalse($codebook->properties['sharedWithTeams']);
    }
}
