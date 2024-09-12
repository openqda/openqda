<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeCodeRequest;
use App\Http\Requests\DeleteSelectionRequest;
use App\Http\Requests\StoreSelectionRequest;
use App\Models\Code;
use App\Models\Project;
use App\Models\Selection;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SelectionController extends Controller
{
    public function index()
    {

    }

    /**
     * Store a newly created selection.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreSelectionRequest $request, Project $project, Source $source, Code $code)
    {
        $data = $request->validated();
        $data['id'] = $request->input('textId');
        $data['code_id'] = $code->id;
        $data['source_id'] = $source->id;
        $data['creating_user_id'] = Auth::id();
        $data['project_id'] = $project->id;

        $selection = Selection::create($data);

        return response()->json(['message' => 'Selection saved successfully!', 'selection' => $selection]);
    }

    /**
     * Edit the selection to a new code.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeCode(ChangeCodeRequest $request, Project $project, Source $source, Code $code, Selection $selection)
    {
        // Get the validated data from the request
        $validatedData = $request->validated();

        // Update the selection's code_id with the newCodeId from the request
        $selection->code_id = $validatedData['newCodeId'];
        $selection->modifying_user_id = auth()->id();
        $selection->updated_at = now();
        $selection->save();

        // Return a response
        return response()->json(['success' => true, 'message' => 'Code updated successfully']);
    }

    /**
     * Remove the specified selection.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(DeleteSelectionRequest $request, Project $project, Source $source, Code $code, Selection $selection)
    {

        try {
            // Ensure that the selection belongs to the correct code and source
            if ($selection->source_id != $source->id || $selection->code_id != $code->id) {
                return response()->json(['success' => false, 'message' => 'Selection does not match the given source or code'], 400);
            }

            // Delete the database record
            $selection->delete();

            return response()->json(['success' => true, 'message' => 'Text deleted successfully from code']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()]);
        }
    }
}
