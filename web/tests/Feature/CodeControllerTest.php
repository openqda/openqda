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
}
