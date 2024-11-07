<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexSourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $projectId = $this->route('project');
        $project = Project::find($projectId);

        // If the project does not exist, deny authorization
        if (! $project) {
            return false;
        }

        // Use Gate to check the `view` permission on the project
        return Gate::allows('view', $project);
    }

    /**
     * Define validation rules for the request.
     *
     * @return array
     */
    public function rules()
    {
        return [

        ];
    }
}
