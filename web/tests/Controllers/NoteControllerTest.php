<?php

namespace Tests\Controllers;

use App\Models\Note;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteControllerTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------------
    // index
    // ---------------------------------------------------------------------------

    public function test_index_returns_own_notes(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $note = Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'visibility' => 0,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('notes.index', $project));

        $response->assertOk()
            ->assertJsonPath('notes.0.id', $note->id);
    }

    public function test_index_returns_visible_notes_from_others(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $owner->id]);
        $note = Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $other->id,
            'visibility' => 1,
        ]);

        $response = $this->actingAs($owner)
            ->getJson(route('notes.index', $project));

        $response->assertOk()
            ->assertJsonPath('notes.0.id', $note->id);
    }

    public function test_index_does_not_return_private_notes_from_others(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $owner->id]);
        Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $other->id,
            'visibility' => 0,
        ]);

        $response = $this->actingAs($owner)
            ->getJson(route('notes.index', $project));

        $response->assertOk()
            ->assertJsonCount(0, 'notes');
    }

    public function test_index_requires_authentication(): void
    {
        $project = Project::factory()->create();

        $this->getJson(route('notes.index', $project))
            ->assertUnauthorized();
    }

    // ---------------------------------------------------------------------------
    // show
    // ---------------------------------------------------------------------------

    public function test_show_returns_own_note(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $note = Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'visibility' => 0,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('notes.show', [$project, $note]));

        $response->assertOk()
            ->assertJsonPath('note.id', $note->id);
    }

    public function test_show_returns_visible_note_from_another_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $owner->id]);
        $note = Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $other->id,
            'visibility' => 1,
        ]);

        $response = $this->actingAs($owner)
            ->getJson(route('notes.show', [$project, $note]));

        $response->assertOk()
            ->assertJsonPath('note.id', $note->id);
    }

    public function test_show_returns_404_when_note_belongs_to_different_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $otherProject = Project::factory()->create(['creating_user_id' => $user->id]);
        $note = Note::factory()->create([
            'project_id' => $otherProject->id,
            'creating_user_id' => $user->id,
            'visibility' => 1,
        ]);

        $this->actingAs($user)
            ->getJson(route('notes.show', [$project, $note]))
            ->assertNotFound();
    }

    public function test_show_returns_403_for_private_note_from_another_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $owner->id]);
        $note = Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $other->id,
            'visibility' => 0,
        ]);

        // NotePolicy::view: owner is a project member but not the note creator, and visibility = 0 → 403.
        $this->actingAs($owner)
            ->getJson(route('notes.show', [$project, $note]))
            ->assertForbidden();
    }

    public function test_show_returns_403_for_non_project_member(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $owner->id]);
        $note = Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $owner->id,
            'visibility' => 1,
        ]);

        $this->actingAs($outsider)
            ->getJson(route('notes.show', [$project, $note]))
            ->assertForbidden();
    }

    // ---------------------------------------------------------------------------
    // store
    // ---------------------------------------------------------------------------

    public function test_store_creates_note_successfully(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson(route('notes.store', $project), [
                'content' => 'A test note.',
                'target' => (string) $project->id,
                'type' => 'project',
                'scope' => Note::SCOPE_PROJECT,
                'visibility' => 1,
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'note' => ['id', 'content']]);

        $this->assertDatabaseHas('notes', [
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
            'content' => 'A test note.',
        ]);
    }

    public function test_store_requires_content(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson(route('notes.store', $project), [
                'target' => (string) $project->id,
                'type' => 'project',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');
    }

    public function test_store_requires_valid_type(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson(route('notes.store', $project), [
                'content' => 'Some content',
                'target' => (string) $project->id,
                'type' => 'invalid_type',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_store_requires_authentication(): void
    {
        $project = Project::factory()->create();

        $this->postJson(route('notes.store', $project), [
            'content' => 'A note',
            'target' => (string) $project->id,
            'type' => 'project',
        ])->assertUnauthorized();
    }

    // ---------------------------------------------------------------------------
    // update
    // ---------------------------------------------------------------------------

    public function test_update_updates_own_note(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $note = Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('notes.update', [$project, $note]), [
                'content' => 'Updated content',
            ]);

        $response->assertOk()
            ->assertJsonPath('note.content', 'Updated content');

        $this->assertDatabaseHas('notes', ['id' => $note->id, 'content' => 'Updated content']);
    }

    public function test_update_returns_404_when_note_belongs_to_different_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $otherProject = Project::factory()->create(['creating_user_id' => $user->id]);
        $note = Note::factory()->create([
            'project_id' => $otherProject->id,
            'creating_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->patchJson(route('notes.update', [$project, $note]), ['content' => 'X'])
            ->assertNotFound();
    }

    public function test_update_returns_403_when_not_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $owner->id]);
        $note = Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $owner->id,
        ]);

        // NotePolicy::update requires the user to be both a project member AND the note creator.
        $this->actingAs($other)
            ->patchJson(route('notes.update', [$project, $note]), ['content' => 'X'])
            ->assertForbidden();
    }

    // ---------------------------------------------------------------------------
    // destroy
    // ---------------------------------------------------------------------------

    public function test_destroy_deletes_own_note(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $note = Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(route('notes.destroy', [$project, $note]));

        $response->assertOk()
            ->assertJsonPath('message', 'Note deleted successfully.');

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_destroy_returns_404_when_note_belongs_to_different_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $otherProject = Project::factory()->create(['creating_user_id' => $user->id]);
        $note = Note::factory()->create([
            'project_id' => $otherProject->id,
            'creating_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('notes.destroy', [$project, $note]))
            ->assertNotFound();
    }

    public function test_destroy_returns_403_when_not_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $owner->id]);
        $note = Note::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $owner->id,
        ]);

        $this->actingAs($other)
            ->deleteJson(route('notes.destroy', [$project, $note]))
            ->assertForbidden();
    }
}
