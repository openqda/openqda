<?php

namespace App\Models;

use App\Enums\ModelType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'model_type',
        'model_id',
        'values',
    ];

    protected $casts = [
        'values' => 'json',
        'model_type' => ModelType::class,
        'model_id' => 'string',
    ];

    public function setModelIdAttribute($value)
    {
        $this->attributes['model_id'] = (string) $value;
    }

    // Basic validation rule for the JSON structure
    public static function jsonValidationRules(): array
    {
        return [
            'values' => ['required', 'json', 'array'],
            'values.*' => ['required', 'array'],
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'model_id')
            ->where('model_type', ModelType::User->value);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'model_id')
            ->where('model_type', ModelType::Project->value);
    }
}
