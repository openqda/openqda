<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $note = $this->route('note');

        return Gate::allows('update', $note);
    }

    public function rules(): array
    {
        return [
            'content' => 'sometimes|required|string',
            'visibility' => 'sometimes|integer|in:0,1',
        ];
    }
}
