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
        $user = Auth::user();
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
     * Update a specific preference value.
     */
    public function updatePreference(Request $request, string $key = 'theme')
    {
        // Accept value either as { theme: 'light' } or { value: 'light' }
        $rules = [$key => 'required'];
        if ($key === 'theme') {
            $rules[$key] = 'required|string|in:light,dark';
        }

        $validated = $request->validate($rules);

        $user = Auth::user();
        $preferences = UserPreference::where('user_id', $user->id)->first();

        // Create default preferences if they don't exist
        if (! $preferences) {
            $preferences = UserPreference::create([
                'user_id' => $user->id,
                'theme' => 'light',
            ]);
        }

        // Update the preference using Eloquent model
        $preferences->{$key} = $validated[$key];
        $preferences->save();

        return response()->json([$key => $preferences->{$key}]);
    }
}
