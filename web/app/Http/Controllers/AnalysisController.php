<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowAnalysisPage;
use App\Models\Code;
use App\Models\Note;
use App\Models\Project;
use App\Models\Selection;
use App\Models\Source;
use App\Traits\BuildsNestedCode;
use App\Traits\SourceExists;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AnalysisController extends Controller
{
    use BuildsNestedCode;
    use SourceExists;

    /**
     * Show analysis page for a specific project.
     */
    public function show(ShowAnalysisPage $request, Project $project): Response
    {
        $sources = Source::where('project_id', $project->id)
            ->get()
            ->map(function ($source) {
                $converted = $source->converted;
                $status = $source->sourceStatuses()->latest()->first();
                $source->status = $status ? $status->status : 'pending';
                $exists = $this->sourceExists($source);

                return [
                    'id' => $source->id,
                    'name' => $source->name,
                    'type' => $source->type,
                    'user' => $source->creatingUser->name,
                    'userPicture' => $source->creatingUser->profile_photo_url,
                    'date' => $source->created_at->toDateString(),
                    'selectionsCount' => $source->selections()->count(),
                    'converted' => (bool) $converted,
                    'exists' => $exists,
                    'status' => $status && $status->status ? $status->status : 'unknown',
                    'failed' => $status && $status->status === 'failed',
                ];
            });

        $codebooks = $project->codebooks()->get();

        // Fetch and build nested codes structure
        $rootCodes = Code::with(['childrenRecursive', 'codebook', 'selections'])
            ->whereIn('codebook_id', $codebooks->pluck('id'))
            ->get();

        $allCodes = $rootCodes->map(fn ($code) => $this->buildNestedCode($code, null, false));

        // get all relevant noes, could be performance heavy
        // se we might get them lazily in the future
        $userId = Auth::id();
        $notes = Note::where('project_id', $project->id)
            ->whereIn('type', ['project', 'source', 'code', 'selection'])
            ->where(function ($query) use ($userId) {
                // 1.1. ...visible for all team members
                $query->where('visibility', 1)
                      // 1.2. ...or my own notes
                    ->orWhere(function ($query) use ($userId) {
                        $query->where('visibility', 0)
                            ->where('creating_user_id', $userId);
                    });
            })
            ->get();

        return Inertia::render('AnalysisPage', [
            'sources' => $sources,
            'codes' => $allCodes,
            'codebooks' => $codebooks,
            'notes' => $notes,
            'project' => [
                'name' => $project->name,
                'description' => $project->description,
                'created_at' => $project->created_at,
                'id' => $project->id,
                'projectId' => $project->id,
            ],
        ]);
    }

    public function getSelections(ShowAnalysisPage $request, Project $project)
    {
        try {
            $selections = Selection::where('project_id', $project->id)
                ->get(['id', 'code_id', 'source_id', 'text', 'start_position', 'end_position', 'creating_user_id', 'created_at', 'updated_at'])
                ->toArray();

            return response()->json([
                'selections' => $selections,
            ]);
        } catch (Throwable $e) {
            Log::error('Error loading analysis page selections', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to get segments: '.$e->getMessage()], 500);
        }
    }
}
