<?php

namespace App\Http\Requests;

use App\Models\Codebook;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreCodebookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $projectId = $this->route('project')->id;
        $project = Project::findOrFail($projectId);

        return Gate::allows('create', [Codebook::class, $project]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sharedWithPublic' => 'nullable|boolean',
            'sharedWithTeams' => 'nullable|boolean',
            'import' => 'nullable|boolean',
            'id' => 'nullable|exists:codebooks,id',
        ];
    }
}
