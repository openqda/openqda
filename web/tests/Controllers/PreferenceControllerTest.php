<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_preferences(): void
    {
        $project = Project::factory()->create();

        $response = $this->put(route('projects.preferences.update', $project), [
            'theme' => 'dark',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_create_preferences(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->put(route('projects.preferences.update', $project), [
            'theme' => 'dark',
            'sources' => ['showLineNumbers' => true],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'theme' => 'dark',
        ]);
    }

    public function test_zoom_preferences_are_created_if_not_existing(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)->put(route('projects.preferences.update', $project), [
            'zoom' => [
                'coding' => ['viewer' => 2],
            ],
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        $prefs = UserPreference::first();

        $this->assertEquals([
            'coding' => ['viewer' => 2],
        ], $prefs->zoom);
    }

    public function test_zoom_preferences_are_merged(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $prefs = UserPreference::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'theme' => 'light',
            'sources' => ['showLineNumbers' => true],
            'zoom' => [
                'coding' => ['viewer' => 1, 'editor' => 2],
                'preparation' => ['viewer' => 3],
            ],
        ]);

        $response = $this->actingAs($user)->put(route('projects.preferences.update', $project), [
            'theme' => 'dark',
            'zoom' => [
                'coding' => ['viewer' => 5],
            ],
        ]);

        $response->assertRedirect();

        $prefs->refresh();

        $this->assertSame('dark', $prefs->theme);
        $this->assertSame(['showLineNumbers' => true], $prefs->sources);
        $this->assertEquals([
            'coding' => ['viewer' => 5, 'editor' => 2],
            'preparation' => ['viewer' => 3],
        ], $prefs->zoom);
    }

    public function test_preferences_are_user_specific(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user1)->put(route('projects.preferences.update', $project), [
            'theme' => 'dark',
        ]);

        $this->actingAs($user2)->put(route('projects.preferences.update', $project), [
            'theme' => 'light',
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user1->id,
            'project_id' => $project->id,
            'theme' => 'dark',
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user2->id,
            'project_id' => $project->id,
            'theme' => 'light',
        ]);
    }

    public function test_preferences_are_project_specific(): void
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        $this->actingAs($user)->put(route('projects.preferences.update', $project1), [
            'theme' => 'dark',
        ]);

        $this->actingAs($user)->put(route('projects.preferences.update', $project2), [
            'theme' => 'light',
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'project_id' => $project1->id,
            'theme' => 'dark',
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'project_id' => $project2->id,
            'theme' => 'light',
        ]);
    }

    public function test_updating_preferences_does_not_create_duplicate_rows(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)->put(route('projects.preferences.update', $project), [
            'theme' => 'dark',
        ]);

        $this->actingAs($user)->put(route('projects.preferences.update', $project), [
            'theme' => 'light',
        ]);

        $this->assertEquals(
            1,
            UserPreference::where('user_id', $user->id)
                ->where('project_id', $project->id)
                ->count()
        );
    }

    public function test_codebook_preferences_are_merged(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $prefs = UserPreference::create([
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

        $this->actingAs($user)->put(route('projects.preferences.update', $project), [
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

        $prefs = UserPreference::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'zoom' => [
                'coding' => ['viewer' => 3],
            ],
            'codebooks' => [],
        ]);

        $this->actingAs($user)->put(route('projects.preferences.update', $project), [
            'codebooks' => [
                '1' => ['visibility' => ['x' => true]],
            ],
        ]);

        $prefs->refresh();

        $this->assertEquals([
            'coding' => ['viewer' => 3],
        ], $prefs->zoom);
    }

    public function test_updating_theme_does_not_modify_zoom_or_codebooks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $prefs = UserPreference::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'theme' => 'light',
            'zoom' => [
                'coding' => ['viewer' => 2],
            ],
            'codebooks' => [
                '1' => ['visibility' => ['x' => true]],
            ],
        ]);

        $this->actingAs($user)->put(route('projects.preferences.update', $project), [
            'theme' => 'dark',
        ]);

        $prefs->refresh();

        $this->assertSame('dark', $prefs->theme);
        $this->assertEquals([
            'coding' => ['viewer' => 2],
        ], $prefs->zoom);
        $this->assertEquals([
            '1' => ['visibility' => ['x' => true]],
        ], $prefs->codebooks);
    }

    public function test_updating_zoom_and_codebooks_does_not_create_duplicate_rows(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)->put(route('projects.preferences.update', $project), [
            'zoom' => ['coding' => ['viewer' => 1]],
        ]);

        $this->actingAs($user)->put(route('projects.preferences.update', $project), [
            'codebooks' => ['1' => ['visibility' => ['a' => true]]],
        ]);

        $this->assertEquals(
            1,
            UserPreference::where('user_id', $user->id)
                ->where('project_id', $project->id)
                ->count()
        );
    }
}
