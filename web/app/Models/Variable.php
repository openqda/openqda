<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Variable extends Model implements Auditable
{
    use HasUuids, \OwenIt\Auditing\Auditable;

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $fillable = [
        'source_id',
        'project_id',
        'guid',
        'name',
        'type_of_variable',
        'description',
        'text_value',
        'boolean_value',
        'integer_value',
        'float_value',
        'date_value',
        'datetime_value',
    ];

    protected $casts = [
        'date_value' => 'datetime',
        'datetime_value' => 'datetime',
    ];

    /**
     * Get the source that owns the variable.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }
}
