<?php

namespace Tests\Controllers;

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

    public function test_show_displays_project_overview()
    {
        $project = Project::factory()->create(['creating_user_id' => $this->user->id]);

        $response = $this->get(route('project.show', $project->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ProjectOverview')
            ->has('project')
            ->has('projects')
            ->has('userCodebooks')
            ->has('publicCodebooks')
            ->has('availableRoles')
            ->has('availablePermissions')
            ->has('defaultPermissions')
            ->where('hasTeam', false)
        );
    }

    public function test_show_with_codebooks_tab()
    {
        $project = Project::factory()->create(['creating_user_id' => $this->user->id]);

        $response = $this->get(route('project.show', $project->id).'?codebookstab=true');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ProjectOverview')
            ->where('hasCodebooksTab', true)
        );
    }

    public function test_show_includes_sources()
    {
        $project = Project::factory()->create(['creating_user_id' => $this->user->id]);
        \App\Models\Source::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $response = $this->get(route('project.show', $project->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ProjectOverview')
            ->has('project.sources', 1)
        );
    }

    public function test_show_includes_codebooks()
    {
        $project = Project::factory()->create(['creating_user_id' => $this->user->id]);
        \App\Models\Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $response = $this->get(route('project.show', $project->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ProjectOverview')
            ->has('project.codebooks', 1)
        );
    }

    public function test_get_team_data_without_team()
    {
        $project = Project::factory()->create(['creating_user_id' => $this->user->id]);

        $response = $this->get(route('project.team-data', $project->id));

        $response->assertStatus(200);
        $response->assertJson([
            'team' => null,
            'teamOwner' => false,
            'teamMembers' => [],
            'permissions' => [],
            'hasTeam' => false,
        ]);
    }

    public function test_get_team_data_with_team()
    {
        $team = \App\Models\Team::factory()->create(['user_id' => $this->user->id]);
        $project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
            'team_id' => $team->id,
        ]);

        $response = $this->get(route('project.team-data', $project->id));

        $response->assertStatus(200);
        $response->assertJson([
            'teamOwner' => true,
            'hasTeam' => true,
        ]);
        $response->assertJsonStructure([
            'team',
            'teamMembers',
            'permissions',
            'availableRoles',
        ]);
    }

    public function test_get_team_data_with_team_members()
    {
        $team = \App\Models\Team::factory()->create(['user_id' => $this->user->id]);
        $member = User::factory()->create();
        $team->users()->attach($member, ['role' => 'editor']);

        $project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
            'team_id' => $team->id,
        ]);

        $response = $this->get(route('project.team-data', $project->id));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'teamMembers');
    }

    public function test_index_filters_trashed_projects()
    {
        $activeProject = Project::factory()->create(['creating_user_id' => $this->user->id]);
        $trashedProject = Project::factory()->create(['creating_user_id' => $this->user->id]);
        $trashedProject->delete();

        $response = $this->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ProjectsList')
            ->has('projects', 1)
            ->where('projects.0.id', $activeProject->id)
        );
    }

    public function test_index_shows_collaborative_projects()
    {
        $team = \App\Models\Team::factory()->create(['user_id' => $this->user->id]);
        $project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
            'team_id' => $team->id,
        ]);

        $response = $this->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ProjectsList')
            ->where('projects.0.isCollaborative', true)
        );
    }

    public function test_update_project_name()
    {
        $project = Project::factory()->create(['creating_user_id' => $this->user->id]);

        $data = [
            'type' => 'name',
            'value' => 'Updated Name',
        ];

        $response = $this->post(route('project.update', $project->id), $data);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Updated Name']);
    }
}
