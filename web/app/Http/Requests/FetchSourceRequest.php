<?php

namespace App\Http\Requests;

use App\Models\Source;
use Illuminate\Foundation\Http\FormRequest;

class FetchSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $source = Source::findOrFail($this->route('id'));

        return $this->user()->can('view', $source);
    }

    public function rules(): array
    {
        return [];
    }
}
