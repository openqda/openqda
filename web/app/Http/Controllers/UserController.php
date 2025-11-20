<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ResearchConsentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function getOwnedTeams(Request $request, User $user)
    {
        if (! $this->hasPermission($user)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $ownedTeams = $user->ownedTeams()->where('id', '!=', $user->personalTeam()->id ?? null)->with('Projects', 'users')->has('users')->get();

        return response()->json([
            'ownTeams' => $ownedTeams,
        ]);
    }

    /**
     * Record user consent for legal documents (privacy, terms).
     */
    public function consentLegal(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'terms' => ['sometimes', 'boolean'],
            'privacy' => ['sometimes', 'boolean'],
            'research' => ['sometimes', 'boolean'],
        ]);

        if (! $request->hasAny(['terms', 'privacy'])) {
            return response()->json(['message' => 'No consent provided'], 400);
        }

        if ($request->has('terms') && $request->input('terms') === true) {
            $user->update(['terms_consent' => now()]);
            $user->createAudit(User::AUDIT_TERMS_CONSENTED, ['terms_consent' => true]);
        }
        if ($request->has('privacy') && $request->input('privacy') === true) {
            $user->update(['privacy_consent' => now()]);
            $user->createAudit(User::AUDIT_PRIVACY_CONSENTED, ['privacy_consent' => true]);
        }
        if ($request->has('research') && $request->input('research') === true) {
            $research = app(ResearchConsentService::class);
            $research->sendResearchConfirmation($user);
        }

        return response()->json(['message' => 'Consent updated', 'success' => true], 200);
    }

    /**
     * Send research participation token via Email to user.
     */
    public function requestResearch(Request $request)
    {
        $user = Auth::user();
        $consent = app(ResearchConsentService::class);
        $consent->sendResearchConfirmation($user);

        return response()->json(['message' => 'Research token sent via Email.', 'success' => true], 200);
    }

    /**
     * Confirm user research participation.
     */
    public function confirmResearch(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:256',
        ]);
        $user = Auth::user();
        $token = urldecode($request->input('token'));

        $consent = app(ResearchConsentService::class);
        try {
            $consent->confirmResearch($user, $token);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
        $user->createAudit(User::AUDIT_RESEARCH_CONSENTED, ['research_consent' => true]);

        return response()->json(['message' => 'Research participation confirmed.', 'success' => true], 200);
    }

    /**
     * Withdraw user research participation.
     */
    public function withdrawResearch(Request $request)
    {
        $user = Auth::user();
        $consent = app(ResearchConsentService::class);
        try {
            $consent->withdrawResearch($user);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        $user->createAudit(User::AUDIT_RESEARCH_WITHDRAWN, ['research_consent' => false]);

        return response()->json(['message' => 'Research participation withdrawn.', 'success' => true], 200);
    }

    /**
     * Check if the authenticated user has permission to perform actions on the target user.
     */
    protected function hasPermission(User $user): bool
    {
        return Auth::user()->id === $user->id || in_array(Auth::user()->email, config('app.admins'));
    }
}
