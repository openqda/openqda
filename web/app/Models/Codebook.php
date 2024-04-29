<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Codebook extends Model implements Auditable
{

    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'description',
        'project_id',
        'properties',
        'creating_user_id',
    ];

      protected $auditExclude = [
        'plain_text_path',
        'rich_text_path',
        'path',
        'modifying_user_id',
        '™plain_text_content',
    ];


    protected $casts = [
        'properties' => 'array',
    ];
    protected $withCount = ['codes'];

    protected $dispatchesEvents = [
        'deleting' => \App\Events\CodebookDeleting::class,
    ];


    /**
     * Get the project to which the source belongs.
     */
    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function codes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Code::class);
    }

    /**
     * Get the user who created the codebook.
     */
    public function creatingUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'creating_user_id');
    }

    /**
     * Accessor for 'public' attribute.
     */
    public function getPublicAttribute()
    {
        return $this->properties['sharedWithPublic'] ?? false;
    }

    /**
     * Accessor for 'teams' attribute.
     */
    public function getTeamsAttribute()
    {
        return $this->properties['sharedWithTeams'] ?? false;
    }


}
