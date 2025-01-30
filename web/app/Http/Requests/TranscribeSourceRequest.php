<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class TranscribeSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $projectId = $this->input('project_id');
        $project = Project::findOrFail($projectId);

        return $this->user()->can('view', $project);
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|extensions:mpeg,mpga,mp3,wav,aac,ogg,m4a,flac|max:100000',
            'model' => 'required|string',
            'language' => 'required|string',
            'project_id' => 'required|exists:projects,id',
        ];
    }
}
