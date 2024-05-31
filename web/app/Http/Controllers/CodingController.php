<?php

namespace App\Http\Controllers;

use App\Models\Code;
use App\Models\Codebook;
use App\Models\Project;
use App\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CodingController extends Controller
{
    public function index()
    {

    }

    /**
     * Store a newly created code.
     *
     * @return JsonResponse
     */
    public function store(Request $request, Project $project)
    {
        // Validation for code creation can go here...

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

    /**
     * going into coding page
     *
     * @return \Illuminate\Http\RedirectResponse|\Inertia\Response
     */
    public function show(Request $request, Project $project)
    {
        $allSources = Source::where('project_id', $project->id)
            ->whereHas('variables', function ($query) {
                $query->where('name', 'isLocked')
                    ->where('boolean_value', true);
            })
            ->get();

        // Check if source is defined in the request
        if ($request->has('source') && $request->source) {
            $source = Source::findOrFail($request->source);
        } else {
            // Fetch the latest LOCKED source for the project if 'source' is not defined
            $source = Source::where('project_id', $project->id)
                ->whereHas('variables', function ($query) {
                    $query->where('name', 'isLocked')
                        ->where('boolean_value', true);
                })
                ->latest('created_at')
                ->first();
        }

        // If the source exists, get the content
        // otherwise redirect to the preparation page
        if ($source) {
            $content = file_get_contents($source->converted->path);
            $source->content = $content;
        } else {
            return redirect()->route('source.index', ['project' => $project->id]);
        }
        $source->variables = $source->transformVariables();

        // Check if a codebook exists for this project
        $codebook = $project->codebooks()->first();

        // Create a codebook if none exists
        if (is_null($codebook)) {
            $codebook = new Codebook();
            $codebook->name = 'Default Codebook';
            $codebook->project_id = $project->id;
            $codebook->creating_user_id = $request->user()->id;
            $codebook->save();
        }

        $codebooks = $project->codebooks()->get();

        // Fetch only root codes for all codebooks, including necessary relationships
        $rootCodes = Code::with(['childrenRecursive', 'codebook'])
            ->whereIn('codebook_id', $codebooks->pluck('id'))
            ->whereNull('parent_id')  // Fetch only root codes
            ->get();
        // Build nested structure for each root code
        $allCodes = $rootCodes->map(function ($rootCode) use ($source) {

            return $this->buildNestedCode($rootCode, $source->id);
        });

        return Inertia::render('CodingPage', [
            'source' => $source,
            'sources' => $allSources,
            'codebooks' => $codebooks,
            'allCodes' => $allCodes,
        ]);
    }

    /**
     * Remove the specified code and its selections from storage.
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, Project $project, Source $source, Code $code)
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
     * @return JsonResponse
     *                      Update the color of a code
     */
    public function updateColor(Request $request, Project $project, Code $code)
    {
        // Validate the request (ensure color is provided and is in the correct format)
        $validatedData = $request->validate([
            'color' => 'required|string|max:255',  // Adjust validation rules as necessary
        ]);

        // Update the color of the specified code
        $code->color = $request->input('color');
        $code->save();

        // Return a success response (you can also return the updated code if needed)
        return response()->json(['message' => 'Color updated successfully']);
    }

    /**
     * @return JsonResponse
     *                      Update the name of a code
     */
    public function updateTitle(Request $request, Project $project, Code $code)
    {
        // Validate the request (ensure color is provided and is in the correct format)
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',  // Adjust validation rules as necessary
        ]);

        // Update the color of the specified code
        $code->name = $request->input('title');
        $code->save();

        // Return a success response (you can also return the updated code if needed)
        return response()->json(['message' => 'Color title successfully changed']);
    }

    /**
     * @return JsonResponse
     *                      Update the description of a code
     */
    public function updateDescription(Project $project, Source $source, Code $code, Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'description' => 'required|string|max:500', // or any other validation rules you need
        ]);

        // Update the description
        $code->description = $request->input('description');
        $code->save();

        // Respond with success or any additional data if needed
        return response()->json([
            'message' => 'Description updated successfully',
            'description' => $code->description,
        ]);
    }

    /**
     * @return array
     *               format the codes for the front end
     */
    private function nestCodes($codes, $sourceId)
    {
        $nested = [];

        foreach ($codes as $code) {
            if (! $code->parent_id) {  // This means it's a root code without a parent
                $nestedCode = $this->buildNestedCode($code, $sourceId);
                $nested[] = $nestedCode;
            }
        }

        return $nested;
    }

    /**
     * @return array
     */
    private function buildNestedCode($code, $sourceId)
    {

        $nestedCode = [
            'id' => $code->id,
            'name' => $code->name,
            'color' => $code->color,
            'codebook' => $code->codebook->id,
            'description' => $code->description ?? '',
            'text' => $code->selectionsForSource($sourceId)->get()->map(function ($s) {
                return [
                    'id' => $s->id,
                    'text' => $s->text,
                    'start' => $s->start_position,
                    'end' => $s->end_position,
                ];
            })->toArray(),
            'children' => [],
        ];

        foreach ($code->children as $child) {
            $nestedCode['children'][] = $this->buildNestedCode($child, $sourceId);
        }

        return $nestedCode;
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
     */
    public function upHierarchy(Request $request, Project $project, Source $source, Code $code)
    {

        // Update the parent_id from the code's parent
        $code->moveUpHierarchy();

        // Return a success response
        return response()->json(['message' => 'Change successfully made']);
    }
}
