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

        // Merge codebooks so previous codebook visibility map is not overwritten
        if (array_key_exists('codebooks', $data)) {
            $prefs->codebooks = array_replace_recursive(
                $prefs->codebooks ?? [],
                $data['codebooks'] ?? []
            );
            unset($data['codebooks']);
        }

        // Merge analysis visibility settings
        if (array_key_exists('analysis', $data)) {
            $prefs->analysis = array_replace_recursive(
                $prefs->analysis ?? [],
                $data['analysis'] ?? []
            );
            unset($data['analysis']);
        }

        $prefs->fill($data);
        $prefs->save();

        return back();
    }
}
