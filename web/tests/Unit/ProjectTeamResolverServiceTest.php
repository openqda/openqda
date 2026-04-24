<?php

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectTeamResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns empty teamMembers and null team when no project segment in URL', function () {
    $request = Request::create('https://example.com/dashboard');
    $service = new ProjectTeamResolverService;

    $result = $service->resolveFromRequest($request);

    expect($result['team'])->toBeNull()
        ->and($result['teamMembers'])->toBe([]);
});

it('returns empty teamMembers and null team when project id is not numeric', function () {
    $request = Request::create('https://example.com/projects/abc/sources');
    $service = new ProjectTeamResolverService;

    $result = $service->resolveFromRequest($request);

    expect($result['team'])->toBeNull()
        ->and($result['teamMembers'])->toBe([]);
});

it('returns empty teamMembers and null team when project does not exist', function () {
    $request = Request::create('https://example.com/projects/99999/sources');
    $service = new ProjectTeamResolverService;

    $result = $service->resolveFromRequest($request);

    expect($result['team'])->toBeNull()
        ->and($result['teamMembers'])->toBe([]);
});

it('returns team and teamMembers for a valid project URL', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $team = $owner->currentTeam;

    $member = User::factory()->create();
    $team->users()->attach($member, ['role' => 'editor']);

    $project = Project::factory()->create(['team_id' => $team->id]);

    $request = Request::create("https://example.com/projects/{$project->id}/sources");
    $request->setUserResolver(fn () => $owner);
    $service = new ProjectTeamResolverService;

    $result = $service->resolveFromRequest($request);

    expect($result['team'])->not->toBeNull()
        ->and($result['team']->id)->toBe($team->id)
        ->and($result['teamMembers'])->toBeArray()
        ->and(count($result['teamMembers']))->toBeGreaterThanOrEqual(1);

    $memberIds = array_column($result['teamMembers'], 'id');
    expect($memberIds)->toContain($owner->id);
});

it('returns null team for unauthenticated request even when project exists', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $team = $owner->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    // No user logged in – request has no auth user
    $request = Request::create("https://example.com/projects/{$project->id}/sources");
    $service = new ProjectTeamResolverService;

    $result = $service->resolveFromRequest($request);

    expect($result['team'])->toBeNull()
        ->and($result['teamMembers'])->toBe([]);
});

it('returns null team when authenticated user is not part of the team', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $team = $owner->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    $outsider = User::factory()->create();

    $request = Request::create("https://example.com/projects/{$project->id}/sources");
    $request->setUserResolver(fn () => $outsider);
    $service = new ProjectTeamResolverService;

    $result = $service->resolveFromRequest($request);

    expect($result['team'])->toBeNull()
        ->and($result['teamMembers'])->toBe([]);
});

it('includes owner in teamMembers even if not in team users', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $team = $owner->currentTeam;

    $project = Project::factory()->create(['team_id' => $team->id]);

    $request = Request::create("https://example.com/projects/{$project->id}/sources");
    $request->setUserResolver(fn () => $owner);
    $service = new ProjectTeamResolverService;

    $result = $service->resolveFromRequest($request);

    $memberIds = array_column($result['teamMembers'], 'id');
    expect($memberIds)->toContain($owner->id);

    $ownerEntry = collect($result['teamMembers'])->firstWhere('id', $owner->id);
    expect($ownerEntry['isOwner'])->toBeTrue();
});
