<?php

use App\Models\Code;
use App\Models\Project;
use App\Models\Selection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('project relation returns a Project instance, not a Code instance', function () {
    $selection = Selection::factory()->create();

    expect($selection->project)->toBeInstanceOf(Project::class)
        ->and($selection->project)->not->toBeInstanceOf(Code::class);
});

it('project relation resolves to the correct project', function () {
    $project = Project::factory()->create();
    $selection = Selection::factory()->create(['project_id' => $project->id]);

    expect($selection->project->id)->toBe($project->id);
});

it('project relation can be eager loaded', function () {
    $project = Project::factory()->create();
    Selection::factory()->count(3)->create(['project_id' => $project->id]);

    $selections = Selection::with('project')->where('project_id', $project->id)->get();

    $selections->each(function ($selection) use ($project) {
        expect($selection->relationLoaded('project'))->toBeTrue()
            ->and($selection->project)->toBeInstanceOf(Project::class)
            ->and($selection->project->id)->toBe($project->id);
    });
});
