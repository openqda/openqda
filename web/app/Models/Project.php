<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Project extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'origin',
        'creating_user_id',
        'modifying_user_id',
        'base_path',
        'team_id'
    ];

    /**
     * Used to prevent the delete event of project from being dispatched
     * @var bool $conditionallyPreventEvent
     */
    public $conditionallyPreventEvent = false;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($project) {
            if ($project->conditionallyPreventEvent) {
                // Prevent the custom event from being dispatched
                return;
            }

            // Dispatch the event manually if not prevented
            event(new \App\Events\ProjectDeleting($project));
        });
    }

    /**
     * Get the user who created the project.
     */
    public function creatingUser()
    {
        return $this->belongsTo(User::class, 'creating_user_id');
    }

    /**
     * Get the user who last modified the project.
     */
    public function modifyingUser()
    {
        return $this->belongsTo(User::class, 'modifying_user_id');
    }

    /**
     * get the team that has this project shared with
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * get documents that are related to this project
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sources()
    {
        return $this->hasMany(Source::class, 'project_id');
    }


    /**
     * get all codebooks related to this project
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function codebooks()
    {
        return $this->hasMany(Codebook::class, 'project_id');
    }

    /**
     * get all selections related to this project
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function selections()
    {
        return $this->hasMany(Selection::class, 'project_id');
    }

    /**
     * Get all the project, including the deleted ones
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\LaravelIdea\Helper\App\Models\_IH_Source_QB
     */
    public function trashedSources()
    {
        return $this->sources()->withTrashed();
    }


    public function getAllCodesAttribute()
    {
        $codes = collect();  // Create an empty collection

        foreach ($this->codebooks as $codebook) {
            $codes = $codes->merge($codebook->codes);  // Merge codes from each codebook
        }

        return $codes;
    }

}
