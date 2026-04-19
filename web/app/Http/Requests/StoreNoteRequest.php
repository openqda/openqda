<?php

namespace App\Http\Requests;

use App\Models\Note;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return Gate::allows('create', [Note::class, $project]);
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string',
            'target' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'scope' => 'nullable|string|max:255',
            'visibility' => 'integer|in:0,1',
        ];
    }
}
