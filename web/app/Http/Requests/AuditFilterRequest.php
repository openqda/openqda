<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuditFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'sometimes|integer|min:5|max:100',
            'page' => 'sometimes|integer|min:1',
            'query' => 'sometimes|string|max:255',
            'before_date' => 'sometimes',
            'after_date' => 'sometimes',
            'start_date' => 'sometimes|required_with:end_date',
            'end_date' => 'sometimes|required_with:start_date',
            'models' => 'sometimes|array',
            'models.*' => [
                'string',
                Rule::in(['Source', 'Selection', 'Code', 'Project', 'Codebook']),
            ],
            'project_id' => 'sometimes|exists:projects,id',
        ];
    }

    /**
     * Get validated and formatted date filters.
     */
    public function getDateFilters(): array
    {
        $dates = [];

        if ($this->filled('before_date')) {
            $dates['before'] = Carbon::createFromFormat('Y-m-d', $this->before_date)
                ->endOfDay();
        }

        if ($this->filled('after_date')) {
            $dates['after'] = Carbon::createFromFormat('Y-m-d', $this->after_date)
                ->startOfDay();
        }

        if ($this->filled(['start_date', 'end_date'])) {
            $dates['between'] = [
                Carbon::createFromFormat('Y-m-d', $this->start_date)->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $this->end_date)->endOfDay(),
            ];
        }

        return $dates;
    }

    /**
     * Get all filters in a structured format.
     */
    public function getFilters(): array
    {
        return [
            'dates' => $this->getDateFilters(),
            'models' => $this->input('models', []),
            'query' => $this->input('query'),
            'per_page' => $this->input('per_page', config('audit.per_page', 20)),
            'project_id' => $this->input('project_id'),
        ];
    }
}
