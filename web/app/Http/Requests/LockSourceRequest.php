<?php

namespace App\Http\Requests;

use App\Models\Source;
use Illuminate\Foundation\Http\FormRequest;

class LockSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $source = Source::findOrFail($this->route('sourceId'));

        return $this->user()->can('update', $source);
    }

    public function rules(): array
    {
        return [];
    }
}
