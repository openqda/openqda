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

        foreach (['zoom', 'codebooks', 'analysis'] as $field) {
            if (array_key_exists($field, $data)) {
                $prefs->$field = array_replace_recursive(
                    $prefs->$field ?? [],
                    $data[$field] ?? []
                );

                unset($data[$field]);
            }
        }
        if (array_key_exists('sources', $data)) {
            $prefs->sources = $data['sources']; // REPLACE, not merge
            unset($data['sources']);
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
