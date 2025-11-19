<?php

namespace Tests\Unit\Services;

use App\Mail\ResearchConfirmation;
use App\Models\User;
use App\Services\ResearchConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResearchConsentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ResearchConsentService $service;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ResearchConsentService::class);
        $this->user = User::factory()->create();
    }

    public function test_send_research_confirmation()
    {
        Mail::fake();

        $this->service->sendResearchConfirmation($this->user);

        $this->assertNotNull($this->user->fresh()->research_token);
        $this->assertNotNull($this->user->fresh()->research_requested);

        Mail::assertSent(ResearchConfirmation::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    public function test_confirm_research_with_valid_token()
    {
        $token = hash('sha256', uniqid($this->user->id.microtime(), true));
        $this->user->update(['research_token' => $token]);

        $this->service->confirmResearch($this->user, $token);

        $this->assertNotNull($this->user->fresh()->research_consent);
        $this->assertNull($this->user->fresh()->research_token);
        $this->assertNull($this->user->fresh()->research_requested);
    }

    public function test_confirm_research_with_invalid_token()
    {
        $this->user->update(['research_token' => 'valid-token']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid token:invalid-token');

        $this->service->confirmResearch($this->user, 'invalid-token');
    }

    public function test_withdraw_research()
    {
        $this->user->update(['research_consent' => now()]);

        $this->service->withdrawResearch($this->user);

        $this->assertNull($this->user->fresh()->research_consent);
        $this->assertNull($this->user->fresh()->research_token);
        $this->assertNull($this->user->fresh()->research_requested);
    }
}
