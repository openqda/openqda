<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteOrphanSelectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        $selection = $this->route('selection');

        return Gate::allows('delete', $selection);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'selection_id' => 'required|exists:selections,id',
            'source_id' => 'required|exists:sources,id',
        ];
    }


    /**
     * Modify the request data before it is validated.
     *
     * @return array
     */
    public function validationData()
    {
        return array_merge($this->all(), [
            'source_id' => $this->source->id,
            'selection_id' => $this->selection->id,
        ]);
    }
}
