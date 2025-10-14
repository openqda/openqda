<?php

namespace App\Http\Requests;

use App\Models\Code;
use App\Rules\PreventInvalidCodeHierarchy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return Gate::allows('create', [Code::class, $project]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'color' => 'required|string',
            'codebook' => 'required|exists:codebooks,id',
            'parent_id' => ['nullable', 'uuid', new PreventInvalidCodeHierarchy],
        ];
    }
}
