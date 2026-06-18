<?php

use App\Models\Note;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can be created with factory', function () {
    $note = Note::factory()->create();

    expect($note)->toBeInstanceOf(Note::class)
        ->and($note->id)->toBeInt()
        ->and($note->content)->toBeString();
});

it('has correct fillable attributes', function () {
    $note = new Note;

    expect($note->getFillable())->toBe([
        'content',
        'project_id',
        'target',
        'visibility',
        'type',
        'scope',
        'creating_user_id',
    ]);
});

it('belongs to a project', function () {
    $project = Project::factory()->create();
    $note = Note::factory()->create(['project_id' => $project->id]);

    expect($note->project->id)->toBe($project->id);
});

it('belongs to a creating user', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['creating_user_id' => $user->id]);

    expect($note->creatingUser->id)->toBe($user->id);
});

it('can scope query by target', function () {
    $note = Note::factory()->create([
        'scope' => Note::SCOPE_SOURCE,
        'target' => 42,
    ]);

    Note::factory()->create([
        'scope' => Note::SCOPE_CODE,
        'target' => 99,
    ]);

    $results = Note::forTarget(Note::SCOPE_SOURCE, 42)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($note->id);
});

it('resolves target model attribute for project scope', function () {
    $project = Project::factory()->create();
    $note = Note::factory()->create([
        'scope' => Note::SCOPE_PROJECT,
        'target' => $project->id,
    ]);

    expect($note->targetModel)->toBeInstanceOf(Project::class)
        ->and($note->targetModel->id)->toBe($project->id);
});

it('returns null for unknown scope', function () {
    $note = Note::factory()->create(['scope' => 'unknown']);

    expect($note->targetModel)->toBeNull();
});
