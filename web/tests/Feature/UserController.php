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
        $response->assertJson(['message' => 'Invalid token:invalid-token']);
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

    public function test_consent_legal_with_research()
    {
        $this->mock(ResearchConsentService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendResearchConfirmation')->once()->with($this->user);
        });

        $response = $this->postJson('/user/consent', [
            'research' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Consent updated', 'success' => true]);
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
}
