<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferenceController extends Controller
{
    /**
     * Get the current user's preferences.
     */
    public function show(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['theme' => 'light']);
        }

        // Query directly by user_id
        $preferences = UserPreference::where('user_id', $user->id)->first();

        // If no preferences exist, create default ones
        if (! $preferences) {
            $preferences = UserPreference::create([
                'user_id' => $user->id,
                'theme' => 'light',
            ]);
        }

        return response()
            ->json($preferences)
            // Prevent HTTP response caching - when visibilitychange
            // listener fires on tab focus, browser must fetch fresh theme from server, not serve
            // stale cached HTTP response; without these headers cross-tab sync breaks
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

        $user = auth()->user();
        $preferences = UserPreference::where('user_id', $user->id)->first();

        // Create default preferences if they don't exist
        if (! $preferences) {
            $preferences = UserPreference::create([
                'user_id' => $user->id,
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

        if (! $preferences || ! isset($preferences[$key])) {
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
        if (! $preferences) {
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
