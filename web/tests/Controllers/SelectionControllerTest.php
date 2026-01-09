<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\Codebook;
use App\Models\Project;
use App\Models\Selection;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelectionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected Source $source;

    protected Code $code;

    protected Codebook $codebook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);
        $this->source = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);
        $this->codebook = Codebook::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);
        $this->code = Code::factory()->create([
            'codebook_id' => $this->codebook->id,
        ]);
    }

    public function test_store_creates_selection_successfully()
    {
        $response = $this->actingAs($this->user)
            ->post(route('selection.store', [
                'project' => $this->project->id,
                'source' => $this->source->id,
                'code' => $this->code->id,
            ]), [
                'textId' => 'selection-1',
                'start_position' => 0,
                'end_position' => 10,
                'text' => 'test text',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Selection saved successfully!']);
        $response->assertJsonStructure(['selection' => ['id', 'code_id', 'source_id']]);

        $this->assertDatabaseHas('selections', [
            'id' => 'selection-1',
            'code_id' => $this->code->id,
            'source_id' => $this->source->id,
            'creating_user_id' => $this->user->id,
            'project_id' => $this->project->id,
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('selection.store', [
                'project' => $this->project->id,
                'source' => $this->source->id,
                'code' => $this->code->id,
            ]), []);

        $response->assertStatus(422);
    }

    public function test_store_requires_authentication()
    {
        $response = $this->post(route('selection.store', [
            'project' => $this->project->id,
            'source' => $this->source->id,
            'code' => $this->code->id,
        ]), [
            'textId' => 'selection-1',
            'start_position' => 0,
            'end_position' => 10,
            'text' => 'test text',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_store_with_multiple_selections()
    {
        $this->actingAs($this->user)
            ->post(route('selection.store', [
                'project' => $this->project->id,
                'source' => $this->source->id,
                'code' => $this->code->id,
            ]), [
                'textId' => 'selection-1',
                'start_position' => 0,
                'end_position' => 5,
                'text' => 'test',
            ]);

        $response = $this->actingAs($this->user)->post(route('selection.store', [
            'project' => $this->project->id,
            'source' => $this->source->id,
            'code' => $this->code->id,
        ]), [
            'textId' => 'selection-2',
            'start_position' => 10,
            'end_position' => 15,
            'text' => 'more',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('selections', ['id' => 'selection-1']);
        $this->assertDatabaseHas('selections', ['id' => 'selection-2']);
    }

    public function test_change_code_updates_selection_code()
    {
        $selection = Selection::create([
            'id' => 'sel-'.uniqid(),
            'code_id' => $this->code->id,
            'source_id' => $this->source->id,
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'text' => 'test',
            'start_position' => 0,
            'end_position' => 10,
        ]);

        $newCode = Code::factory()->create([
            'codebook_id' => $this->codebook->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('selection.change-code', [
                'project' => $this->project->id,
                'source' => $this->source->id,
                'code' => $this->code->id,
                'selection' => $selection->id,
            ]), [
                'newCodeId' => $newCode->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Code updated successfully']);

        $selection->refresh();
        $this->assertEquals($newCode->id, $selection->code_id);
        $this->assertEquals($this->user->id, $selection->modifying_user_id);
    }

    public function test_change_code_requires_validation()
    {
        $selection = Selection::create([
            'id' => 'sel-'.uniqid(),
            'code_id' => $this->code->id,
            'source_id' => $this->source->id,
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'text' => 'test',
            'start_position' => 0,
            'end_position' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('selection.change-code', [
                'project' => $this->project->id,
                'source' => $this->source->id,
                'code' => $this->code->id,
                'selection' => $selection->id,
            ]), []);

        $response->assertStatus(422);
    }

    public function test_change_code_requires_authentication()
    {
        $selection = Selection::create([
            'id' => 'sel-'.uniqid(),
            'code_id' => $this->code->id,
            'source_id' => $this->source->id,
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'text' => 'test',
            'start_position' => 0,
            'end_position' => 10,
        ]);

        $newCode = Code::factory()->create([
            'codebook_id' => $this->codebook->id,
        ]);

        $response = $this->post(route('selection.change-code', [
            'project' => $this->project->id,
            'source' => $this->source->id,
            'code' => $this->code->id,
            'selection' => $selection->id,
        ]), [
            'newCodeId' => $newCode->id,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_destroy_deletes_selection_successfully()
    {
        $selection = Selection::create([
            'id' => 'sel-'.uniqid(),
            'code_id' => $this->code->id,
            'source_id' => $this->source->id,
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'text' => 'test',
            'start_position' => 0,
            'end_position' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('selection.destroy', [
                'project' => $this->project->id,
                'source' => $this->source->id,
                'code' => $this->code->id,
                'selection' => $selection->id,
            ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Text deleted successfully from code']);

        $this->assertDatabaseMissing('selections', ['id' => $selection->id]);
    }

    public function test_destroy_with_mismatched_source()
    {
        $otherSource = Source::factory()->create([
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $selection = Selection::create([
            'id' => 'sel-'.uniqid(),
            'code_id' => $this->code->id,
            'source_id' => $otherSource->id,
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'text' => 'test',
            'start_position' => 0,
            'end_position' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('selection.destroy', [
                'project' => $this->project->id,
                'source' => $this->source->id,
                'code' => $this->code->id,
                'selection' => $selection->id,
            ]));

        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Selection does not match the given source or code']);

        $this->assertDatabaseHas('selections', ['id' => $selection->id]);
    }

    public function test_destroy_with_mismatched_code()
    {
        $otherCode = Code::factory()->create([
            'codebook_id' => $this->codebook->id,
        ]);

        $selection = Selection::create([
            'id' => 'sel-'.uniqid(),
            'code_id' => $otherCode->id,
            'source_id' => $this->source->id,
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'text' => 'test',
            'start_position' => 0,
            'end_position' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('selection.destroy', [
                'project' => $this->project->id,
                'source' => $this->source->id,
                'code' => $this->code->id,
                'selection' => $selection->id,
            ]));

        $response->assertStatus(400);
        $response->assertJson(['success' => false, 'message' => 'Selection does not match the given source or code']);

        $this->assertDatabaseHas('selections', ['id' => $selection->id]);
    }

    public function test_destroy_requires_authentication()
    {
        $selection = Selection::create([
            'id' => 'sel-'.uniqid(),
            'code_id' => $this->code->id,
            'source_id' => $this->source->id,
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'text' => 'test',
            'start_position' => 0,
            'end_position' => 10,
        ]);

        $response = $this->delete(route('selection.destroy', [
            'project' => $this->project->id,
            'source' => $this->source->id,
            'code' => $this->code->id,
            'selection' => $selection->id,
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_destroy_orphan_deletes_selection()
    {
        $selection = Selection::create([
            'id' => 'sel-'.uniqid(),
            'code_id' => $this->code->id,
            'source_id' => $this->source->id,
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'text' => 'test',
            'start_position' => 0,
            'end_position' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('selection.destroyOrphan', [
                'project' => $this->project->id,
                'source' => $this->source->id,
                'selection' => $selection->id,
            ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Selection deleted successfully']);

        $this->assertDatabaseMissing('selections', ['id' => $selection->id]);
    }

    public function test_destroy_orphan_requires_authentication()
    {
        $selection = Selection::create([
            'id' => 'sel-'.uniqid(),
            'code_id' => $this->code->id,
            'source_id' => $this->source->id,
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'text' => 'test',
            'start_position' => 0,
            'end_position' => 10,
        ]);

        $response = $this->delete(route('selection.destroyOrphan', [
            'project' => $this->project->id,
            'source' => $this->source->id,
            'selection' => $selection->id,
        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_store_with_non_existent_project()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('selection.store', [
                'project' => 'non-existent-id',
                'source' => $this->source->id,
                'code' => $this->code->id,
            ]), [
                'textId' => 'selection-1',
                'start_position' => 0,
                'end_position' => 10,
                'text' => 'test',
            ]);

        $response->assertStatus(404);
    }

    public function test_destroy_with_non_existent_selection()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('selection.destroy', [
                'project' => $this->project->id,
                'source' => $this->source->id,
                'code' => $this->code->id,
                'selection' => 'non-existent-id',
            ]));

        $response->assertStatus(404);
    }

    public function test_change_code_with_non_existent_code()
    {
        $selection = Selection::create([
            'id' => 'sel-'.uniqid(),
            'code_id' => $this->code->id,
            'source_id' => $this->source->id,
            'project_id' => $this->project->id,
            'creating_user_id' => $this->user->id,
            'text' => 'test',
            'start_position' => 0,
            'end_position' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('selection.change-code', [
                'project' => $this->project->id,
                'source' => $this->source->id,
                'code' => 'non-existent-code',
                'selection' => $selection->id,
            ]), [
                'newCodeId' => 'invalid-code-id',
            ]);

        $response->assertStatus(404);
    }
}
