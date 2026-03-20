<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGlobalPreferenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'projects' => ['sometimes', 'array'],
            'projects.sort' => ['sometimes', 'array'],
            'projects.sort.by' => ['nullable', 'string'],
            'projects.sort.dir' => ['nullable', 'in:asc,desc'],

            'theme' => ['sometimes', 'string'],
        ];
    }
}
