<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\UserGlobalPreference;
use App\Models\UserProjectPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_preferences(): void
    {
        $project = Project::factory()->create();

        $response = $this->put(route('preferences.update.project', $project), [
            'theme' => 'dark',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_update_global_preferences(): void
    {
        $response = $this->put(route('preferences.update.global'), [
            'theme' => 'dark',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_create_preferences(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->put(route('preferences.update.project', $project), [
            'sources' => ['showLineNumbers' => true],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('user_project_preferences', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_user_can_create_global_preferences(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('preferences.update.global'), [
            'theme' => 'dark',
            'projects' => [
                'sort' => ['by' => 'name', 'dir' => 'asc'],
            ],
        ]);

        $response->assertRedirect();

        $prefs = UserGlobalPreference::first();

        $this->assertSame($user->id, $prefs->user_id);
        $this->assertSame('dark', $prefs->theme);
        $this->assertEquals([
            'sort' => ['by' => 'name', 'dir' => 'asc'],
        ], $prefs->projects);
    }

    public function test_zoom_preferences_are_created_if_not_existing(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)->put(route('preferences.update.project', $project), [
            'zoom' => [
                'coding' => ['viewer' => 2],
            ],
        ]);

        $this->assertDatabaseHas('user_project_preferences', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        $prefs = UserProjectPreference::first();

        $this->assertEquals([
            'coding' => ['viewer' => 2],
        ], $prefs->zoom);
    }

    public function test_zoom_preferences_are_merged(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $prefs = UserProjectPreference::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'sources' => ['showLineNumbers' => true],
            'zoom' => [
                'coding' => ['viewer' => 1, 'editor' => 2],
                'preparation' => ['viewer' => 3],
            ],
        ]);

        $response = $this->actingAs($user)->put(route('preferences.update.project', $project), [
            'zoom' => [
                'coding' => ['viewer' => 5],
            ],
        ]);

        $response->assertRedirect();

        $prefs->refresh();

        $this->assertSame(['showLineNumbers' => true], $prefs->sources);
        $this->assertEquals([
            'coding' => ['viewer' => 5, 'editor' => 2],
            'preparation' => ['viewer' => 3],
        ], $prefs->zoom);
    }

    public function test_analysis_preferences_are_merged(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $prefs = UserProjectPreference::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'analysis' => [
                'filters' => ['a' => true],
                'visibility' => ['x' => true],
            ],
        ]);

        $this->actingAs($user)->put(route('preferences.update.project', $project), [
            'analysis' => [
                'filters' => ['b' => true],
            ],
        ]);

        $prefs->refresh();

        $this->assertEquals([
            'filters' => ['a' => true, 'b' => true],
            'visibility' => ['x' => true],
        ], $prefs->analysis);
    }

    public function test_preferences_are_user_specific(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user1)->put(route('preferences.update.project', $project), [
            'sources' => ['showLineNumbers' => true],
        ]);

        $this->actingAs($user2)->put(route('preferences.update.project', $project), [
            'sources' => ['showLineNumbers' => false],
        ]);

        $this->assertDatabaseHas('user_project_preferences', [
            'user_id' => $user1->id,
            'project_id' => $project->id,
        ]);

        $this->assertDatabaseHas('user_project_preferences', [
            'user_id' => $user2->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_preferences_are_project_specific(): void
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        $this->actingAs($user)->put(route('preferences.update.project', $project1), [
            'sources' => ['showLineNumbers' => true],
        ]);

        $this->actingAs($user)->put(route('preferences.update.project', $project2), [
            'sources' => ['showLineNumbers' => false],
        ]);

        $this->assertDatabaseHas('user_project_preferences', [
            'user_id' => $user->id,
            'project_id' => $project1->id,
        ]);

        $this->assertDatabaseHas('user_project_preferences', [
            'user_id' => $user->id,
            'project_id' => $project2->id,
        ]);
    }

    public function test_updating_preferences_does_not_create_duplicate_rows(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)->put(route('preferences.update.project', $project), [
            'sources' => ['showLineNumbers' => true],
        ]);

        $this->actingAs($user)->put(route('preferences.update.project', $project), [
            'sources' => ['showLineNumbers' => false],
        ]);

        $this->assertEquals(
            1,
            UserProjectPreference::where('user_id', $user->id)
                ->where('project_id', $project->id)
                ->count()
        );
    }

    public function test_codebook_preferences_are_merged(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $prefs = UserProjectPreference::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'codebooks' => [
                '1' => [
                    'visibility' => [
                        'code-a' => true,
                        'code-b' => false,
                    ],
                ],
            ],
        ]);

        $this->actingAs($user)->put(route('preferences.update.project', $project), [
            'codebooks' => [
                '1' => [
                    'visibility' => [
                        'code-b' => true,
                    ],
                ],
            ],
        ]);

        $prefs->refresh();

        $this->assertEquals([
            '1' => [
                'visibility' => [
                    'code-a' => true,
                    'code-b' => true,
                ],
            ],
        ], $prefs->codebooks);
    }

    public function test_updating_codebooks_does_not_modify_zoom(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $prefs = UserProjectPreference::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'zoom' => [
                'coding' => ['viewer' => 3],
            ],
            'codebooks' => [],
        ]);

        $this->actingAs($user)->put(route('preferences.update.project', $project), [
            'codebooks' => [
                '1' => ['visibility' => ['x' => true]],
            ],
        ]);

        $prefs->refresh();

        $this->assertEquals([
            'coding' => ['viewer' => 3],
        ], $prefs->zoom);
    }

    public function test_sources_are_replaced_not_merged(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $prefs = UserProjectPreference::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'sources' => [
                'sort' => [
                    ['by' => 'name', 'dir' => 'asc'],
                ],
            ],
        ]);

        $this->actingAs($user)->put(route('preferences.update.project', $project), [
            'sources' => [
                'sort' => [
                    ['by' => 'date', 'dir' => 'desc'],
                ],
            ],
        ]);

        $prefs->refresh();

        $this->assertEquals([
            'sort' => [
                ['by' => 'date', 'dir' => 'desc'],
            ],
        ], $prefs->sources);
    }

    public function test_updating_theme_does_not_modify_projects(): void
    {
        $user = User::factory()->create();
        $prefs = UserGlobalPreference::create([
            'user_id' => $user->id,
            'theme' => 'light',
            'projects' => [
                'sort' => ['by' => 'name', 'dir' => 'asc'],
            ],
        ]);

        $this->actingAs($user)->put(route('preferences.update.global'), [
            'theme' => 'dark',
        ]);

        $prefs->refresh();

        $this->assertSame('dark', $prefs->theme);
        $this->assertEquals([
            'sort' => ['by' => 'name', 'dir' => 'asc'],
        ], $prefs->projects);
    }

    public function test_global_projects_are_replaced_not_merged(): void
    {
        $user = User::factory()->create();

        $prefs = UserGlobalPreference::create([
            'user_id' => $user->id,
            'projects' => [
                'sort' => ['by' => 'name', 'dir' => 'asc'],
                'view' => 'grid',
            ],
        ]);

        $this->actingAs($user)->put(route('preferences.update.global'), [
            'projects' => [
                'sort' => ['by' => 'date', 'dir' => 'desc'],
            ],
        ]);

        $prefs->refresh();

        $this->assertEquals([
            'sort' => ['by' => 'date', 'dir' => 'desc'],
        ], $prefs->projects);
    }

    public function test_updating_zoom_and_codebooks_does_not_create_duplicate_rows(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)->put(route('preferences.update.project', $project), [
            'zoom' => ['coding' => ['viewer' => 1]],
        ]);

        $this->actingAs($user)->put(route('preferences.update.project', $project), [
            'codebooks' => ['1' => ['visibility' => ['a' => true]]],
        ]);

        $this->assertEquals(
            1,
            UserProjectPreference::where('user_id', $user->id)
                ->where('project_id', $project->id)
                ->count()
        );
    }
}
