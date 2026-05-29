<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVariableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $projectId = $this->route('project');

        $project = Project::find($projectId);

        if (! $project) {
            return false;
        }

        return Gate::allows('view', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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
