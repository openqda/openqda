<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return auth()->check() && auth()->id() === $project->creating_user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|in:name,description',
            'value' => 'required|string|max:255',
        ];
    }

    /**
     * Custom messages for validation errors
     */
    public function messages(): array
    {
        return [
            'type.required' => 'The type of the field to update is required.',
            'type.in' => 'The type must be either name or description.',
            'value.required' => 'The value for the update is required.',
            'value.string' => 'The value must be a string.',
            'value.max' => 'The value may not be greater than 255 characters.',
        ];
    }
}
