<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_index_displays_projects()
    {
        Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $response = $this->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ProjectsList')
            ->has('projects', 1)
            ->where('projects.0.isOwner', true)
        );
    }

    public function test_store_creates_project()
    {
        $data = [
            'name' => 'Test Project',
            'description' => 'Project description',
        ];

        $response = $this->post(route('project.store'), $data);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'project' => ['name', 'description', 'created_at', 'id', 'isOwner'],
        ]);

        $this->assertDatabaseHas('projects', ['name' => 'Test Project']);
    }

    public function test_store_fails_validation()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('project.store'), [], [
            'X-Requested-With' => 'XMLHttpRequest',  // AJAX request for JSON response
            'Accept' => 'application/json',           // Forces JSON response
        ]);

        $response->assertStatus(422); // Expect validation error status
        $response->assertJsonValidationErrors(['name']); // Assert specific validation error for 'name'
    }

    public function test_update_project_attributes()
    {
        $project = Project::factory()->create(['creating_user_id' => $this->user->id]);

        $data = [
            'type' => 'description',
            'value' => 'Updated description',
        ];

        $response = $this->post(route('project.update', $project->id), $data);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Project updated successfully']);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'description' => 'Updated description']);
    }

    public function test_destroy_deletes_project()
    {
        $project = Project::factory()->create(['creating_user_id' => $this->user->id]);

        $response = $this->delete(route('project.destroy', $project->id));

        $response->assertStatus(302); // Redirects to the index page
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }
}
