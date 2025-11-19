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
