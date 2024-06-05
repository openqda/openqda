<?php

namespace App\Models;

use App\Enums\ContentType;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Source extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    // append these functions return value to the model
    protected $appends = ['isLocked', 'CanUnlock', 'charsXLine', 'showLineNumbers'];

    protected $primaryKey = 'id';

    public $incrementing = false; // Ensure the UUID id is not auto-incrementing

    // remove values when saving audits
    protected $auditExclude = [
        'upload_path',
        'modifying_user_id',
    ];

    protected $casts = [
        'type' => ContentType::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'description',
        'creating_user_id',
        'modifying_user_id',
        'project_id',
        'type',
        'upload_path',
        'path',
        'current_path',
    ];

    /**
     * get if the document is locked for coding or not
     */
    public function getIsLockedAttribute(): bool
    {
        return isset($this->variables['isLocked']) && (bool) $this->variables['isLocked'];
    }

    public function getCharsXLineAttribute(): int
    {
        return isset($this->variables['lineNumbers_integer']) ? (int) $this->variables['lineNumbers_integer'] : 80;
    }

    public function getShowLineNumbersAttribute(): bool
    {
        return isset($this->variables['lineNumbers_boolean']) && (bool) $this->variables['lineNumbers_boolean'];
    }

    /**
     * Determine if the source has the 'converted:html' status.
     */
    public function getConvertedAttribute(): SourceStatus|bool
    {
        $status = $this->sourceStatuses()->where('status', 'like', 'converted:%')->first();

        return $status ?: false;
    }

    /**
     * format the variables to be used in the source
     */
    public function transformVariables(): array
    {
        return $this->variables->mapWithKeys(function ($variable) {
            $result = []; // Initialize the result array

            $keys = ['text_value', 'boolean_value', 'integer_value', 'float_value', 'date_value'];
            if ($variable->type_of_variable === 'multiple') {
                // Handle the case where 'type' is 'multiple'
                // create a new key for each filled value such as 'name_text', 'name_boolean', etc.
                foreach ($keys as $key) {
                    if (! is_null($variable->$key)) {
                        $result[$variable->name.'_'.str_replace('_value', '', $key)] = $variable->$key;
                    }
                }
            } else {
                // Handle the case where 'type' is not 'multiple'
                $filledKey = collect($keys)->first(function ($key) use ($variable) {
                    return ! is_null($variable->$key);
                });
                $result[$variable->name] = $variable->$filledKey;
            }

            return $result;
        })->toArray();
    }

    /**
     * Get the user who created the source.
     */
    public function creatingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creating_user_id');
    }

    /**
     * Get the "canUnlock" status of the source.
     */
    public function getCanUnlockAttribute(): bool
    {
        $isLocked = $this->isLocked; // Make use of previously defined accessor
        $hasSelections = $this->selections->isNotEmpty();

        return $isLocked && ! $hasSelections;
    }

    /**
     * Get the user who last modified the source.
     */
    public function modifyingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modifying_user_id');
    }

    /**
     * Get the project to which the source belongs.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the variables associated with the source.
     */
    public function variables(): HasMany
    {
        return $this->hasMany(Variable::class, 'source_id');
    }

    /**
     * Get the variables associated with the selection.
     */
    public function selections(): HasMany
    {
        return $this->hasMany(Selection::class, 'source_id');
    }

    /**
     * Get the source statuses.
     */
    public function sourceStatuses(): HasMany
    {
        return $this->hasMany(SourceStatus::class, 'source_id');
    }
}
