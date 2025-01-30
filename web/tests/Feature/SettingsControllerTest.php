<?php

namespace Tests\Feature;

use App\Enums\ModelType;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    private Setting $setting;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base user
        $this->user = User::factory()->create();

        // Create team and project
        $this->team = Team::factory()->create();
        $this->project = Project::factory()
            ->for($this->team)
            ->create();

        // Add user to team
        $this->team->users()->attach($this->user->id);

        // Create project setting
        $this->setting = Setting::factory()
            ->forProject()
            ->create([
                'model_id' => $this->project->id,
                'values' => [
                    'display' => [
                        'theme' => 'dark',
                        'sidebar' => 'expanded',
                    ],
                ],
            ]);
    }

    public function test_user_can_view_settings_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('settings.index', [
                'model_type' => ModelType::Project->value,
                'model_id' => (string) $this->project->id,  // Cast to string since that's how it's stored
            ]));

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('data.0.model_type', ModelType::Project->value)
            ->assertJsonPath('data.0.model_id', (string) $this->project->id);  // Cast to string here too
    }

    public function test_user_can_create_setting_for_project_in_team()
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.store'), [
                'model_type' => ModelType::Project->value,
                'model_id' => (string) $this->project->id,
                'values' => [
                    'notifications' => [
                        'email' => true,
                    ],
                ],
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('settings', [
            'model_type' => ModelType::Project->value,
            'model_id' => $this->project->id,
        ]);

        // Assert JSON structure
        $response->assertJsonStructure([
            'id',
            'model_type',
            'model_id',
            'values' => [
                'notifications' => [
                    'email',
                ],
            ],
        ]);
    }

    public function test_user_cannot_create_setting_for_project_not_in_team()
    {
        // Create project in different team
        $otherTeam = Team::factory()->create();
        $otherProject = Project::factory()
            ->for($otherTeam)
            ->create();

        $response = $this->actingAs($this->user)
            ->post(route('settings.store'), [
                'model_type' => ModelType::Project->value,
                'model_id' => $otherProject->id,
                'values' => [
                    'notifications' => [
                        'email' => true,
                    ],
                ],
            ]);

        $response->assertForbidden();
    }

    public function test_user_can_create_own_settings()
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.store'), [
                'model_type' => ModelType::User->value,
                'model_id' => $this->user->id,
                'values' => [
                    'preferences' => [
                        'language' => 'en',
                    ],
                ],
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('settings', [
            'model_type' => ModelType::User->value,
            'model_id' => $this->user->id,
        ]);
    }

    public function test_user_cannot_create_settings_for_other_user()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('settings.store'), [
                'model_type' => ModelType::User->value,
                'model_id' => $otherUser->id,
                'values' => [
                    'preferences' => [
                        'language' => 'en',
                    ],
                ],
            ]);

        $response->assertForbidden();
    }

    public function test_user_can_update_project_setting_in_team()
    {
        $response = $this->actingAs($this->user)
            ->patch(route('settings.update-value', $this->setting), [
                'group' => 'display',
                'key' => 'theme',
                'value' => 'light',
            ]);

        $response->assertOk();

        $this->setting->refresh();
        $this->assertEquals('light', $this->setting->values['display']['theme']);
    }

    public function test_can_delete_setting_with_proper_permissions()
    {
        // Can delete own user setting
        $userSetting = Setting::factory()
            ->forUser()
            ->create([
                'model_id' => $this->user->id,
            ]);

        $response = $this->actingAs($this->user)
            ->delete(route('settings.destroy', $userSetting));

        $response->assertNoContent();

        // Can delete project setting if in team
        $response = $this->actingAs($this->user)
            ->delete(route('settings.destroy', $this->setting));

        $response->assertNoContent();

        // Cannot delete setting for project not in team
        $otherTeam = Team::factory()->create();
        $otherProject = Project::factory()
            ->for($otherTeam)
            ->create();

        $otherSetting = Setting::factory()
            ->forProject()
            ->create([
                'model_id' => $otherProject->id,
            ]);

        $response = $this->actingAs($this->user)
            ->delete(route('settings.destroy', $otherSetting));

        $response->assertForbidden();
    }
}
