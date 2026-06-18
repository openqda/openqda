<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectTeamResolverService
{
    /**
     * Resolve the project team from the given request URL.
     *
     * Returns an array with:
     *   - 'team': the Team model (or null)
     *   - 'teamMembers': array of formatted team member data
     *
     * An authorization check is performed: team data is only returned when the
     * authenticated user is a member of the team or the team owner. Without this
     * check, any authenticated user could craft a URL such as /projects/999/foo
     * and receive names, e-mail addresses and profile photos of team members they
     * have no legitimate relationship with.
     */
    public function resolveFromRequest(Request $request): array
    {
        $path = parse_url($request->fullUrl(), PHP_URL_PATH);
        $segments = explode('/', $path);

        $projectIdIndex = array_search('projects', $segments) + 1;
        $projectId = $segments[$projectIdIndex] ?? null;

        $team = null;
        if ($projectId && is_numeric($projectId)) {
            $project = Project::find($projectId);
            $team = $project?->team;
        }

        // Guard: if there is no authenticated user, or the user is neither a
        // team member nor the team owner, treat the request as if no team was
        // found. The route-level authorization layer is responsible for
        // rejecting the request entirely; here we simply avoid leaking data.
        if ($team) {
            $user = $request->user();

            if (! $user) {
                // Unauthenticated request – never expose team data.
                $team = null;
            } else {
                $isMember = $team->users()->where('users.id', $user->id)->exists();
                $isOwner = $team->owner->id === $user->id;

                if (! $isMember && ! $isOwner) {
                    $team = null;
                }
            }
        }

        if ($team) {
            $team = $team->load('users');
            $teamMembers = $team->users;

            // add owner if not already in the collection
            if (! $teamMembers->contains($team->owner_id)) {
                $teamMembers->push($team->owner);
            }

            $teamMembers = $teamMembers->map(function ($usr) use ($team) {
                return [
                    'id' => $usr->id,
                    'name' => $usr->name,
                    'isOwner' => $usr->id === $team->owner->id,
                    'email' => $usr->email,
                    'profile_photo_url' => $usr->profile_photo_url,
                ];
            })->values()->all();
        } else {
            $teamMembers = [];
        }

        return [
            'team' => $team,
            'teamMembers' => $teamMembers,
        ];
    }
}
