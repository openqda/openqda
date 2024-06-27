<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowAnalysisPage;
use App\Models\Code;
use App\Models\Project;
use App\Models\Source;
use Inertia\Inertia;

class AnalysisController extends Controller
{
    public function index()
    {

    }

    public function show(ShowAnalysisPage $request, Project $project)
    {
        $sources = Source::where('project_id', $project->id)->get();
        $codebooks = $project->codebooks()->get();

        // Fetch only root codes for all codebooks, including necessary relationships
        $rootCodes = Code::with(['childrenRecursive', 'codebook'])
            ->whereIn('codebook_id', $codebooks->pluck('id'))
            ->get();

        // Build nested structure for each root code
        $allCodes = $rootCodes->map(function ($rootCode) {
            return $this->buildNestedCode($rootCode);
        });

        return Inertia::render('AnalysisPage', [
            'sources' => $sources,
            'codes' => $allCodes,
            'codebooks' => $codebooks,
        ]);
    }

    /**
     * @return array
     */
    private function buildNestedCode($code)
    {
        $nestedCode = [
            'id' => $code->id,
            'name' => $code->name,
            'color' => $code->color,
            'codebook' => $code->codebook->id,
            'description' => $code->description ?? '',
            'children' => [],
            'text' => $code->selections()->get()->map(function ($s) {
                return [
                    'id' => $s->id,
                    'text' => $s->text,
                    'start' => $s->start_position,
                    'end' => $s->end_position,
                    'createdBy' => $s->creating_user_id, // resolve: User::find($s->creating_user_id)->name,
                    'createdAt' => $s->created_at,
                    'updatedAt' => $s->updated_at,
                    'source_id' => $s->source_id, // Assuming you have a source_id field
                ];
            })->toArray(),
        ];

        foreach ($code->children as $child) {
            $nestedCode['children'][] = $this->buildNestedCode($child);
        }

        return $nestedCode;
    }
}
