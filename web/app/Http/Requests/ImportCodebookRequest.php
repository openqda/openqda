<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCodebookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $projectId = $this->input('project_id');
        $project = \App\Models\Project::find($projectId);

        // If you need to check for specific permissions, you can use Gate
        // return $project && Gate::allows('importCodebook', $project);

        // If no specific authorization logic is needed, you can simply return true
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'file' => 'required|file|mimes:qde,xml,qdc',
            'project_id' => 'required|exists:projects,id',
        ];
    }
}
