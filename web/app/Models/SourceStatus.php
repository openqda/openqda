<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SourceStatus extends Model
{
    use HasUuids;

    protected $fillable = ['source_id', 'status', 'path'];

    protected $primaryKey = 'id';

    public $incrementing = false; // Ensure the UUID id is not auto-incrementing

    protected $casts = [
        'id' => 'string', // Ensure the UUID id is treated as a string
    ];

    /**
     * Relationship to the Source model
     */
    public function source()
    {
        return $this->belongsTo(Source::class);
    }
}
