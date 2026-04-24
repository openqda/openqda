<?php

namespace App\Http\Middleware;

use App\Services\ProjectTeamResolverService;
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

    public function __construct(private readonly ProjectTeamResolverService $teamResolver)
    {
        // parent Inertia\Middleware has no constructor arguments
    }

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
        // Due to the collaborative setting,
        // we need to resolve the team and team members for nearly every request.
        // If no team exists or the user is not part of any team, these will simply be null/empty.
        ['team' => $team, 'teamMembers' => $teamMembers] = $this->teamResolver->resolveFromRequest($request);

        $privacyFile = resource_path('markdown/privacy.md');
        $termsFile = resource_path('markdown/terms.md');
        $privacy = file_exists($privacyFile) ? date('c', filemtime($privacyFile)) : null;
        $terms = file_exists($termsFile) ? date('c', filemtime($termsFile)) : null;

        return array_merge(parent::share($request), [
            // Synchronously...
            'logo' => asset(config('app.logo')),
            'appName' => config('app.name'),
            'flash' => [
                'message' => session('message'),
            ],
            'privacy' => $privacy,
            'terms' => $terms,
            'projectId' => session('projectId'),
            'sharedTeam' => $team?->only('id', 'name'),
            'usersInPages' => [],
            'teamMembers' => $teamMembers,
            // Lazily...
            'auth.user' => fn () => $request->user()
                ? $request->user()->only('id', 'name', 'email', 'profile_photo_url', 'research_requested', 'research_consent', 'privacy_consent', 'terms_consent', 'email_verified_at')
                : null,

        ], [
            'shouldInterpolate' => true,
        ]);
    }
}
