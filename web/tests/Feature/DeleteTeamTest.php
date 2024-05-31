<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_teams_cant_be_deleted(): void
    {
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $user->ownedTeams()->save($team = Team::factory()->make([
            'personal_team' => false,
        ]));

        $team->users()->attach(
            $otherUser = User::factory()->create(), ['role' => 'test-role']
        );

        $response = $this->delete('/teams/'.$team->id);

        $this->assertNotNull($team->fresh());
        $this->assertCount(0, $otherUser->fresh()->teams);
    }

    public function test_empty_teams_can_be_deleted(): void
    {
        self::refreshDatabase();
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    // Create a project associated with the user
        $project = Project::factory()->create([
            'creating_user_id' => $user->id,
        ]);

        $user->ownedTeams()->save($team = Team::factory()->make([
            'personal_team' => false,
        ]));

        $project->team()->associate($team)->save();
        $response = $this->delete('/teams/'.$team->id);

        $this->assertNull($project->fresh()->team);
    }

    public function test_personal_teams_cant_be_deleted(): void
    {
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $response = $this->delete('/teams/'.$user->currentTeam->id);

        $this->assertNotNull($user->currentTeam->fresh());
    }
}
