<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateVariableRequest extends FormRequest
{
    public function authorize(): bool
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

    public function rules(): array
    {
        return [
            'source_id' => 'required|uuid|exists:sources,id',

            'name' => 'required|string|max:255',

            'type_of_variable' => 'required|string|max:255',

            'description' => 'nullable|string',

            'text_value' => 'nullable|string',

            'boolean_value' => 'nullable|boolean',

            'integer_value' => 'nullable|integer',

            'float_value' => 'nullable|numeric',

            'date_value' => 'nullable|date',

            'datetime_value' => 'nullable|date',
        ];
    }
}
