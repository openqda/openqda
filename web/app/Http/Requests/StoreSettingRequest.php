<?php

namespace App\Http\Requests;

use App\Enums\ModelType;
use App\Models\Project;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $modelType = ModelType::from($this->input('model_type'));
        $modelId = (string) $this->input('model_id');

        return match ($modelType) {
            ModelType::User => auth()->id() === (int) $modelId,
            ModelType::Project => $this->user()->can('create', [
                Setting::class,
                Project::find($modelId),
            ]),
            default => false
        };
    }

    public function rules(): array
    {
        return [
            'model_type' => ['required', 'string', new Enum(ModelType::class)],
            'model_id' => ['required', 'string'],
            'values' => ['required', 'array'],
            'values.*' => ['required', 'array'],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('model_id')) {
            $this->merge([
                'model_id' => (string) $this->model_id,
            ]);
        }
    }
}
