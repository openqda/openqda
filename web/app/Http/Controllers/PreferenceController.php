<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class PreferenceController extends Controller
{
    /**
     * Get the current user's preferences.
     */
    public function show(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['theme' => 'light']);
        }

        // Query directly by user_id, don't load full user relation
        $preferences = UserPreference::where('user_id', $userId)->first();

        // If no preferences exist, create default ones
        if (!$preferences) {
            $preferences = UserPreference::create([
                'user_id' => $userId,
                'theme' => 'light',
            ]);
        }

        return response()
            ->json($preferences)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Update the user's preferences.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'sometimes|string|in:light,dark',
        ]);

        $userId = Auth::id();
        $preferences = UserPreference::where('user_id', $userId)->first();

        // Create default preferences if they don't exist
        if (!$preferences) {
            $preferences = UserPreference::create([
                'user_id' => $userId,
                'theme' => 'light',
            ]);
        }

        // Update preferences
        $preferences->update($validated);

        return response()->json($preferences);
    }

    /**
     * Get a specific preference value.
     */
    public function getPreference(Request $request, string $key)
    {
        $preferences = Auth::user()->preferences;

        if (!$preferences || !isset($preferences[$key])) {
            return response()->json(['error' => 'Preference not found'], 404);
        }

        return response()->json([$key => $preferences[$key]]);
    }

    /**
     * Update a specific preference value.
     */
    public function updatePreference(Request $request, string $key)
    {
        $user = Auth::user();
        $preferences = $user->preferences;

        // Create default preferences if they don't exist
        if (!$preferences) {
            $preferences = UserPreference::create([
                'user_id' => $user->id,
                'theme' => 'light',
            ]);
        }

        $validated = $request->validate([
            'value' => 'required',
        ]);

        // Update the specific preference
        $preferences->update([$key => $validated['value']]);

        return response()->json([$key => $preferences[$key]]);
    }
}
