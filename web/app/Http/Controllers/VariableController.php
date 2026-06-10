<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteVariableRequest;
use App\Http\Requests\StoreVariableRequest;
use App\Http\Requests\UpdateVariableRequest;
use App\Models\Project;
use App\Models\Variable;
use Illuminate\Support\Str;

class VariableController extends Controller
{
    public function store(StoreVariableRequest $request, Project $project)
    {
        $variable = new Variable($request->validated());

        $variable->project_id = $project->id;
        $variable->guid = (string) Str::uuid();
        $variable->save();

        return response()->json([
            'message' => 'Variable created successfully.',
            'id' => $variable->id,
        ], 201);
    }

    public function show(Project $project, Variable $variable)
    {
        if ((string) $variable->project_id !== (string) $project->id) {
            abort(404);
        }

        return response()->json([
            'variable' => $variable,
        ]);
    }

    public function update(UpdateVariableRequest $request, Project $project, Variable $variable)
    {
        if ((string) $variable->project_id !== (string) $project->id) {
            abort(404);
        }

        $variable->fill($request->validated());

        $variable->save();

        return response()->json([
            'message' => 'Variable successfully updated',
            'variable' => $variable,
        ]);
    }

    public function destroy(DeleteVariableRequest $request, Project $project, Variable $variable)
    {
        if ((string) $variable->project_id !== (string) $project->id) {
            abort(404);
        }

        $variable->delete();

        return response()->json([
            'message' => 'Variable successfully deleted',
        ]);
    }
}
