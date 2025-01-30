<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllowedSettingValue extends Model
{
    use HasUuids;

    protected $fillable = [
        'setting_key',
        'value',
        'caption',
    ];

    use HasFactory;

    // This could be useful to check which settings use this allowed value
    public function settings()
    {
        // We'll need a scope or query to actually check within JSON
        return $this->hasMany(Setting::class);
    }
}
