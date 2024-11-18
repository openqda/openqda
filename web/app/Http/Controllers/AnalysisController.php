<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowAnalysisPage;
use App\Models\Code;
use App\Models\Project;
use App\Models\Source;
use App\Traits\BuildsNestedCode;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AnalysisController extends Controller
{
    use BuildsNestedCode;

    /**
     * Show analysis page for a specific project.
     */
    public function show(ShowAnalysisPage $request, Project $project): Response
    {
        try {
            $sources = Source::where('project_id', $project->id)->get();
            $codebooks = $project->codebooks()->get();

            // Fetch and build nested codes structure
            $rootCodes = Code::with(['childrenRecursive', 'codebook', 'selections'])
                ->whereIn('codebook_id', $codebooks->pluck('id'))
                ->get();

            $allCodes = $rootCodes->map(fn ($code) => $this->buildNestedCode($code));
            // collaboration
            if ($project->team) {
                $team = $project->team->load('users');
                $teamMembers = $team->users;
            } else {
                $teamMembers = [];
            }

            return Inertia::render('AnalysisPage', [
                'sources' => $sources,
                'codes' => $allCodes,
                'codebooks' => $codebooks,
                'project' => [
                    'name' => $project->name,
                    'description' => $project->description,
                    'created_at' => $project->created_at,
                    'id' => $project->id,
                    'projectId' => $project->id,
                ],
                'teamMembers' => $teamMembers ? $teamMembers->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'profile_photo_url' => $user->profile_photo_url,
                    ];
                }) : [],
            ]);

        } catch (Throwable $e) {
            Log::error('Error loading analysis page', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return Inertia::render('Error', [
                'message' => 'Failed to load analysis page. Please try again.',
            ]);
        }
    }
}
