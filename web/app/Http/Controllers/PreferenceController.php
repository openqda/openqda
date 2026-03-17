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
        $data = $request->validated();
        // Load or create WITHOUT replacing existing preferences
        $prefs = UserProjectPreference::firstOrNew([
            'user_id' => $request->user()->id,
            'project_id' => $project->id,
        ]);
        // Merge new preferences with existing ones, preserving old values
        foreach (['zoom', 'codebooks', 'analysis'] as $field) {
            if (array_key_exists($field, $data)) {
                $prefs->$field = array_replace_recursive(
                    $prefs->$field ?? [], // Existing stored preferences.
                    $data[$field] ?? [] // Incoming updates override matching keys.
                );

                // Remove the field since it was merged into preferences.
                unset($data[$field]);
            }
        }
        if (array_key_exists('sources', $data)) {
            // Replace sources as a whole (no merge).
            $prefs->sources = $data['sources'];
            unset($data['sources']);
        }

        $prefs->fill($data);
        $prefs->save();

        return back();
    }

    public function updateGlobalPreference(UpdateGlobalPreferenceRequest $request)
    {
        $data = $request->validated();
        $prefs = UserGlobalPreference::firstOrNew([
            'user_id' => $request->user()->id,
        ]);

        // Replace project sorting preferences entirely (do not merge sorting arrays)
        if (array_key_exists('projects', $data)) {
            $prefs->projects = $data['projects'];
            // Remove after processing to prevent it from being handled again later.
            unset($data['projects']);
        }
        // Theme is stored as a simple value, so replace it directly instead of merging
        if (array_key_exists('theme', $data)) {
            $prefs->theme = $data['theme'];
            // Remove after processing to prevent it from being handled again later.
            unset($data['theme']);
        }

        $prefs->fill($data);
        $prefs->save();

        return back();
    }
}
