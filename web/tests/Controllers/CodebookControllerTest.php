<?php

namespace Tests\Controllers;

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


    //New test scenarios added
    /**
     * Test updating codebook name and description.
     *
     * @return void
     */
    public function test_update_codebook_successfully()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'name' => 'Old Name',
            'description' => 'Old Description',
            'properties' => [
                'sharedWithPublic' => false,
                'sharedWithTeams' => false,
            ],
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('codebook.update', [$project->id, $codebook->id]), [
                'name' => 'Updated Name',
                'description' => 'Updated Description',
                'sharedWithPublic' => true,
                'sharedWithTeams' => true,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Codebook updated successfully',
            'codebook' => [
                'name' => 'Updated Name',
                'description' => 'Updated Description',
                'properties' => [
                    'sharedWithPublic' => true,
                    'sharedWithTeams' => true,
                ],
            ],
        ]);

        $this->assertDatabaseHas('codebooks', [
            'id' => $codebook->id,
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]);
    }

    /**
     * Test updating codebook without code_order should preserves existing order.
     *
     * @return void
     */
    public function test_update_codebook_preserves_existing_code_order()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $existingCodeOrder = [['id' => 'code-1'], ['id' => 'code-2']];
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'properties' => [
                'sharedWithPublic' => false,
                'sharedWithTeams' => false,
                'code_order' => $existingCodeOrder,
            ],
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('codebook.update', [$project->id, $codebook->id]), [
                'name' => 'Updated Name',
                'description' => 'Updated Description',
                'sharedWithPublic' => true,
                'sharedWithTeams' => false,
            ]);

        $response->assertStatus(200);
        $codebook->refresh();
        $this->assertEquals($existingCodeOrder, $codebook->properties['code_order']);
    }

    /**
     * Test updating codebook with null properties.
     *
     * @return void
     */
    public function test_update_codebook_with_null_properties()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'properties' => null,
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('codebook.update', [$project->id, $codebook->id]), [
                'name' => 'Updated Name',
                'description' => 'Updated Description',
                'sharedWithPublic' => true,
                'sharedWithTeams' => false,
            ]);

        $response->assertStatus(200);
        $codebook->refresh();
        $this->assertTrue($codebook->properties['sharedWithPublic']);
        $this->assertFalse($codebook->properties['sharedWithTeams']);
    }

    /**
     * Test updating codebook with defaults when sharing options not provided.
     *
     * @return void
     */
    public function test_update_codebook_with_default_sharing_options()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('codebook.update', [$project->id, $codebook->id]), [
                'name' => 'Updated Name',
                'description' => 'Updated Description',
            ]);

        $response->assertStatus(200);
        $codebook->refresh();
        $this->assertFalse($codebook->properties['sharedWithPublic']);
        $this->assertFalse($codebook->properties['sharedWithTeams']);
    }

    /**
     * Test get public codebooks with default pagination.
     *
     * @return void
     */
    public function test_get_public_codebooks_default_pagination()
    {
        $user = User::factory()->create();

        // Create some public codebooks
        Codebook::factory()->count(15)->create([
            'creating_user_id' => $user->id,
            'properties' => ['sharedWithPublic' => true],
        ]);

        // Create some private codebooks
        Codebook::factory()->count(5)->create([
            'creating_user_id' => $user->id,
            'properties' => ['sharedWithPublic' => false],
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('api.codebooks.public'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'current_page',
            'per_page',
            'total',
        ]);
        $this->assertEquals(10, count($response->json('data'))); 
        $this->assertEquals(15, $response->json('total'));
    }

    /**
     * Test get public codebooks with custom per_page.
     *
     * @return void
     */
    public function test_get_public_codebooks_custom_per_page()
    {
        $user = User::factory()->create();

        Codebook::factory()->count(25)->create([
            'creating_user_id' => $user->id,
            'properties' => ['sharedWithPublic' => true],
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('api.codebooks.public', ['per_page' => 15]));

        $response->assertStatus(200);
        $this->assertEquals(15, count($response->json('data')));
    }

    /**
     * Test get public codebooks with invalid per_page falls back to default.
     *
     * @return void
     */
    public function test_get_public_codebooks_invalid_per_page()
    {
        $user = User::factory()->create();

        Codebook::factory()->count(25)->create([
            'creating_user_id' => $user->id,
            'properties' => ['sharedWithPublic' => true],
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('api.codebooks.public', ['per_page' => 100])); // Invalid value

        $response->assertStatus(200);
        $this->assertEquals(10, count($response->json('data'))); 
    }

    /**
     * Test get public codebooks includes codes count and creator info.
     *
     * @return void
     */
    public function test_get_public_codebooks_includes_metadata()
    {
        $user = User::factory()->create();
        $codebook = Codebook::factory()->create([
            'creating_user_id' => $user->id,
            'properties' => ['sharedWithPublic' => true],
        ]);

        // Create some codes for the codebook
        \App\Models\Code::factory()->count(3)->create([
            'codebook_id' => $codebook->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('api.codebooks.public'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'properties',
                    'codes_count',
                    'project_id',
                    'creating_user_id',
                    'creatingUser',
                    'creatingUserEmail',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
        $this->assertEquals(3, $response->json('data.0.codes_count'));
    }

    /**
     * Test search public codebooks by name.
     *
     * @return void
     */
    public function test_search_public_codebooks_by_name()
    {
        $user = User::factory()->create();

        Codebook::factory()->create([
            'name' => 'Interview Codebook',
            'creating_user_id' => $user->id,
            'properties' => ['sharedWithPublic' => true],
        ]);

        Codebook::factory()->create([
            'name' => 'Survey Codebook',
            'creating_user_id' => $user->id,
            'properties' => ['sharedWithPublic' => true],
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('api.codebooks.search', ['q' => 'Interview']));

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('Interview Codebook', $response->json('data.0.name'));
    }

    /**
     * Test search public codebooks by user email.
     *
     * @return void
     */
    public function test_search_public_codebooks_by_user_email()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Codebook::factory()->create([
            'creating_user_id' => $user1->id,
            'properties' => ['sharedWithPublic' => true],
        ]);

        Codebook::factory()->create([
            'creating_user_id' => $user2->id,
            'properties' => ['sharedWithPublic' => true],
        ]);

        $response = $this->actingAs($user1)
            ->getJson(route('api.codebooks.search', ['q' => $user1->email]));

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals($user1->email, $response->json('data.0.creatingUserEmail'));
    }

    /**
     * Test search public codebooks by user name.
     *
     * @return void
     */
    public function test_search_public_codebooks_by_user_name()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Codebook::factory()->create([
            'creating_user_id' => $user1->id,
            'properties' => ['sharedWithPublic' => true],
        ]);

        Codebook::factory()->create([
            'creating_user_id' => $user2->id,
            'properties' => ['sharedWithPublic' => true],
        ]);

        $response = $this->actingAs($user1)
            ->getJson(route('api.codebooks.search', ['q' => $user1->name]));

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    /**
     * Test search with query less than 3 characters returns empty.
     *
     * @return void
     */
    public function test_search_public_codebooks_short_query()
    {
        $user = User::factory()->create();

        Codebook::factory()->count(5)->create([
            'creating_user_id' => $user->id,
            'properties' => ['sharedWithPublic' => true],
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('api.codebooks.search', ['q' => 'ab']));

        $response->assertStatus(200);
        $this->assertEquals(0, count($response->json('data')));
    }

    /**
     * Test search limits results to 20.
     *
     * @return void
     */
    public function test_search_public_codebooks_limits_results()
    {
        $user = User::factory()->create();

        // Create 25 codebooks with similar names
        Codebook::factory()->count(25)->create([
            'name' => 'Test Codebook',
            'creating_user_id' => $user->id,
            'properties' => ['sharedWithPublic' => true],
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('api.codebooks.search', ['q' => 'Test']));

        $response->assertStatus(200);
        $this->assertEquals(20, count($response->json('data'))); // Limited to 20
    }

    /**
     * Test search does not include private codebooks.
     *
     * @return void
     */
    public function test_search_public_codebooks_excludes_private()
    {
        $user = User::factory()->create();

        Codebook::factory()->create([
            'name' => 'Public Interview Codebook',
            'creating_user_id' => $user->id,
            'properties' => ['sharedWithPublic' => true],
        ]);

        Codebook::factory()->create([
            'name' => 'Private Interview Codebook',
            'creating_user_id' => $user->id,
            'properties' => ['sharedWithPublic' => false],
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('api.codebooks.search', ['q' => 'Interview']));

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('Public Interview Codebook', $response->json('data.0.name'));
    }

    /**
     * Test creating codebook with import from existing codebook.
     *
     * @return void
     */
    public function test_store_codebook_with_import()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        // Create original codebook with codes
        $originalCodebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
        ]);

        $code1 = \App\Models\Code::factory()->create([
            'codebook_id' => $originalCodebook->id,
            'name' => 'Parent Code',
            'parent_id' => null,
        ]);

        $code2 = \App\Models\Code::factory()->create([
            'codebook_id' => $originalCodebook->id,
            'name' => 'Child Code',
            'parent_id' => $code1->id,
        ]);

        // Import the codebook
        $response = $this->actingAs($user)
            ->postJson(route('codebook.store', $project->id), [
                'name' => 'Imported Codebook',
                'description' => 'This is an imported codebook',
                'import' => true,
                'id' => $originalCodebook->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Codebook created successfully']);

        // Verify the new codebook was created
        $this->assertDatabaseHas('codebooks', [
            'name' => 'Imported Codebook',
            'description' => 'This is an imported codebook',
        ]);

        // Get the newly created codebook
        $newCodebook = Codebook::where('name', 'Imported Codebook')->first();

        // Verify codes were imported
        $this->assertEquals(2, $newCodebook->codes()->count());

        // Verify parent-child relationship was preserved
        $importedParent = $newCodebook->codes()->where('name', 'Parent Code')->first();
        $importedChild = $newCodebook->codes()->where('name', 'Child Code')->first();

        $this->assertNull($importedParent->parent_id);
        $this->assertEquals($importedParent->id, $importedChild->parent_id);
    }

    /**
     * Test creating codebook with import of complex hierarchy.
     *
     * @return void
     */
    public function test_store_codebook_with_import_complex_hierarchy()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $originalCodebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
        ]);

        // Create a complex hierarchy
        $parent = \App\Models\Code::factory()->create([
            'codebook_id' => $originalCodebook->id,
            'name' => 'Level 1',
            'parent_id' => null,
        ]);

        $child1 = \App\Models\Code::factory()->create([
            'codebook_id' => $originalCodebook->id,
            'name' => 'Level 2-A',
            'parent_id' => $parent->id,
        ]);

        $child2 = \App\Models\Code::factory()->create([
            'codebook_id' => $originalCodebook->id,
            'name' => 'Level 2-B',
            'parent_id' => $parent->id,
        ]);

        $grandchild = \App\Models\Code::factory()->create([
            'codebook_id' => $originalCodebook->id,
            'name' => 'Level 3',
            'parent_id' => $child1->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('codebook.store', $project->id), [
                'name' => 'Imported Complex Codebook',
                'description' => 'Complex hierarchy import',
                'import' => true,
                'id' => $originalCodebook->id,
            ]);

        $response->assertStatus(200);

        $newCodebook = Codebook::where('name', 'Imported Complex Codebook')->first();
        $this->assertEquals(4, $newCodebook->codes()->count());

        // Verify hierarchy
        $importedParent = $newCodebook->codes()->where('name', 'Level 1')->first();
        $importedChild1 = $newCodebook->codes()->where('name', 'Level 2-A')->first();
        $importedChild2 = $newCodebook->codes()->where('name', 'Level 2-B')->first();
        $importedGrandchild = $newCodebook->codes()->where('name', 'Level 3')->first();

        $this->assertNull($importedParent->parent_id);
        $this->assertEquals($importedParent->id, $importedChild1->parent_id);
        $this->assertEquals($importedParent->id, $importedChild2->parent_id);
        $this->assertEquals($importedChild1->id, $importedGrandchild->parent_id);
    }

    /**
     * Test updating codebook without code_order when no existing code_order.
     *
     * @return void
     */
    public function test_update_codebook_without_code_order_initializes_empty_array()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        
        // Create codebook without code_order in properties
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'properties' => [
                'sharedWithPublic' => false,
                'sharedWithTeams' => false,
                // No code_order set
            ],
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('codebook.update', [$project->id, $codebook->id]), [
                'name' => 'Updated Name',
                'description' => 'Updated Description',
                'sharedWithPublic' => true,
            ]);

        $response->assertStatus(200);
        $codebook->refresh();
        
        // Should initialize code_order to empty array
        $this->assertArrayHasKey('code_order', $codebook->properties);
        $this->assertEquals([], $codebook->properties['code_order']);
    }
}
