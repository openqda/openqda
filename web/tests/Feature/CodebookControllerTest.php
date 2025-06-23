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

    /**
     * Test fetching a public codebook with its codes.
     *
     * @return void
     */
    public function test_get_public_codebook_with_codes()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'properties' => [
                'sharedWithPublic' => true,
                'sharedWithTeams' => false,
            ],
        ]);

        // Create some codes for the codebook
        $codes = \App\Models\Code::factory()->count(3)->create([
            'codebook_id' => $codebook->id,
        ]);

        // Anyone should be able to access a public codebook
        $anotherUser = User::factory()->create();
        $response = $this->actingAs($anotherUser)
            ->getJson("/api/codebooks/{$codebook->id}/codes");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'name',
            'description',
            'properties',
            'codes',
            'codes_count',
            'project_id',
            'creating_user_id',
            'creatingUser',
            'creatingUserEmail',
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonCount(3, 'codes');
        $response->assertJson([
            'codes_count' => 3,
            'properties' => [
                'sharedWithPublic' => true,
            ],
        ]);
    }

    /**
     * Test fetching a private codebook with codes as the creator.
     *
     * @return void
     */
    public function test_get_private_codebook_with_codes_as_creator()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'properties' => [
                'sharedWithPublic' => false,
                'sharedWithTeams' => false,
            ],
        ]);

        // Create some codes for the codebook
        $codes = \App\Models\Code::factory()->count(2)->create([
            'codebook_id' => $codebook->id,
        ]);

        // Creator should be able to access their own private codebook
        $response = $this->actingAs($user)
            ->getJson("/api/codebooks/{$codebook->id}/codes");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'codes');
        $response->assertJson([
            'id' => $codebook->id,
            'codes_count' => 2,
            'properties' => [
                'sharedWithPublic' => false,
            ],
        ]);
    }

    /**
     * Test fetching a private codebook with codes as a project member.
     *
     * @return void
     */
    public function test_get_private_codebook_with_codes_as_project_member()
    {
        $creator = User::factory()->create();
        $projectMember = User::factory()->create();

        // Create a project with team
        $team = \App\Models\Team::factory()->create(['user_id' => $creator->id]);
        $project = Project::factory()->create([
            'creating_user_id' => $creator->id,
            'team_id' => $team->id,
        ]);

        // Add the project member to the team
        $team->users()->attach($projectMember, ['role' => 'editor']);

        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $creator->id,
            'properties' => [
                'sharedWithPublic' => false,
                'sharedWithTeams' => true,
            ],
        ]);

        // Create some codes for the codebook
        $codes = \App\Models\Code::factory()->count(2)->create([
            'codebook_id' => $codebook->id,
        ]);

        // Project member should be able to access the codebook
        $response = $this->actingAs($projectMember)
            ->getJson("/api/codebooks/{$codebook->id}/codes");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'codes');
    }

    /**
     * Test unauthorized access to a private codebook.
     *
     * @return void
     */
    public function test_cannot_access_private_codebook_without_permission()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'properties' => [
                'sharedWithPublic' => false,
                'sharedWithTeams' => false,
            ],
        ]);

        // Another user should not be able to access the private codebook
        $response = $this->actingAs($anotherUser)
            ->getJson("/api/codebooks/{$codebook->id}/codes");

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Unauthorized']);
    }

    /**
     * Test accessing a non-existent codebook.
     *
     * @return void
     */
    public function test_get_non_existent_codebook_returns_404()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/codebooks/non-existent-id/codes');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Codebook not found']);
    }

    /**
     * Test fetching a codebook without codes returns empty array.
     *
     * @return void
     */
    public function test_get_codebook_without_codes()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'properties' => [
                'sharedWithPublic' => true,
                'sharedWithTeams' => false,
            ],
        ]);

        // No codes created for this codebook

        $response = $this->actingAs($user)
            ->getJson("/api/codebooks/{$codebook->id}/codes");

        $response->assertStatus(200);
        $response->assertJson([
            'codes' => [],
            'codes_count' => 0,
        ]);
    }

    /**
     * Test fetching a private codebook without properties array.
     *
     * @return void
     */
    public function test_get_private_codebook_without_properties_as_creator()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'properties' => null, // No properties set
        ]);

        // Create some codes for the codebook
        $codes = \App\Models\Code::factory()->count(2)->create([
            'codebook_id' => $codebook->id,
        ]);

        // Creator should be able to access their own codebook even without properties
        $response = $this->actingAs($user)
            ->getJson("/api/codebooks/{$codebook->id}/codes");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'codes');
        $response->assertJson([
            'id' => $codebook->id,
            'codes_count' => 2,
        ]);
    }
}
