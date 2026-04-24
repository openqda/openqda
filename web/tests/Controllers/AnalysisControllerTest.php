<?php

namespace Tests\Controllers;

use App\Models\Note;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalysisControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_analysis_page_renders_successfully(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('analysis.show', ['project' => $project->id]));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('AnalysisPage')
                ->has('sources')
                ->has('codes')
                ->has('codebooks')
                ->has('notes')
                ->has('project')
            );
    }

    public function test_show_includes_visible_notes_for_all_types(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $note = Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $other->id,
            'type' => 'source',
            'scope' => Note::SCOPE_SOURCE,
            'target' => '1',
            'visibility' => 1,
        ]);

        $response = $this->actingAs($user)
            ->get(route('analysis.show', ['project' => $project->id]));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('AnalysisPage')
                ->has('notes', 1)
                ->where('notes.0.id', $note->id)
            );
    }

    public function test_show_excludes_private_notes_from_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $other->id,
            'type' => 'code',
            'scope' => Note::SCOPE_CODE,
            'target' => '1',
            'visibility' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get(route('analysis.show', ['project' => $project->id]));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('AnalysisPage')
                ->has('notes', 0)
            );
    }

    public function test_show_includes_own_private_notes(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $note = Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'type' => 'selection',
            'scope' => Note::SCOPE_SELECTION,
            'target' => '1',
            'visibility' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get(route('analysis.show', ['project' => $project->id]));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('AnalysisPage')
                ->has('notes', 1)
                ->where('notes.0.id', $note->id)
            );
    }

    public function test_show_excludes_notes_with_non_matching_types(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        // 'case' type is not in the allowed list for analysis page
        Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'type' => 'case',
            'scope' => Note::SCOPE_PROJECT,
            'target' => (string) $project->id,
            'visibility' => 1,
        ]);

        $response = $this->actingAs($user)
            ->get(route('analysis.show', ['project' => $project->id]));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('AnalysisPage')
                ->has('notes', 0)
            );
    }

    public function test_show_requires_authentication(): void
    {
        $project = Project::factory()->create();

        $this->get(route('analysis.show', ['project' => $project->id]))
            ->assertRedirect(route('login'));
    }
}
