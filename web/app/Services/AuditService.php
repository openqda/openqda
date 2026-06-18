<?php

namespace App\Services;

use App\Models\Code;
use App\Models\Note;
use App\Models\Project;
use App\Models\Selection;
use App\Models\Source;
use App\Models\Team;
use App\Models\Variable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use OwenIt\Auditing\Models\Audit;

/**
 * Class AuditService
 *
 * Handles the business logic for audit trail management including
 * retrieval, transformation, filtering, and pagination of audit data.
 */
class AuditService
{
    /**
     * Cache duration in minutes
     */
    private const CACHE_DURATION = 5;

    /**
     * Get all audits related to a specific project.
     */
    public function getProjectAudits(Project $project): Collection
    {
        return Cache::remember(
            "project_audits_{$project->id}",
            Carbon::now()->addMinutes(self::CACHE_DURATION),
            function () use ($project) {
                return $this->collectProjectAudits($project);
            }
        );
    }

    /**
     * Get all audits for a project as a flat list, without filtering or pagination.
     * Reuses the same cache entry as getProjectAudits().
     */
    public function getAllProjectAudits(Project $project): Collection
    {
        return $this->getProjectAudits($project)
            ->sortByDesc('created_at_timestamp')
            ->values();
    }

    /**
     * Filter audits based on provided criteria.
     */
    public function filterAudits(Collection $audits, array $filters): Collection
    {
        return $audits->filter(function ($audit) use ($filters) {
            $matchesQuery = empty($filters['query']) ||
                $this->auditMatchesQuery($audit, $filters['query']);

            $matchesModel = empty($filters['models']) ||
                in_array($audit['model'], $filters['models']);

            $matchesEvent = empty($filters['events']) ||
                in_array($audit['event'], $filters['events']);

            $matchesDateRange = $this->auditMatchesDateRange($audit, $filters);

            return $matchesQuery && $matchesModel && $matchesEvent && $matchesDateRange;
        });
    }

    /**
     * Transform audit data into a standardized format.
     */
    public function transformAudits(Collection $auditData, string $model, array $exceptFields): Collection
    {
        return $auditData->map(function ($audit) use ($model, $exceptFields) {
            $createdAt = Carbon::parse($audit->created_at);

            return [
                'id' => $audit->id,
                'event' => $audit->event,
                'model' => $model,
                'user_id' => optional($audit->user)->email,
                'user_profile_picture' => optional($audit->user)->profile_photo_url,
                'created_at' => $createdAt->format(config('audit.date_format')),
                'created_at_timestamp' => $createdAt->timestamp,
                'old_values' => collect($audit->old_values)->except($exceptFields)->toArray(),
                'new_values' => collect($audit->new_values)->except($exceptFields)->toArray(),
            ];
        });
    }

