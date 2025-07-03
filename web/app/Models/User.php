<?php

namespace App\Models;

use App\Enums\ModelType;
use App\Services\AuditService;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements Auditable, FilamentUser, MustVerifyEmail
{
    use AuditableTrait, HasApiTokens, HasFactory, HasProfilePhoto, HasTeams, Notifiable, TwoFactorAuthenticatable;

    public function canAccessPanel(Panel|\Filament\Panel $panel): bool
    {
        $allowedEmails = config('filament.admin_panel.allowed_emails');

        return in_array($this->email, $allowedEmails);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Exclude specific values when saving audits.
     *
     * @var array<int, string>
     */
    protected $auditExclude = [
        'remember_token',
    ];

    public function projects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Project::class, 'creating_user_id');
    }

    public function settings()
    {
        return $this->hasMany(Setting::class, 'model_id')
            ->where('model_type', ModelType::User->value); // or Project->value
    }

    /**
     * Get all projects where the user is part of the team or the creator.
     */
    public function allRelatedProjects(): Collection|array|\LaravelIdea\Helper\App\Models\_IH_Project_C
    {
        // Get the ids of all the teams the user belongs to
        $teamIds = $this->teams->pluck('id');

        // Use whereHas to filter projects where the user is part of the team
        // or where the user is the creator
        return Project::whereHas('team', function (Builder $query) use ($teamIds) {
            $query->whereIn('id', $teamIds);
        })
            ->orWhere('creating_user_id', $this->id)
            ->with([
                'audits',
                'sources.audits',
                'sources.selections.audits',
                'codebooks.codes.audits',
            ])
            ->get();
    }

    public function getAllAudits($filters = [])
    {
        $auditService = app(AuditService::class); // manually resolving the service from the container

        // Eager load audits for all related models
        $projects = $this->allRelatedProjects();

        $allAudits = collect();

        foreach ($projects as $project) {
            // Transform project audits
            $projectAuditsData = collect($project->audits);
            $projectAudits = $auditService->transformAudits($projectAuditsData, 'Project', ['id', 'project_id', 'creating_user_id']);
            $allAudits = $allAudits->concat($projectAudits);
            // For each source within a project
            foreach ($project->sources as $source) {
                // Transform source audits
                $sourceAuditsData = collect($source->audits);
                $sourceAudits = $auditService->transformAudits($sourceAuditsData, 'Source', ['id', 'project_id', 'creating_user_id']);
                $allAudits = $allAudits->concat($sourceAudits);

                // For each selection within a source
                foreach ($source->selections as $selection) {
                    $selectionAuditsData = collect($selection->audits);
                    $selectionAudits = $auditService->transformAudits($selectionAuditsData, 'Selection', ['id', 'source_id', 'creating_user_id', 'project_id', 'start_position', 'end_position']);

                    // Update the code_id within the audits
                    $selectionAudits->transform(function ($audit) use ($selection) {
                        if (isset($audit['old_values']['code_id'])) {
                            $audit['old_values']['code_id'] = $selection->code->name ?? $audit['old_values']['code_id'];
                        }
                        if (isset($audit['new_values']['code_id'])) {
                            $audit['new_values']['code_id'] = $selection->code->name ?? $audit['new_values']['code_id'];
                        }

                        return $audit;
                    });

                    $allAudits = $allAudits->concat($selectionAudits);
                }
            }

            // Transform code audits within codebooks
            foreach ($project->codebooks as $codebook) {
                foreach ($codebook->codes as $code) {
                    $codeAuditsData = collect($code->audits);
                    $codeAudits = $auditService->transformAudits($codeAuditsData, 'Code', ['id', 'codebook_id', 'creating_user_id']);
                    $allAudits = $allAudits->concat($codeAudits);
                }
            }
        }

        $allAudits = $auditService->filterAudits($allAudits, $filters);

        return $allAudits->sortByDesc('created_at');
    }

    public function codebooks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Codebook::class, 'creating_user_id');
    }

    /**
     * Get all codebooks where the user is the creator of the project.
     * Optionally exclude codebooks from a specific project.
     *
     * @param  int|null  $excludeProjectId  Project ID to exclude codebooks from, if provided.
     * @return Builder[]|Collection
     */
    public function getCodebooksAsCreator($excludeProjectId = null)
    {
        $query = Codebook::withCount('codes')
            ->where('creating_user_id', '=', $this->id)
            ->whereHas('project', function ($query) use ($excludeProjectId) {
                if (! is_null($excludeProjectId)) {
                    $query->where('id', '!=', $excludeProjectId);
                }
            });

        return $query->get()->map(function ($codebook) {
            return [
                'id' => $codebook->id,
                'name' => $codebook->name,
                'description' => $codebook->description,
                'properties' => $codebook->properties,
                'codes_count' => $codebook->codes_count, // Use count instead of full codes
                'project_id' => $codebook->project_id,
                // Include other codebook attributes as needed
                'creatingUserEmail' => $codebook->creatingUser->email ?? '', // Include the creating user's email
            ];
        });
    }
}
