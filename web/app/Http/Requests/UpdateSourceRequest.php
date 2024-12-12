<?php

namespace App\Http\Requests;

use App\Models\Source;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $source = Source::findOrFail($this->input('id'));

        // Check if source is locked before allowing update
        if ($source->isLocked) {
            return false;
        }

        return $this->user()->can('update', $source);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'exists:sources,id'],
            'content' => ['required', 'array', function ($attribute, $value, $fail) {

                // Check for malicious content
                if (str_contains(strtolower($value['editorContent']), '<script') ||
                    str_contains(strtolower($value['editorContent']), 'javascript:') ||
                    preg_match('/on\w+\s*=/i', $value['editorContent'])) {
                    $fail('The content contains potentially malicious code.');
                }
            }],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.required' => 'A document ID is required',
            'id.exists' => 'The specified document does not exist',
            'content.required' => 'The content field is required',
        ];
    }
}
