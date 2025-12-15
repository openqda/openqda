<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class StoreSourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $projectId = $this->input('projectId');
        $project = Project::find($projectId);

        // If the project does not exist, deny authorization
        if (! $project) {
            return false;
        }

        // Use Gate to check the `view` permission on the project
        return Gate::allows('view', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // get services
        return [
            'file' => 'required|file|mimes:txt,rtf|max:10240', // File type and size restriction
            'projectId' => 'required|exists:projects,id',
        ];
    }

    /**
     * Prepare the data for validation and handle rate limiting.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $rateLimitKey = 'upload-limit:'.(optional($this->user())->id ?: $this->ip());
        if (RateLimiter::tooManyAttempts($rateLimitKey, $perMinute = 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            abort(response()->json([
                'message' => 'Rate limit exceeded. Try again in '.$seconds.' seconds.',
            ], 429));
        }
    }
}
