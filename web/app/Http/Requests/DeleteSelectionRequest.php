<?php

namespace App\Http\Requests;

use App\Models\Selection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteSelectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $selection = Selection::findOrFail($this->route('selection'));

        return Gate::allows('delete', $selection);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'source_id' => 'required|exists:sources,id',
            'code_id' => 'required|exists:codes,id',
            'selection_id' => 'required|exists:selections,id',
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
            'code_id' => $this->code->id,
            'selection_id' => $this->selection->id,
        ]);
    }
}
