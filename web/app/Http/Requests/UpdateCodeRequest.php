<?php

namespace App\Http\Requests;

use App\Models\Code;
use App\Rules\PreventSelfReferentialCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return Gate::allows('update', [Code::class, $project]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'color' => 'sometimes|required|string|max:255',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'parent_id' => ['sometimes', 'nullable', 'uuid', new PreventSelfReferentialCode($this->route('code')?->id)],
        ];
    }
}
