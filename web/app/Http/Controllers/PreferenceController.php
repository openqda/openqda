<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    /**
     * Get the current user's preferences.
     */
    public function show(Request $request)
    {
        // TODO: Implement preference retrieval
        return response()->json(['message' => 'show preferences'], 501);
    }

    /**
     * Update a specific preference value.
     */
    public function updatePreference(Request $request, string $key = 'theme')
    {
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
