<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGlobalPreferenceRequest;
use App\Http\Requests\UpdateProjectPreferenceRequest;
use App\Models\Project;
use App\Models\UserGlobalPreference;
use App\Models\UserProjectPreference;

// use App\Models\GlobalPreference;
class PreferenceController extends Controller
{
    /**
     * Update a specific preference value.
     */
    public function updateProjectPreference(UpdateProjectPreferenceRequest $request, Project $project)
    {
        // Load or create WITHOUT replacing existing preferences
        $prefs = UserProjectPreference::firstOrNew([
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

    public function updateGlobalPreference(UpdateGlobalPreferenceRequest $request)
    {
        $prefs = UserGlobalPreference::firstOrNew([
            'user_id' => $request->user()->id,
        ]);

        $data = $request->validated();

        // Merge project sorting
        if (array_key_exists('projects', $data)) {
            $prefs->projects = array_replace_recursive(
                $prefs->projects ?? [],
                $data['projects'] ?? []
            );
            unset($data['projects']);
        }

        // Theme saved as a separate field, not merged, as it's a single value
        if (array_key_exists('theme', $data)) {
            $prefs->theme = $data['theme'];
            unset($data['theme']);
        }

        $prefs->fill($data);
        $prefs->save();

        return back();
    }
}
