<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('setting'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'values' => ['required', 'array'],
            'values.*' => ['required', 'array'],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('model_id')) {
            $this->merge([
                'model_id' => (string) $this->model_id,
            ]);
        }
    }
}
