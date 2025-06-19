<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyCodebookRequest;
use App\Http\Requests\StoreCodebookRequest;
use App\Http\Requests\UpdateCodebookRequest;
use App\Models\Code;
use App\Models\Codebook;
use App\Models\Project;

class CodebookController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCodebookRequest $request, Project $project)
    {
        try {

            // Create the codebook with validated data
            $codebook = new Codebook([
                'name' => $request->name,
                'description' => $request->description,
                'project_id' => $project->id,
                'creating_user_id' => $request->user()->id,
                'properties' => [
                    'sharedWithPublic' => $request->get('sharedWithPublic', false),
                    'sharedWithTeams' => $request->get('sharedWithTeams', false),
                ],
            ]);

            // Save the codebook
            $codebook->save();

            // Check if importing an existing codebook
            if ($request->import && $request->id) {
                $originalCodebookId = $request->id;
                $originalCodebook = Codebook::with('codes', 'codes.childrenRecursive')->findOrFail($originalCodebookId);
                $codesToImport = $originalCodebook->codes;

                // Create a mapping of old ID => new ID
                $idMapping = [];

                // First pass: Create all codes without parent relationships
                foreach ($codesToImport as $code) {
                    $newCode = new Code;
                    $newCode->name = $code->name;
                    $newCode->color = $code->color;
                    $newCode->codebook_id = $codebook->id;
                    $newCode->description = $code->description;
                    $newCode->parent_id = null; // We'll set this in the second pass
                    $newCode->save();

                    // Store the mapping of old ID to new ID
                    $idMapping[$code->id] = $newCode->id;
                }

                // Second pass: Update parent relationships using the mapping
                foreach ($codesToImport as $code) {
                    if (! empty($code->parent_id)) {
                        // Find the corresponding new code using our ID mapping
                        $newCode = Code::find($idMapping[$code->id]);
                        $newCode->parent_id = $idMapping[$code->parent_id];
                        $newCode->save();
                    }
                }
            }

            // Return a successful response with the newly created codebook data
            return response()->json(['message' => 'Codebook created successfully', 'codebook' => $codebook]);
        } catch (\Throwable $th) {

            // Handle any exceptions that occur during the creation process
            return response()->json(['error' => 'An error occurred while creating the codebook '.$th], 500);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCodebookRequest $request, $project, $codebookId)
    {
        try {
            $codebook = Codebook::findOrFail($codebookId);

            $sharedWithPublic = $request->get('sharedWithPublic', false);
            $sharedWithTeams = $request->get('sharedWithTeams', false);

            // Prepare updated properties, keeping existing code order if not provided
            $properties = $codebook->properties ?? [];
            $properties['sharedWithPublic'] = $sharedWithPublic;
            $properties['sharedWithTeams'] = $sharedWithTeams;

            // If 'code_order' is present in the request, update it, else keep the existing one
            if ($request->has('code_order')) {
                $newCodeOrder = $request->input('code_order'); // Expecting an array of UUIDs or code IDs
                $properties['code_order'] = $newCodeOrder;
            } else {
                // Preserve existing 'code_order' if it's already set
                $properties['code_order'] = $properties['code_order'] ?? [];
            }

            // Update the codebook's properties
            $codebook->update([
                'name' => $request->name,
                'description' => $request->description,
                'properties' => $properties,
            ]);

            return response()->json(['message' => 'Codebook updated successfully', 'codebook' => $codebook]);

        } catch (\Throwable $th) {
            return response()->json(['error' => 'An error occurred while updating the codebook: '.$th->getMessage()], 500);
        }
    }

    /**
     * Delete a codebook
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($project, $codebook, DestroyCodebookRequest $request)
    {
        $codebook = Codebook::find($codebook);
        try {
            $codebook->delete();

            return response()->json(['success' => true, 'message' => 'Codebook deleted']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()]);
        }

    }

    /**
     * Get paginated public codebooks
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPublicCodebooks(\Illuminate\Http\Request $request)
    {
        $perPage = $request->get('per_page', 10); // Default 10, allow 15/20
        $allowedPerPage = [10, 15, 20];
        
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }
        
        $codebooks = Codebook::where('properties->sharedWithPublic', true)
            ->with(['creatingUser:id,name,email'])
            ->withCount('codes')
            ->latest()
            ->paginate($perPage);
        
        return response()->json($codebooks);
    }

    /**
     * Search public codebooks by name or user email
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchPublicCodebooks(\Illuminate\Http\Request $request)
    {
        $query = $request->get('q');
        
        if (strlen($query) < 3) {
            return response()->json(['data' => []]);
        }
        
        $codebooks = Codebook::where('properties->sharedWithPublic', true)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhereHas('creatingUser', function($q) use ($query) {
                      $q->where('email', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%");
                  });
            })
            ->with(['creatingUser:id,name,email'])
            ->withCount('codes')
            ->take(20) // Limit results
            ->get();
        
        return response()->json(['data' => $codebooks]);
    }
}