    /**
     * Create a paginator for audit data.
     */
    public function paginateAudit(Collection $audits, Request $request): LengthAwarePaginator
    {
        $page = $request->get('page', 1);
        $perPage = config('audit.per_page', 20);

        return new LengthAwarePaginator(
            $audits->forPage($page, $perPage),
            $audits->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * Get the counts of audits for each model type.
     */
    public function getAuditCountsByModel(Collection $audits): array
    {
        return $audits->groupBy('model')->map->count()->toArray();
    }

    /**
     * Collect all audit types for a project.
     */
    private function collectProjectAudits(Project $project): Collection
    {
        $allAudits = collect();

        // Project audits
        $allAudits = $allAudits->concat(
            $this->transformAudits(
                $project->audits,
                'Project',
                ['id', 'project_id', 'creating_user_id']
            )
        );

        // Source and Selection audits
        foreach ($project->sources as $source) {
            $allAudits = $allAudits->concat(
                $this->transformAudits(
                    $source->audits,
                    'Source',
                    ['id', 'project_id', 'creating_user_id']
                )
            );

            foreach ($source->selections as $selection) {
                $allAudits = $allAudits->concat(
                    $this->transformSelectionAudits($selection)
                );
            }
        }

        // Codebook and Code audits
        foreach ($project->codebooks as $codebook) {
            foreach ($codebook->codes as $code) {
                $allAudits = $allAudits->concat(
                    $this->transformAudits(
                        $code->audits,
                        'Code',
                        ['id', 'codebook_id', 'creating_user_id']
                    )
                );
            }
        }

        // Note audits
        foreach ($project->notes as $note) {
            $allAudits = $allAudits->concat(
                $this->transformAudits(
                    $note->audits,
                    'Note',
                    ['id', 'project_id', 'creating_user_id']
                )
            );
        }

        // Variable audits
        foreach ($project->variables as $variable) {
            $allAudits = $allAudits->concat(
                $this->transformAudits(
                    $variable->audits,
                    'Variable',
                    ['id', 'project_id', 'source_id', 'guid']
                )
            );
        }

        // Team audits (store, update, destroy, makeOwner)
        if ($project->team_id) {
            $team = Team::withTrashed()->find($project->team_id);
            if ($team) {
                $allAudits = $allAudits->concat(
                    $this->transformAudits(
                        $team->audits,
                        'Project',
                        ['id', 'personal_team']
                    )
                );
            }
        }

        // Audits for hard-deleted models (not reachable via relationships)
        $allAudits = $allAudits->concat($this->collectOrphanedDeletedAudits($project));

        return $allAudits
            ->filter()
            // ->sortByDesc('created_at_timestamp')
            ->values();
    }

    /**
     * Check if an audit matches the search query.
     */
    private function auditMatchesQuery(array $audit, string $query): bool
    {
        $searchableValues = collect($audit['new_values'])
            ->merge($audit['old_values'])
            ->filter()
            ->implode(' ');

        return stripos($searchableValues, $query) !== false;
    }

    /**
     * Check if an audit falls within the specified date range.
     */
    private function auditMatchesDateRange(array $audit, array $filters): bool
    {
        $auditDate = Carbon::createFromTimestamp($audit['created_at_timestamp']);

        if (! empty($filters['dates']['before']) && $auditDate->isAfter($filters['dates']['before'])) {
            return false;
        }

        if (! empty($filters['dates']['after']) && $auditDate->isBefore($filters['dates']['after'])) {
            return false;
        }

        if (! empty($filters['dates']['between'])) {
            [$start, $end] = $filters['dates']['between'];

            return $auditDate->between($start, $end);
        }

        return true;
    }

    /**
     * Transform selection audits with special handling for code references.
     *
     * @param  mixed  $selection
     */
    private function transformSelectionAudits($selection): Collection
    {
        $audits = $this->transformAudits(
            $selection->audits,
            'Selection',
            ['id', 'source_id', 'creating_user_id', 'project_id', 'start_position', 'end_position']
        );

        return $audits->map(function ($audit) use ($selection) {
            if (isset($audit['old_values']['code_id'])) {
                $audit['old_values']['code_id'] = optional($selection->code)->name ?? $audit['old_values']['code_id'];
            }
            if (isset($audit['new_values']['code_id'])) {
                $audit['new_values']['code_id'] = optional($selection->code)->name ?? $audit['new_values']['code_id'];
            }

            return $audit;
        });
    }

    /**
     * Collect audit records for hard-deleted models that are no longer reachable
     * via Eloquent relationships. The owenIt/auditing package persists audit rows
     * (including the 'deleted' event) in the audits table even after the auditable
     * model is removed from the database, so we query the audits table directly.
     */
    public function collectOrphanedDeletedAudits(Project $project): Collection
    {
        $allAudits = collect();

        // Models that carry project_id directly as an attribute
        $modelMap = [
            Source::class => [
                'label' => 'Source',
                'except' => ['id', 'project_id', 'creating_user_id'],
                'existingIds' => $project->sources->pluck('id'),
            ],
            Note::class => [
                'label' => 'Note',
                'except' => ['id', 'project_id', 'creating_user_id'],
                'existingIds' => $project->notes->pluck('id'),
            ],
            Variable::class => [
                'label' => 'Variable',
                'except' => ['id', 'project_id', 'source_id', 'guid'],
                'existingIds' => $project->variables->pluck('id'),
            ],
            Selection::class => [
                'label' => 'Selection',
                'except' => ['id', 'source_id', 'creating_user_id', 'project_id', 'start_position', 'end_position'],
                'existingIds' => $project->sources->flatMap->selections->pluck('id'),
            ],
        ];

        foreach ($modelMap as $modelClass => $config) {
            $orphans = Audit::where('auditable_type', $modelClass)
                ->where('event', 'deleted')
                ->whereNotIn('auditable_id', $config['existingIds'])
                ->where('old_values->project_id', $project->id)
                ->get();

            $allAudits = $allAudits->concat(
                $this->transformAudits($orphans, $config['label'], $config['except'])
            );
        }

        // Code: project relation is indirect via codebook_id
        $codebookIds = $project->codebooks->pluck('id');
        $existingCodeIds = $project->codebooks->flatMap->codes->pluck('id');

        foreach ($codebookIds as $codebookId) {
            $orphans = Audit::where('auditable_type', Code::class)
                ->where('event', 'deleted')
                ->whereNotIn('auditable_id', $existingCodeIds)
                ->where('old_values->codebook_id', $codebookId)
                ->get();

            $allAudits = $allAudits->concat(
                $this->transformAudits($orphans, 'Code', ['id', 'codebook_id', 'creating_user_id'])
            );
        }

        return $allAudits;
    }
}
