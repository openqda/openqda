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
            // These fields are NOT allowed during update
            'id' => 'missing',
            'project_id' => 'missing',
            'source_id' => 'missing',
            'guid' => 'missing',
            'name' => 'missing',
            'type_of_variable' => 'missing',
            'description' => 'missing',

            // Only these value fields are allowed
            'text_value' => 'sometimes|nullable|string',
            'boolean_value' => 'sometimes|nullable|boolean',
            'integer_value' => 'sometimes|nullable|integer',
            'float_value' => 'sometimes|nullable|numeric',
            'date_value' => 'sometimes|nullable|date',
            'datetime_value' => 'sometimes|nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'id.missing' => 'The variable id cannot be updated.',
            'project_id.missing' => 'The project cannot be changed.',
            'source_id.missing' => 'The source cannot be changed.',
            'guid.missing' => 'The variable GUID cannot be updated.',
            'name.missing' => 'The variable name cannot be updated.',
            'type_of_variable.missing' => 'The variable type cannot be updated.',
            'description.missing' => 'The variable description cannot be updated.',
        ];
    }
}
