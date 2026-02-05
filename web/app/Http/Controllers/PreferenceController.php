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
        UserPreference::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'project_id' => $project->id,
            ],
            $request->validated()
        );

        return back();
    }
}
