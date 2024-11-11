<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Retrieve the project ID from the route
        $projectId = $this->route('project');

        // Find the project model by ID
        $project = Project::find($projectId);

        // Check if the user is authorized to delete the project
        return Gate::allows('delete', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // No additional rules are necessary for this request
        return [];
    }
}
