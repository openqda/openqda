<?php

namespace Tests\Controllers;

use App\Models\Project;
use App\Models\Source;
use App\Models\User;
use App\Models\Variable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VariableControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function createVariable(Project $project, Source $source, array $overrides = []): Variable
    {
        $variable = new Variable;

        $variable->id = (string) Str::uuid();
        $variable->project_id = (string) $project->id;
        $variable->source_id = $source->id;
        $variable->guid = (string) Str::uuid();
        $variable->name = $overrides['name'] ?? 'Gender';
        $variable->type_of_variable = $overrides['type_of_variable'] ?? 'text';
        $variable->description = $overrides['description'] ?? null;
        $variable->text_value = $overrides['text_value'] ?? 'Female';
        $variable->boolean_value = $overrides['boolean_value'] ?? null;
        $variable->integer_value = $overrides['integer_value'] ?? null;
        $variable->float_value = $overrides['float_value'] ?? null;
        $variable->date_value = $overrides['date_value'] ?? null;
        $variable->datetime_value = $overrides['datetime_value'] ?? null;

        $variable->save();

        return $variable;
    }

    public function test_store_creates_variable()
    {
        $project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $source = Source::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $data = [
            'source_id' => $source->id,
            'name' => 'Gender',
            'type_of_variable' => 'text',
            'text_value' => 'Female',
        ];

        $response = $this->postJson(route('variables.store', $project), $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'id',
        ]);

        $this->assertDatabaseHas('variables', [
            'project_id' => (string) $project->id,
            'source_id' => $source->id,
            'name' => 'Gender',
            'type_of_variable' => 'text',
            'text_value' => 'Female',
        ]);

        $variable = Variable::where('name', 'Gender')->first();

        $this->assertNotNull($variable);
        $this->assertNotNull($variable->guid);
    }

    public function test_store_fails_validation()
    {
        $project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $response = $this->postJson(route('variables.store', $project), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'source_id',
            'name',
            'type_of_variable',
        ]);
    }

    public function test_show_returns_variable()
    {
        $project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $source = Source::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $variable = $this->createVariable($project, $source, [
            'name' => 'Age',
            'type_of_variable' => 'integer',
            'text_value' => null,
            'integer_value' => 25,
        ]);

        $response = $this->getJson(route('variables.show', [
            'project' => $project->id,
            'variable' => $variable->id,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'variable' => [
                'id' => $variable->id,
                'project_id' => (string) $project->id,
                'source_id' => $source->id,
                'name' => 'Age',
                'type_of_variable' => 'integer',
                'integer_value' => 25,
            ],
        ]);
    }

    public function test_show_returns_404_if_variable_belongs_to_other_project()
    {
        $project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $otherProject = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $source = Source::factory()->create([
            'project_id' => $otherProject->id,
            'creating_user_id' => $this->user->id,
        ]);

        $variable = $this->createVariable($otherProject, $source, [
            'name' => 'Other Variable',
            'text_value' => 'Other value',
        ]);

        $response = $this->getJson(route('variables.show', [
            'project' => $project->id,
            'variable' => $variable->id,
        ]));

        $response->assertStatus(404);
    }

    public function test_update_changes_variable()
    {
        $project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $source = Source::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $variable = $this->createVariable($project, $source);

        $data = [
            'source_id' => $source->id,
            'name' => 'Gender Updated',
            'type_of_variable' => 'text',
            'text_value' => 'Female Updated',
        ];

        $response = $this->putJson(route('variables.update', [
            'project' => $project->id,
            'variable' => $variable->id,
        ]), $data);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Variable successfully updated',
            'variable' => [
                'id' => $variable->id,
                'name' => 'Gender Updated',
                'type_of_variable' => 'text',
                'text_value' => 'Female Updated',
            ],
        ]);

        $this->assertDatabaseHas('variables', [
            'id' => $variable->id,
            'name' => 'Gender Updated',
            'text_value' => 'Female Updated',
        ]);
    }

    public function test_update_fails_validation()
    {
        $project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $source = Source::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $variable = $this->createVariable($project, $source);

        $response = $this->putJson(route('variables.update', [
            'project' => $project->id,
            'variable' => $variable->id,
        ]), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'source_id',
            'name',
            'type_of_variable',
        ]);
    }

    public function test_update_returns_404_if_variable_belongs_to_other_project()
    {
        $project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $otherProject = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $source = Source::factory()->create([
            'project_id' => $otherProject->id,
            'creating_user_id' => $this->user->id,
        ]);

        $variable = $this->createVariable($otherProject, $source, [
            'name' => 'Other Variable',
            'text_value' => 'Other value',
        ]);

        $data = [
            'source_id' => $source->id,
            'name' => 'Updated Name',
            'type_of_variable' => 'text',
            'text_value' => 'Updated Value',
        ];

        $response = $this->putJson(route('variables.update', [
            'project' => $project->id,
            'variable' => $variable->id,
        ]), $data);

        $response->assertStatus(404);

        $this->assertDatabaseHas('variables', [
            'id' => $variable->id,
            'name' => 'Other Variable',
            'text_value' => 'Other value',
        ]);
    }

    public function test_destroy_deletes_variable()
    {
        $project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $source = Source::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $variable = $this->createVariable($project, $source, [
            'name' => 'Variable To Delete',
            'text_value' => 'Delete me',
        ]);

        $response = $this->deleteJson(route('variables.destroy', [
            'project' => $project->id,
            'variable' => $variable->id,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Variable successfully deleted',
        ]);

        $this->assertDatabaseMissing('variables', [
            'id' => $variable->id,
        ]);
    }

    public function test_destroy_returns_404_if_variable_belongs_to_other_project()
    {
        $project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $otherProject = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $source = Source::factory()->create([
            'project_id' => $otherProject->id,
            'creating_user_id' => $this->user->id,
        ]);

        $variable = $this->createVariable($otherProject, $source, [
            'name' => 'Other Variable',
            'text_value' => 'Other value',
        ]);

        $response = $this->deleteJson(route('variables.destroy', [
            'project' => $project->id,
            'variable' => $variable->id,
        ]));

        $response->assertStatus(404);

        $this->assertDatabaseHas('variables', [
            'id' => $variable->id,
        ]);
    }

    public function test_store_fails_if_variable_name_already_exists_for_same_project_and_source()
    {
        $project = Project::factory()->create([
            'creating_user_id' => $this->user->id,
        ]);

        $source = Source::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $this->user->id,
        ]);

        $this->createVariable($project, $source, [
            'name' => 'Gender',
            'type_of_variable' => 'text',
            'text_value' => 'Female',
        ]);

        $response = $this->postJson(route('variables.store', $project), [
            'source_id' => $source->id,
            'name' => 'Gender',
            'type_of_variable' => 'text',
            'text_value' => 'Male',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
        ]);
    }
}
