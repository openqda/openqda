<?php

namespace App\Http\Requests;

use App\Models\Source;
use Illuminate\Foundation\Http\FormRequest;

class RenameSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $source = Source::findOrFail($this->route('source'));

        return $this->user()->can('update', $source);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
