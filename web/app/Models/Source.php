<?php

namespace App\Models;

use App\Enums\ContentType;
use App\Traits\AuditableServiceTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Source extends Model implements Auditable
{
    use AuditableServiceTrait, HasFactory, HasUuids, SoftDeletes;

    public const AUDIT_CONTENT_UPDATED = 'source.content_updated';

    public const AUDIT_RENAMED = 'source.renamed';

    public const AUDIT_LOCKED = 'source.locked';

    public const AUDIT_UNLOCKED = 'source.unlocked';

    public const AUDIT_DOWNLOADED = 'source.downloaded';

    protected $appends = ['isLocked', 'CanUnlock', 'charsXLine', 'showLineNumbers'];

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $auditExclude = [
        'upload_path',
        'modifying_user_id',
    ];

    protected $casts = [
        'type' => ContentType::class,
    ];

    protected $fillable = [
        'id',
        'name',
        'description',
        'creating_user_id',
        'modifying_user_id',
        'project_id',
        'type',
        'upload_path',
    ];

    /**
     * Lock the source for coding
     */
    public function lock(): bool
    {
        return Variable::updateOrCreate(
            [
                'source_id' => $this->id,
                'name' => 'isLocked',
            ],
            [
                'type_of_variable' => 'boolean',
                'boolean_value' => true,
            ]
        )->exists;
    }

    /**
     * Unlock the source
     */
    public function unlock(): bool
    {
        return Variable::updateOrCreate(
            [
                'source_id' => $this->id,
                'name' => 'isLocked',
            ],
            [
                'type_of_variable' => 'boolean',
                'boolean_value' => false,
            ]
        )->exists;
    }

    /**
     * Scope for converted sources
     */
    public function scopeConverted($query)
    {
        return $query->whereHas('sourceStatuses', function ($query) {
            $query->where('status', 'like', 'converted:%');
        });
    }

    /**
     * Scope for locked sources
     */
    public function scopeWhereIsLocked($query)
    {
        return $query->whereHas('variables', function ($query) {
            $query->where('name', 'isLocked')
                ->where('boolean_value', true);
        });
    }

    /**
     * Check if content has malicious code
     */
    public function hasMaliciousContent(?string $content): bool
    {
        if (! $content) {
            return false;
        }

        $maliciousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',  // matches onclick=, onload=, etc.
            '/data:\s*[^,]*base64/i',  // matches data:text/html;base64 etc.
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

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

    public function getConvertedAttribute(): SourceStatus|bool
    {
        $status = $this->sourceStatuses()->where('status', 'like', 'converted:%')->first();

        return $status ?: false;
    }

    public function transformVariables(): array
    {
        return $this->variables->mapWithKeys(function ($variable) {
            $result = [];

            $keys = ['text_value', 'boolean_value', 'integer_value', 'float_value', 'date_value'];
            if ($variable->type_of_variable === 'multiple') {
                foreach ($keys as $key) {
                    if (! is_null($variable->$key)) {
                        $result[$variable->name.'_'.str_replace('_value', '', $key)] = $variable->$key;
                    }
                }
            } else {
                $filledKey = collect($keys)->first(function ($key) use ($variable) {
                    return ! is_null($variable->$key);
                });
                $result[$variable->name] = $variable->$filledKey;
            }

            return $result;
        })->toArray();
    }

    public function creatingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creating_user_id');
    }

    public function getCanUnlockAttribute(): bool
    {
        $isLocked = $this->isLocked;
        $hasSelections = $this->selections->isNotEmpty();

        return $isLocked && ! $hasSelections;
    }

    public function modifyingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modifying_user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function variables(): HasMany
    {
        return $this->hasMany(Variable::class, 'source_id');
    }

    public function selections(): HasMany
    {
        return $this->hasMany(Selection::class, 'source_id');
    }

    public function sourceStatuses(): HasMany
    {
        return $this->hasMany(SourceStatus::class, 'source_id');
    }
}
