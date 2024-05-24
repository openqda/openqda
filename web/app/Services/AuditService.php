<?php

namespace App\Services;

use App\Models\Codebook;
use App\Models\Project;
use App\Models\Source;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function getAudits(?Project $project = null)
    {

        $projectIds = ($project) ? [$project->id] : Auth::user()->projects()->withTrashed()->pluck('id');

        // Fetch Project Audits
        if ($project && ! $project->exists) {
            $projectAuditsData = $project->audits()->with('user')->get();
        } else {

            $projectAuditsData = collect();
            foreach ($projectIds as $projectId) {
                $project = Project::find($projectId);
                $projectAuditsData = $projectAuditsData->concat($project->audits()->with('user')->get());
            }
        }

        // Fetch Source Audits and Selections Audits
        $sourceAuditsData = Source::whereIn('project_id', $projectIds)
            ->withTrashed()
            ->with(['audits.user', 'selections.audits.user', 'selections.code'])
            ->get();

        // Fetch Code Audits
        $codebooks = Codebook::whereIn('project_id', $projectIds)
            ->with(['codes.audits.user'])
            ->get();

        $codeAuditsData = $codebooks->pluck('codes')->flatten(1);

        // Transform project audits
        $projectAudits = $this->transformAudits($projectAuditsData, 'Project', ['id', 'project_id', 'creating_user_id']);

        // Transform source audits
        $sourceAudits = $sourceAuditsData->flatMap(function ($source) {
            return $this->transformAudits($source->audits, 'Source', ['id', 'project_id', 'creating_user_id']);
        });

        // Transform selections audits within sources
        $selectionAudits = $sourceAuditsData->flatMap(function ($source) {
            return $source->selections->flatMap(function ($selection) {
                $audits = $this->transformAudits($selection->audits, 'Selection', ['id', 'source_id', 'creating_user_id', 'project_id', 'start_position', 'end_position']);

                // Update the code_id within the audits
                $audits->transform(function ($audit) use ($selection) {
                    if (isset($audit['old_values']['code_id'])) {
                        $audit['old_values']['code_id'] = $selection->code->name ?? $audit['old_values']['code_id'];
                    }
                    if (isset($audit['new_values']['code_id'])) {
                        $audit['new_values']['code_id'] = $selection->code->name ?? $audit['new_values']['code_id'];
                    }

                    return $audit;
                });

                return $audits;
            });
        });

        // Transform code audits
        $codeAudits = $codeAuditsData->flatMap(function ($code) {
            return $this->transformAudits($code->audits, 'Code', ['id', 'codebook_id']);
        });

        // Transform code audits
        $codebookAudits = $codebooks->flatMap(function ($codebook) {
            return $this->transformAudits($codebook->audits, 'Codebook', ['id', 'creating_user_id', 'project_id']);
        });

        // Concatenate, sort, and format
        return $projectAudits->concat($sourceAudits)->concat($selectionAudits)->concat($codeAudits)->concat($codebookAudits)
            ->sortByDesc('created_at')
            ->map([$this, 'formatAuditDates'])
            ->values();
    }

    /**
     * convert audits to a more readable format
     *
     * @param  mixed  $auditData
     * @param  mixed  $model
     * @param  mixed  $exceptFields
     * @return mixed
     */
    public function transformAudits($auditData, $model, $exceptFields)
    {
        return $auditData->map(function ($audit) use ($model, $exceptFields) {
            return [
                'id' => $audit->id,
                'event' => $audit->event,
                'model' => $model,
                'user_id' => optional($audit->user)->email,
                'user_profile_picture' => optional($audit->user)->profile_photo_url,
                'created_at' => $audit->created_at,
                'old_values' => collect($audit->old_values)->except($exceptFields)->toArray(),
                'new_values' => collect($audit->new_values)->except($exceptFields)->toArray(),
            ];
        });
    }

    public function formatAuditDates($audit)
    {
        if ($audit['created_at'] instanceof \DateTime) {
            $audit['created_at'] = $audit['created_at']->format('d.m.Y H:i');
        } elseif (is_string($audit['created_at'])) {
            $audit['created_at'] = Carbon::parse($audit['created_at'])->format('d.m.Y H:i');
        }

        return $audit;
    }

    /**
     * @param  Illuminate\Http\Request  $request
     */
    public function paginateAudit($allAudits, Request $request): ?LengthAwarePaginator
    {
        if ($allAudits->isEmpty()) {
            $paginator = null;

        } else {
            $perPage = 20;
            $currentPage = $request->get('page', 1);
            $total = count($allAudits);
            $start = ($currentPage - 1) * $perPage;
            $currentData = $allAudits->slice($start, $perPage);

            $paginator = new LengthAwarePaginator($currentData, $total, $perPage, $currentPage, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        }

        return $paginator;
    }
}
