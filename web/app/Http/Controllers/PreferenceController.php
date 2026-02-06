<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserPreferenceRequest;
use App\Models\Project;
use App\Models\UserPreference;

class PreferenceController extends Controller
{
    /**
     * Update a specific preference value.
     */
    public function updatePreference(UpdateUserPreferenceRequest $request, Project $project)
    {
        // Load or create WITHOUT replacing existing preferences
        $prefs = UserPreference::firstOrNew([
            'user_id' => $request->user()->id,
            'project_id' => $project->id,
        ]);

        $data = $request->validated();

        // Merge zoom settings if provided, ensuring we don't overwrite existing ones
        if (array_key_exists('zoom', $data)) {
            $prefs->zoom = array_replace_recursive(
                $prefs->zoom ?? [],
                $data['zoom'] ?? []
            );
            unset($data['zoom']);
        }

        $prefs->fill($data);
        $prefs->save();

        return back();
    }
}
