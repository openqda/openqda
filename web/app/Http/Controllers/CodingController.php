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
use App\Traits\SourceExists;
use App\Traits\ValidatesStoragePath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CodingController extends Controller
{
    use BuildsNestedCode, SourceExists, ValidatesStoragePath;

    /**
     * Store a newly created code.
     *
     * @return JsonResponse
     */
    public function store(StoreCodeRequest $request, Project $project)
    {
        // Now you can create the code
        $code = new Code;
        $code->name = $request->input('title');
        $code->description = $request->input('description');
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
        $allSources = Source::where('project_id', $projectId)->with('variables')
            ->whereHas('variables', function ($query) {
                $query->where('name', 'isLocked')
                    ->where('boolean_value', true);
            })
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
                    'variables' => $source->transformVariables(),
                    'converted' => (bool) $converted,
                    'exists' => $exists,
                    'status' => $status && $status->status ? $status->status : 'unknown',
                    'failed' => $status && $status->status === 'failed',
                ];
            });

        // Handle codebook creation/retrieval
        $codebook = $project->codebooks()->first() ?? $this->createDefaultCodebook($project, $request);
        $codebooks = $project->codebooks()->get();

        // Get root codes with necessary relationships
        $rootCodes = Code::with(['childrenRecursive', 'codebook'])
            ->whereIn('codebook_id', $codebooks->pluck('id'))
            ->whereNull('parent_id')
            ->get();

        // collaboration
        if ($project->team) {
            $team = $project->team->load('users');
            $teamMembers = $team->users;
        } else {
            $teamMembers = [];
        }

        // Get source (either from request or latest locked one)
        $source = $request->has('source') && $request->source
            ? Source::where('project_id', $project->id)->where('id', $request->source)->firstOrFail()
            : Source::where('project_id', $project->id)
                ->whereHas('variables', function ($query) {
                    $query->where('name', 'isLocked')
                        ->where('boolean_value', true);
                })
                ->latest('created_at')
                ->first();

        // Build nested codes structure
        $allCodes = $rootCodes->map(fn ($code) => $this->buildNestedCode($code, $source ? $source->id : null));

        // Get content if source exists, otherwise return minimal data for the view
        if ($source && $source->converted) {
            $validatedPath = $this->validateStoragePath($source->converted->path);
            if ($validatedPath !== null) {
                $content = file_get_contents($validatedPath);
                $source->content = $content;
            }
            $source->variables = $source->transformVariables();
        }

        return Inertia::render('CodingPage', [
            'source' => $source,
            'sources' => $allSources,
            'codebooks' => $codebooks,
            'allCodes' => $allCodes,
            'projectId' => $projectId,
            'teamMembers' => $teamMembers ? $teamMembers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url,
                ];
            }) : [],
        ]);
    }

    /**
     * Remove the specified code and its selections from storage.
     *
     * @return JsonResponse
     */
    public function destroy(DestroyCodeRequest $request, Project $project, Source $source, Code $code, bool $isRecursiveCall = false)
    {
        try {
            // Use a database transaction to ensure atomicity
            return \DB::transaction(function () use ($request, $project, $source, $code, $isRecursiveCall) {
                // Get all child codes that have this code as their parent
                $childCodes = Code::where('parent_id', $code->id)->get();

                // Recursively delete all children
                foreach ($childCodes as $childCode) {
                    $this->destroy($request, $project, $source, $childCode, true);
                }

                if ($code->parent_id) {
                    $code->parent_id = null;
                    $code->save();
                }

                // Delete all associated selections
                $code->selections()->delete();

                // Delete the code itself
                $code->delete();

                // Only return JSON response for the initial call, not for recursive calls
                if (! $isRecursiveCall) {
                    return response()->json(['message' => 'Code and its selections successfully deleted']);
                }

                return true;
            });
        } catch (\Exception $e) {
            if (! $isRecursiveCall) {
                return response()->json(['error' => 'Failed to delete code: '.$e->getMessage()], 500);
            }
            throw $e;
        }
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

        if ($request->has('parent_id')) {
            $code->parent_id = $request->input('parent_id');
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

        // Return a success responsep
        return response()->json(['message' => 'Change successfully made']);
    }

    /**
     * Return the default codebook for a project.
     *
     * @return Codebook
     */
    private function createDefaultCodebook(Project $project, Request $request)
    {
        $codebook = new Codebook;
        $codebook->name = $project->name.' Codebook';
        $codebook->project_id = $project->id;
        $codebook->creating_user_id = $request->user()->id;
        $codebook->save();

        return $codebook;
    }
}
