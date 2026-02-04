<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPreferenceRequest extends FormRequest
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
            'theme' => 'sometimes|string|in:light,dark',
            'sources' => 'sometimes|array',
            'zoom' => 'sometimes|array',
            'codebooks' => 'sometimes|array',
            'analysis' => 'sometimes|array',
        ];
    }
}
