<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectPreferenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // we dont need to validate project id here,
        // because the settings are associated by user Id
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
            'sources' => 'sometimes|array',
            'zoom' => 'sometimes|array',
            'codebooks' => 'sometimes|array',
            'analysis' => 'sometimes|array',
        ];
    }
}
