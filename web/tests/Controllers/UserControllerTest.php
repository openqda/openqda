<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ResearchConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_request_research_sends_email()
    {
        $response = $this->post('/user/research/request');

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Research token sent via Email.']);
        $this->assertNotNull($this->user->fresh()->research_token);
        $this->assertNotNull($this->user->fresh()->research_requested);
    }

    public function test_confirm_research_with_valid_token()
    {
        $token = hash('sha256', uniqid($this->user->id.microtime(), true));
        $this->user->update(['research_token' => $token]);

        $response = $this->post('/user/research/confirm', ['token' => $token]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Research participation confirmed.']);
        $this->assertNotNull($this->user->fresh()->research_consent);
        $this->assertNull($this->user->fresh()->research_token);
        $this->assertNull($this->user->fresh()->research_requested);
    }

    public function test_confirm_research_with_invalid_token()
    {
        $this->user->update(['research_token' => 'valid-token']);

        $response = $this->post('/user/research/confirm', ['token' => 'invalid-token']);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Invalid token']);
        $this->assertNull($this->user->fresh()->research_consent);
    }

    public function test_withdraw_research_participation()
    {
        $this->user->update(['research_consent' => now()]);

        $response = $this->post('/user/research/withdraw');

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Research participation withdrawn.']);
        $this->assertNull($this->user->fresh()->research_consent);
        $this->assertNull($this->user->fresh()->research_token);
        $this->assertNull($this->user->fresh()->research_requested);
    }

    public function test_consent_legal_with_terms_and_privacy()
    {
        $response = $this->postJson('/user/consent', [
            'terms' => true,
            'privacy' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Consent updated', 'success' => true]);

        $this->assertNotNull($this->user->fresh()->terms_consent);
        $this->assertNotNull($this->user->fresh()->privacy_consent);
    }

    public function test_consent_legal_without_any_consent()
    {
        $response = $this->postJson('/user/consent', []);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'No consent provided']);
    }

    public function test_consent_legal_with_partial_consents()
    {
        $response = $this->postJson('/user/consent', [
            'terms' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Consent updated', 'success' => true]);

        $this->assertNotNull($this->user->fresh()->terms_consent);
        $this->assertNull($this->user->fresh()->privacy_consent);
    }

    public function test_get_owned_teams_returns_teams()
    {
        // Create a team owned by this user
        $team = $this->user->ownedTeams()->create(['name' => 'Test Team', 'personal_team' => false]);

        // Add a user to the team so it has users relation
        $otherUser = User::factory()->create();
        $team->users()->attach($otherUser);

        $response = $this->getJson("/user/{$this->user->id}/owned-teams");

        $response->assertStatus(200);
        $response->assertJsonStructure(['ownTeams']);
        $this->assertCount(1, $response->json('ownTeams'));
    }

    public function test_get_owned_teams_excludes_personal_team()
    {
        // Personal team is automatically created, should be excluded
        $response = $this->getJson("/user/{$this->user->id}/owned-teams");

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('ownTeams'));
    }

    public function test_get_owned_teams_excludes_teams_without_users()
    {
        // Create a team without users
        $this->user->ownedTeams()->create(['name' => 'Empty Team', 'personal_team' => false]);

        $response = $this->getJson("/user/{$this->user->id}/owned-teams");

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('ownTeams'));
    }

    public function test_get_owned_teams_forbidden_for_other_user()
    {
        $otherUser = User::factory()->create();

        $response = $this->getJson("/user/{$otherUser->id}/owned-teams");

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Forbidden']);
    }

    public function test_get_owned_teams_admin_can_access_other_user_teams()
    {
        config(['app.admins' => [$this->user->email]]);
        $otherUser = User::factory()->create();
        $team = $otherUser->ownedTeams()->create(['name' => 'Other Team', 'personal_team' => false]);
        $anotherUser = User::factory()->create();
        $team->users()->attach($anotherUser);

        $response = $this->getJson("/user/{$otherUser->id}/owned-teams");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('ownTeams'));
    }

    public function test_request_research_without_prior_request()
    {
        $response = $this->post('/user/research/request');

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Research token sent via Email.']);
        $this->assertNotNull($this->user->fresh()->research_token);
        $this->assertNotNull($this->user->fresh()->research_requested);
    }

    public function test_confirm_research_clears_token_and_requested_date()
    {
        $token = hash('sha256', uniqid($this->user->id.microtime(), true));
        $this->user->update([
            'research_token' => $token,
            'research_requested' => now(),
        ]);

        $response = $this->post('/user/research/confirm', ['token' => $token]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Research participation confirmed.']);
        $this->assertNotNull($this->user->fresh()->research_consent);
        $this->assertNull($this->user->fresh()->research_token);
        $this->assertNull($this->user->fresh()->research_requested);
    }

    public function test_confirm_research_with_empty_token()
    {
        $response = $this->post('/user/research/confirm', ['token' => '']);

        $response->assertStatus(302);
    }

    public function test_confirm_research_with_no_token_field()
    {
        $response = $this->post('/user/research/confirm', []);

        $response->assertStatus(302);
    }

    public function test_confirm_research_with_url_encoded_token()
    {
        $originalToken = hash('sha256', uniqid($this->user->id.microtime(), true));
        $this->user->update(['research_token' => $originalToken]);
        $encodedToken = urlencode($originalToken);

        $response = $this->post('/user/research/confirm', ['token' => $encodedToken]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Research participation confirmed.']);
        $this->assertNotNull($this->user->fresh()->research_consent);
    }

    public function test_withdraw_research_clears_all_research_data()
    {
        $token = hash('sha256', uniqid($this->user->id.microtime(), true));
        $this->user->update([
            'research_consent' => now(),
            'research_token' => $token,
            'research_requested' => now(),
        ]);

        $response = $this->post('/user/research/withdraw');

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Research participation withdrawn.']);
        $this->assertNull($this->user->fresh()->research_consent);
        $this->assertNull($this->user->fresh()->research_token);
        $this->assertNull($this->user->fresh()->research_requested);
    }

    public function test_withdraw_research_when_not_consented()
    {
        $this->user->update([
            'research_consent' => null,
            'research_token' => null,
            'research_requested' => null,
        ]);

        $response = $this->post('/user/research/withdraw');

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Research participation withdrawn.']);
    }

    public function test_consent_legal_with_all_consents()
    {
        $this->mock(ResearchConsentService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendResearchConfirmation')->once()->with($this->user);
        });

        $response = $this->postJson('/user/consent', [
            'terms' => true,
            'privacy' => true,
            'research' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Consent updated', 'success' => true]);

        $this->assertNotNull($this->user->fresh()->terms_consent);
        $this->assertNotNull($this->user->fresh()->privacy_consent);
    }

    public function test_consent_legal_with_privacy_only()
    {
        $response = $this->postJson('/user/consent', [
            'privacy' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Consent updated', 'success' => true]);

        $this->assertNull($this->user->fresh()->terms_consent);
        $this->assertNotNull($this->user->fresh()->privacy_consent);
    }

    public function test_consent_legal_ignores_false_values()
    {
        // When only false values are sent, they are ignored, and since nothing is true, response succeeds
        $response = $this->postJson('/user/consent', [
            'terms' => false,
            'privacy' => false,
            'research' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Consent updated', 'success' => true]);

        $this->assertNull($this->user->fresh()->terms_consent);
        $this->assertNull($this->user->fresh()->privacy_consent);
    }

    public function test_consent_legal_with_research_only()
    {
        // Research alone fails validation because it requires terms or privacy
        $response = $this->postJson('/user/consent', [
            'research' => true,
        ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'No consent provided']);
    }

    public function test_request_research_after_withdrawal_succeeds()
    {
        $this->post('/user/research/withdraw');

        $response = $this->post('/user/research/request');

        $response->assertStatus(200);
    }

    public function test_confirm_research_creates_audit_entry()
    {
        $token = hash('sha256', uniqid($this->user->id.microtime(), true));
        $this->user->update(['research_token' => $token]);

        $this->post('/user/research/confirm', ['token' => $token]);

        $this->assertDatabaseHas('audits', [
            'user_id' => $this->user->id,
            'event' => 'user.research_consent_confirmed',
        ]);
    }

    public function test_withdraw_research_creates_audit_entry()
    {
        $this->user->update(['research_consent' => now()]);

        $this->post('/user/research/withdraw');

        $this->assertDatabaseHas('audits', [
            'user_id' => $this->user->id,
            'event' => 'user.research_consent_withdrawn',
        ]);
    }

    public function test_consent_legal_terms_creates_audit_entry()
    {
        $this->postJson('/user/consent', [
            'terms' => true,
        ]);

        $this->assertDatabaseHas('audits', [
            'user_id' => $this->user->id,
            'event' => 'user.terms_consent',
        ]);
    }

    public function test_consent_legal_privacy_creates_audit_entry()
    {
        $this->postJson('/user/consent', [
            'privacy' => true,
        ]);

        $this->assertDatabaseHas('audits', [
            'user_id' => $this->user->id,
            'event' => 'user.privacy_consent',
        ]);
    }

    public function test_get_owned_teams_includes_projects_and_users()
    {
        $team = $this->user->ownedTeams()->create(['name' => 'Full Team', 'personal_team' => false]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $team->users()->attach([$user1->id, $user2->id]);

        $response = $this->getJson("/user/{$this->user->id}/owned-teams");

        $response->assertStatus(200);
        $teams = $response->json('ownTeams');
        $this->assertCount(1, $teams);
        $this->assertNotNull($teams[0]['name']);
    }
}
