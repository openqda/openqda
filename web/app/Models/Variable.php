<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Variable extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $fillable = [
        'source_id',
        'name',
        'type_of_variable',
        'description',
        'text_value',
        'boolean_value',
        'integer_value',
        'float_value',
        'date_value',
    ];

    protected $casts = [
        'date_value' => 'datetime',
    ];

    /**
     * Get the source that owns the variable.
     */
    public function source(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }
}
