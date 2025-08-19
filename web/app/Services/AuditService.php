<?php

namespace App\Services;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
     * Filter audits based on provided criteria.
     */
    public function filterAudits(Collection $audits, array $filters): Collection
    {
        return $audits->filter(function ($audit) use ($filters) {
            $matchesQuery = empty($filters['query']) ||
                $this->auditMatchesQuery($audit, $filters['query']);

            $matchesModel = empty($filters['models']) ||
                in_array($audit['model'], $filters['models']);

            $matchesDateRange = $this->auditMatchesDateRange($audit, $filters);

            return $matchesQuery && $matchesModel && $matchesDateRange;
        });
    }

    /**
     * Transform audit data into a standardized format.
     */
    public function transformAudits(Collection $auditData, string $model, array $exceptFields): Collection
    {
        return $auditData->map(function ($audit) use ($model, $exceptFields) {
            return [
                'id' => $audit->id,
                'event' => $audit->event,
                'model' => $model,
                'user_id' => optional($audit->user)->email,
                'user_profile_picture' => optional($audit->user)->profile_photo_url,
                'created_at' => Carbon::parse($audit->created_at)->format(config('audit.date_format')),
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

        return $allAudits->filter()->sortByDesc('created_at');
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
        $auditDate = Carbon::parse($audit['created_at']); // Check the date format here

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
}
