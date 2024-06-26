<?php

namespace App\Http\Controllers;

use App\Models\Code;
use App\Models\Codebook;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CodebookController extends Controller
{
    public function index()
    {

    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Project $project)
    {

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        /**
         * Check if the user is authorized to create a codebook for the project
         */
        if (! Gate::allows('create', [Codebook::class, $project])) {
            abort(403, 'Unauthorized action.');
        }

        // If validation fails, return a response with errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

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

                foreach ($codesToImport as $code) {
                    $newCode = new Code();
                    $newCode->name = $code->name;
                    $newCode->color = $code->color;
                    $newCode->codebook_id = $codebook->id;
                    $newCode->description = $code->description;
                    // Save the parent-child relationship for this code
                    if (! empty($code['parent_id'])) {
                        $newCode->parent_id = $code->parent_id;
                    } else {
                        $newCode->parent_id = null;
                    }
                    $newCode->save();
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
    public function update(Request $request, $project, $codebook)
    {
        // First, validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        // If validation fails, return a response with errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $codebook = Codebook::find($codebook);

            // Get 'sharedWithPublic' and 'sharedWithTeams' from the request, default to false if not present
            $sharedWithPublic = $request->get('sharedWithPublic', false);
            $sharedWithTeams = $request->get('sharedWithTeams', false);

            // Prepare properties data
            $properties = [
                'sharedWithPublic' => $sharedWithPublic,
                'sharedWithTeams' => $sharedWithTeams,
            ];

            // Update the codebook with validated data
            $codebook->update([
                'name' => $request->name,
                'description' => $request->description,
                'properties' => $properties,
            ]);

            // Return a successful response with the updated codebook data
            return response()->json(['message' => 'Codebook updated successfully', 'codebook' => $codebook]);

        } catch (\Throwable $th) {
            // Handle any exceptions that occur during the update process
            return response()->json(['error' => 'An error occurred while updating the codebook'], 500);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($project, $codebook)
    {
        $codebook = Codebook::findOrFail($codebook);
        if (! Gate::allows('delete', [Codebook::class, $codebook])) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $codebook->delete();

            return response()->json(['success' => true, 'message' => 'Codebook deleted']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()]);
        }

    }
}
