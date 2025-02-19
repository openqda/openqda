<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ChangeCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {

        $selection = $this->route('selection');

        return Gate::allows('update', $selection);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'newCodeId' => 'required|exists:codes,id',
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
