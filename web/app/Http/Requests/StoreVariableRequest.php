<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreVariableRequest extends FormRequest
{
    public function authorize()
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            $project = Project::find($project);
        }

        if (! $project) {
            return false;
        }

        return Gate::allows('view', $project);
    }

    public function rules()
    {
        $project = $this->route('project');

        $projectId = $project instanceof Project
            ? $project->id
            : $project;

        return [
            'source_id' => [
                'required',
                'uuid',
                Rule::exists('sources', 'id')->where('project_id', (string) $projectId),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('variables', 'name')
                    ->where('project_id', (string) $projectId)
                    ->where('source_id', $this->input('source_id')),
            ],

            'type_of_variable' => [
                'required',
                Rule::in(['text', 'boolean', 'integer', 'float', 'data', 'datetime']),
            ],

            'description' => 'nullable|string',

            'text_value' => 'nullable|string',

            'boolean_value' => 'nullable|boolean',

            'integer_value' => 'nullable|integer',

            'float_value' => 'nullable|numeric',

            'date_value' => 'nullable|date',

            'datetime_value' => 'nullable|date',
        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'A variable with this name already exists for this project and source.',
        ];
    }
}
