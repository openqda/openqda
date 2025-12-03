<?php

namespace Tests\Feature;

use App\Mail\TeamMemberAddedNotification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Mail\TeamInvitation;
use Tests\TestCase;

class InviteTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_configuration_allows_invitations(): void
    {
        config(['app.env' => 'testing']);
        putenv('TEAM_INVITATION_REQUIRED=true');
        Features::teams(['invitations' => true]);
        $this->assertEqual(true, Features::sendsTeamInvitations());

        putenv('TEAM_INVITATION_REQUIRED=false');
        Features::teams(['invitations' => false]);
        $this->assertEquals(false, Features::sendsTeamInvitations());
    }

    public function test_team_members_can_be_invited_to_team(): void
    {
        config(['app.env' => 'testing']);
        putenv('TEAM_INVITATION_REQUIRED=true');
        Features::teams(['invitations' => true]);

        Mail::fake();

        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $response = $this->post('/teams/'.$user->currentTeam->id.'/members', [
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        Mail::assertSent(TeamInvitation::class);

        // Assert that no notification email is sent
        Mail::assertNotSent(TeamMemberAddedNotification::class);

        $this->assertCount(1, $user->currentTeam->fresh()->teamInvitations);
    }

    public function test_team_member_invitations_can_be_cancelled(): void
    {
        config(['app.env' => 'testing']);
        putenv('TEAM_INVITATION_REQUIRED=true');
        Features::teams(['invitations' => true]);

        Mail::fake();

        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $invitation = $user->currentTeam->teamInvitations()->create([
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        $response = $this->delete('/team-invitations/'.$invitation->id);

        $this->assertCount(0, $user->currentTeam->fresh()->teamInvitations);
    }

    public function test_team_member_added_notification_is_sent_when_invitation_is_not_required(): void
    {
        // Set the environment variable to disable invitations
        config(['app.env' => 'testing']);
        putenv('TEAM_INVITATION_REQUIRED=false');
        Features::teams(['invitations' => false]);
        Mail::fake();

        // Authenticate the user and ensure they have a personal team
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $team = $user->currentTeam;

        // Ensure the user has permission to add team members
        $team->users()->updateExistingPivot($user->id, ['role' => 'owner']);

        // Create a project and associate it with the team
        $project = Project::factory()->create(['team_id' => $team->id]);

        $newMember = User::factory()->create([
            'email' => 'newmember@example.com',
        ]);

        $response = $this->post('/teams/'.$team->id.'/members', [
            'email' => $newMember->email,
            'role' => 'admin',
        ]);

        // Assert that the team invitation is not sent
        Mail::assertNotSent(TeamInvitation::class);

        // Assert that the notification email is sent
        Mail::assertSent(TeamMemberAddedNotification::class, function ($mail) use ($newMember) {
            return $mail->hasTo($newMember->email);
        });
    }
}
