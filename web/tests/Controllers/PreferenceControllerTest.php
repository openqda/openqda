<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test show preferences creates default preference for authenticated user
     */
    public function test_show_preferences_creates_default_for_authenticated_user(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/preferences');

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'user_id', 'theme', 'created_at', 'updated_at']);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'theme' => 'light',
        ]);
    }

    /**
     * Test show preferences returns existing preference for authenticated user
     */
    public function test_show_preferences_returns_existing_preference(): void
    {
        $this->actingAs($this->user);

        UserPreference::create([
            'user_id' => $this->user->id,
            'theme' => 'dark',
        ]);

        $response = $this->getJson('/preferences');

        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $this->user->id,
                'theme' => 'dark',
            ]);
    }

    /**
     * Test show preferences response has no-cache headers
     */
    public function test_show_preferences_has_no_cache_headers(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/preferences');

        // Check that required cache headers are present (order may vary due to middleware)
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
        $this->assertEquals('0', $response->headers->get('Expires'));
    }

    /**
     * Test show preferences with specific key parameter
     */
    public function test_show_preferences_with_key_parameter(): void
    {
        $this->actingAs($this->user);

        UserPreference::create([
            'user_id' => $this->user->id,
            'theme' => 'dark',
        ]);

        $response = $this->getJson('/preferences/theme');

        $response->assertStatus(200)
            ->assertJson(['user_id' => $this->user->id, 'theme' => 'dark']);
    }

    /**
     * Test update preference with light theme
     */
    public function test_update_preference_to_light(): void
    {
        $this->actingAs($this->user);

        UserPreference::create([
            'user_id' => $this->user->id,
            'theme' => 'dark',
        ]);

        $response = $this->putJson('/preferences', ['theme' => 'light']);

        $response->assertStatus(200)
            ->assertJson(['theme' => 'light']);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'theme' => 'light',
        ]);
    }

    /**
     * Test update preference with dark theme
     */
    public function test_update_preference_to_dark(): void
    {
        $this->actingAs($this->user);

        UserPreference::create([
            'user_id' => $this->user->id,
            'theme' => 'light',
        ]);

        $response = $this->putJson('/preferences', ['theme' => 'dark']);

        $response->assertStatus(200)
            ->assertJson(['theme' => 'dark']);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'theme' => 'dark',
        ]);
    }

    /**
     * Test update preference creates default if not exists
     */
    public function test_update_preference_creates_default_if_not_exists(): void
    {
        $this->actingAs($this->user);

        $response = $this->putJson('/preferences', ['theme' => 'dark']);

        $response->assertStatus(200)
            ->assertJson(['theme' => 'dark']);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'theme' => 'dark',
        ]);
    }

    /**
     * Test update preference with key parameter
     */
    public function test_update_preference_with_key_parameter(): void
    {
        $this->actingAs($this->user);

        UserPreference::create([
            'user_id' => $this->user->id,
            'theme' => 'light',
        ]);

        $response = $this->putJson('/preferences/theme', ['theme' => 'dark']);

        $response->assertStatus(200)
            ->assertJson(['theme' => 'dark']);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'theme' => 'dark',
        ]);
    }

    /**
     * Test update preference validation fails with invalid theme
     */
    public function test_update_preference_validation_fails_with_invalid_theme(): void
    {
        $this->actingAs($this->user);

        $response = $this->putJson('/preferences', ['theme' => 'invalid']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['theme']);
    }

    /**
     * Test update preference validation fails with missing theme
     */
    public function test_update_preference_validation_fails_with_missing_theme(): void
    {
        $this->actingAs($this->user);

        $response = $this->putJson('/preferences', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['theme']);
    }

    /**
     * Test update preference validation fails with empty theme
     */
    public function test_update_preference_validation_fails_with_empty_theme(): void
    {
        $this->actingAs($this->user);

        $response = $this->putJson('/preferences', ['theme' => '']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['theme']);
    }

    /**
     * Test update preference validation fails with null theme
     */
    public function test_update_preference_validation_fails_with_null_theme(): void
    {
        $this->actingAs($this->user);

        $response = $this->putJson('/preferences', ['theme' => null]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['theme']);
    }

    /**
     * Test update preference without authentication
     */
    public function test_update_preference_without_authentication(): void
    {
        $response = $this->putJson('/preferences', ['theme' => 'dark']);

        $response->assertStatus(401);
    }

    /**
     * Test show preferences with non-existent key still returns all preferences
     */
    public function test_show_preferences_returns_all_even_with_key_parameter(): void
    {
        $this->actingAs($this->user);

        UserPreference::create([
            'user_id' => $this->user->id,
            'theme' => 'dark',
        ]);

        $response = $this->getJson('/preferences/nonexistent');

        $response->assertStatus(200);
    }

    /**
     * Test multiple users have separate preferences
     */
    public function test_multiple_users_have_separate_preferences(): void
    {
        $user1 = $this->user;
        $user2 = User::factory()->create();

        UserPreference::create([
            'user_id' => $user1->id,
            'theme' => 'dark',
        ]);

        UserPreference::create([
            'user_id' => $user2->id,
            'theme' => 'light',
        ]);

        $this->actingAs($user1);
        $response1 = $this->getJson('/preferences');
        $response1->assertJson(['theme' => 'dark']);

        $this->actingAs($user2);
        $response2 = $this->getJson('/preferences');
        $response2->assertJson(['theme' => 'light']);
    }

    /**
     * Test update preference updates timestamp
     */
    public function test_update_preference_updates_timestamp(): void
    {
        $this->actingAs($this->user);

        $pref = UserPreference::create([
            'user_id' => $this->user->id,
            'theme' => 'light',
        ]);

        $originalTimestamp = $pref->updated_at;
        sleep(1); // Ensure time difference

        $this->putJson('/preferences', ['theme' => 'dark']);

        $updatedPref = UserPreference::where('user_id', $this->user->id)->first();
        $this->assertGreaterThan($originalTimestamp, $updatedPref->updated_at);
    }
}
