<?php

namespace Tests\Feature;

use App\Providers\RouteServiceProvider;
use GrantHolle\Altcha\Altcha;
use GrantHolle\Altcha\Exceptions\InvalidAlgorithmException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');

            return;
        }

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    /**
     * @throws InvalidAlgorithmException
     */
    public function test_new_users_can_register(): void
    {

        // Generate a valid altcha challenge
        $altchaService = app(Altcha::class);
        $challenge = $altchaService->createChallenge();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'altcha' => $challenge['signature'],  // Use the valid altcha challenge
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }
}
