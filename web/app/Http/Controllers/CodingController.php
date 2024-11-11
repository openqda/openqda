<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyCodeRequest;
use App\Http\Requests\StoreCodeRequest;
use App\Http\Requests\UpdateCodeRequest;
use App\Models\Code;
use App\Models\Codebook;
use App\Models\Project;
use App\Models\Source;
use App\Traits\BuildsNestedCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CodingController extends Controller
{
    use BuildsNestedCode;

    /**
     * Store a newly created code.
     *
     * @return JsonResponse
     */
    public function store(StoreCodeRequest $request, Project $project)
    {
        // Now you can create the code
        $code = new Code();
        $code->name = $request->input('title');
        $code->color = $request->input('color');
        $code->codebook_id = $request->input('codebook');
        if ($request->input('parent_id')) {
            $code->parent_id = $request->input('parent_id');
        }

        $code->save();

        return response()->json(['message' => 'Code successfully created', 'id' => $code->id], 201);
    }

    public function show(Request $request, Project $project)
    {
        $projectId = $project->id;
        $allSources = Source::where('project_id', $projectId)
            ->whereHas('variables', function ($query) {
                $query->where('name', 'isLocked')
                    ->where('boolean_value', true);
            })
            ->get();

        // Get source (either from request or latest locked one)
        $source = $request->has('source') && $request->source
            ? Source::findOrFail($request->source)
            : Source::where('project_id', $project->id)
                ->whereHas('variables', function ($query) {
                    $query->where('name', 'isLocked')
                        ->where('boolean_value', true);
                })
                ->latest('created_at')
                ->firstOrFail();

        // Get content if source exists, otherwise redirect
        if ($source) {
            $content = file_get_contents($source->converted->path);
            $source->content = $content;
        } else {
            return redirect()->route('source.index', ['project' =>  $projectId]);
        }

        $source->variables = $source->transformVariables();

        // Handle codebook creation/retrieval
        $codebook = $project->codebooks()->first() ?? $this->createDefaultCodebook($project, $request);
        $codebooks = $project->codebooks()->get();

        // Get root codes with necessary relationships
        $rootCodes = Code::with(['childrenRecursive', 'codebook'])
            ->whereIn('codebook_id', $codebooks->pluck('id'))
            ->whereNull('parent_id')
            ->get();

        // Build nested codes structure
        $allCodes = $rootCodes->map(fn ($code) => $this->buildNestedCode($code, $source->id));

        return Inertia::render('CodingPage', [
            'source' => $source,
            'sources' => $allSources,
            'codebooks' => $codebooks,
            'allCodes' => $allCodes,
            'projectId' => $projectId
        ]);
    }

    /**
     * Remove the specified code and its selections from storage.
     *
     * @return JsonResponse
     */
    public function destroy(DestroyCodeRequest $request, Project $project, Source $source, Code $code)
    {

        if ($code->parent_id) {
            $code->parent_id = null;
            $code->save();
        }

        // Delete all associated selections
        $code->selections()->delete();

        // Delete the code itself
        $code->delete();

        return response()->json(['message' => 'Code and its selections successfully deleted']);
    }

    /**
     * Update the code attributes.
     */
    public function updateAttribute(UpdateCodeRequest $request, Project $project, Code $code): JsonResponse
    {
        if ($request->has('color')) {
            $code->color = $request->input('color');
        }

        if ($request->has('title')) {
            $code->name = $request->input('title');
        }

        if ($request->has('description')) {
            $code->description = $request->input('description');
        }

        $code->save();

        return response()->json(['message' => 'Code updated successfully', 'code' => $code]);
    }

    /**
     * @return JsonResponse
     *                      Remove the parent of a code
     */
    public function removeParent(Request $request, Project $project, Source $source, Code $code)
    {
        // Remove the parent_id from the code
        $code->removeParent();

        // Return a success response
        return response()->json(['message' => 'Parent removed successfully']);
    }

    /**
     * @return JsonResponse
     *                      Move the code up the hierarchy by one level
     */
    public function upHierarchy(Request $request, Project $project, Source $source, Code $code)
    {

        // Update the parent_id from the code's parent
        $code->moveUpHierarchy();

        // Return a success response
        return response()->json(['message' => 'Change successfully made']);
    }
}
