<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ChangeTeamOwnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $teamId = $this->input('teamId');
        $team = \App\Models\Team::findOrFail($teamId);

        // Check if the user is authorized to change the owner
        return Gate::forUser(auth()->user())->allows('changeOwner', $team);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'userId' => 'required|exists:users,id',
            'projectId' => 'required|exists:projects,id',
            'teamId' => 'required|exists:teams,id',
        ];
    }
}
