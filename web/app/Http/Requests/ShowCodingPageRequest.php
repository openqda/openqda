<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ShowCodingPageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('view', $this->route('project'));
    }

    /**
     * Define validation rules for the request.
     *
     * @return array
     */
    public function rules()
    {
        return [

        ];
    }
}
