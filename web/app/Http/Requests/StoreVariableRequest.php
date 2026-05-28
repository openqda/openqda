<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreVariableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $projectId = $this->route('project');

        $project = Project::find($projectId);

        // If the project does not exist, deny authorization
        if (! $project) {
            return false;
        }

        // User must have access to this project
        return Gate::allows('view', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
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
