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
        // TODO: Implement preference update
        return response()->json(['message' => 'update preference'], 501);
    }
}
