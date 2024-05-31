<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     */
    public function share(Request $request): array
    {

        // Get the current URL
        $currentUrl = $request->fullUrl();

        // Parse the URL to get the path segments
        $path = parse_url($currentUrl, PHP_URL_PATH);
        $segments = explode('/', $path);

        // Find the project ID segment in the URL - assuming the URL structure is fixed
        $projectIdIndex = array_search('projects', $segments) + 1;
        $projectId = $segments[$projectIdIndex] ?? null;

        // Now that we have the project ID, we can find the project and its team
        $team = null;
        if ($projectId && is_numeric($projectId)) {
            $project = \App\Models\Project::find($projectId);
            $team = $project?->team;
        }

        return array_merge(parent::share($request), [
            // Synchronously...
            'logo' => asset(config('app.logo')),
            'appName' => config('app.name'),
            'flash' => [
                'message' => session('message'),
            ],
            'projectId' => session('projectId'),
            'sharedTeam' => $team?->only('id', 'name'),
            'usersInPages' => [],
            'ownTeams' => ($request->user()?->ownedTeams()->where('id', '!=', $request->user()?->personalTeam()->id ?? null)->with('Projects', 'users')->has('users')->get()),
            // Lazily...
            'auth.user' => fn () => $request->user()
                ? $request->user()->only('id', 'name', 'email', 'profile_photo_url')
                : null,

        ], [
            'shouldInterpolate' => true,
        ]);
    }
}
