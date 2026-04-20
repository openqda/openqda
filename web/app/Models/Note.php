<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Note extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'content',
        'project_id',
        'target',
        'visibility',
        'type',
        'scope',
        'creating_user_id',
    ];

    public const AUDIT_CONTENT_UPDATED = 'note.content_updated';

    public const AUDIT_CREATED = 'note.created';

    public const AUDIT_UPDATED = 'note.updated';

    public const AUDIT_DELETED = 'note.deleted';

    /**
     * Scope constants for target resolution.
     */
    public const SCOPE_SELECTION = 'selection';

    public const SCOPE_SOURCE = 'source';

    public const SCOPE_CODE = 'code';

    public const SCOPE_PROJECT = 'project';

    /**
     * Get the project this note belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the user who created this note.
     */
    public function creatingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creating_user_id');
    }

    /**
     * Resolve the target model based on scope and target fields.
     */
    public function getTargetModelAttribute(): ?Model
    {
        return match ($this->scope) {
            self::SCOPE_SELECTION => Selection::find($this->target),
            self::SCOPE_SOURCE => Source::find($this->target),
            self::SCOPE_CODE => Code::find($this->target),
            self::SCOPE_PROJECT => Project::find($this->target),
            default => null,
        };
    }

    /**
     * Scope query to notes for a specific target type and id.
     */
    public function scopeForTarget($query, string $scope, $targetId)
    {
        return $query->where('scope', $scope)->where('target', $targetId);
    }
}
