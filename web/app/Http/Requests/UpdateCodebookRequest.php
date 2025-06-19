<?php

namespace App\Http\Requests;

use App\Models\Codebook;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateCodebookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $codebook = Codebook::find($this->route('codebook'));

        return Gate::allows('update', [Codebook::class, $codebook]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'sharedWithPublic' => 'nullable|boolean',
            'sharedWithTeams' => 'nullable|boolean',
            'code_order' => 'nullable',
        ];
    }
}
