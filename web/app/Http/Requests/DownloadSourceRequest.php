<?php

namespace App\Http\Requests;

use App\Models\Source;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;

class DownloadSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $source = Source::findOrFail($this->route('sourceId'));

        return $this->user()->can('view', $source->project);
    }

    protected function prepareForValidation(): void
    {
        $key = 'downloads:'.$this->user()->id;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            abort(429, "Too many downloads. Please try again in {$seconds} seconds.");
        }

        RateLimiter::hit($key, 60);
    }

    public function rules(): array
    {
        return [];
    }
}
