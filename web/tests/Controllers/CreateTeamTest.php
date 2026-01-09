<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_teams_can_be_created(): void
    {
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $response = $this->post('/teams', [
            'name' => 'Test Team',
            'personal_team' => false,
            'projectId' => $project->id,
        ]);

        $this->assertCount(2, $user->fresh()->ownedTeams);
        $this->assertEquals('Test Team', $user->fresh()->ownedTeams()->latest('id')->first()->name);
    }

    public function test_newly_created_team_becomes_current_team(): void
    {
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $this->post('/teams', [
            'name' => 'Second Team',
            'personal_team' => false,
            'projectId' => $project->id,
        ]);

        $user = $user->fresh();
        $newTeam = $user->ownedTeams()->latest('id')->first();

        $this->assertTrue($user->isCurrentTeam($newTeam));
    }

    public function test_guest_cannot_create_team(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $response = $this->post('/teams', [
            'name' => 'Guest Team',
            'personal_team' => false,
            'projectId' => $project->id,
        ]);

        $response->assertRedirect('/login');

        $this->assertDatabaseCount('teams', 0);
    }
}
