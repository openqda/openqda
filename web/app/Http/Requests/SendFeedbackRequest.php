<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SendFeedbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if the user is authenticated; customize if further authorization logic is needed
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'summary' => 'string',
            'projectId' => 'nullable|string',
            'type' => 'required|string',
            'path' => 'nullable|string',
            'query' => 'nullable|string',
            'attachLog' => 'nullable|boolean',
            'sendConfirm' => 'nullable|boolean',
        ];
    }

    /**
     * Format and add additional information for feedback data.
     *
     * @return array<string, mixed>
     */
    public function feedbackData(): array
    {
        $user = Auth::user();

        return array_merge($this->validated(), [
            'userId' => $user->id,
            'contact' => $user->email,
        ]);
    }
}
