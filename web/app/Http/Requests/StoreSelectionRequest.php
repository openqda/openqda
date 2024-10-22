<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSelectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Authorize the request if needed
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'text' => 'required|string',
            'start_position' => 'required|integer',
            'end_position' => 'required|integer',
            'description' => 'sometimes|string',
        ];
    }

    /**
     * Modify the request data before it is validated.
     *
     * @return array
     */
    public function validationData()
    {
        return $this->all();
    }
}
