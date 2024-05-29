<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Features;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_accounts_can_be_deleted(): void
    {
        if (! Features::hasAccountDeletionFeatures()) {
            $this->markTestSkipped('Account deletion is not enabled.');
            return;
        }

        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $response = $this->delete('/user', [
            'password' => 'password',
            '_token' => csrf_token(),
        ]);

        // Follow the redirect
        $response->assertStatus(302);
        $response->assertRedirect('/');

        $this->followRedirects($response);

        $this->assertNull($user->fresh());
    }


}
