<?php

namespace App\Http\Controllers;

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

    public function store(Request $request, Project $project, Source $source, Code $code)
    {
        $data = $request->validate([
            'text' => 'required|string',
            'start_position' => 'required|integer',
            'end_position' => 'required|integer',
            'description' => 'sometimes|string', // optional
        ]);

        $data['id'] = $request->input('textId');
        $data['code_id'] = $code->id;
        $data['source_id'] = $source->id;
        $data['creating_user_id'] = Auth::id();
        $data['project_id'] = $project->id;


        $selection = Selection::create($data);

        return response()->json(['message' => 'Selection saved successfully!', 'selection' => $selection]);
    }


    public function changeCode(Request $request, Project $project, Source $source, Code $code, Selection $selection)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'newCodeId' => 'required|exists:codes,id' // Assuming "codes" is the table name for the codes.
        ]);

        // Update the selection's code_id with the newCodeId from the request
        $selection->code_id = $validatedData['newCodeId'];
        $selection->modifying_user_id = auth()->id();
        $selection->updated_at = now();;
        $selection->save();

        // Return a response
        return response()->json(['success' => true, 'message' => 'Code updated successfully']);
    }


    public function destroy(Request $request, Project $project, Source $source, Code $code, Selection $selection)
    {


        /*
        I need source id - code id - and selection id
        This is because I need to delete a selection that is belonging to a code, that is belonging to a document
        This is good practice so you're SURE you delete a precise selection
        TODO create policy for selection
        // It will automatically look for a method named 'delete' in the policy for the Source model
        if (!Gate::allows('delete', $source)) {
            return response()->json(['success' => false, 'message' => 'Not allowed'], 403);
        }
        */
        try {

            // Delete the database record
            $selection->delete();

            return response()->json(['success' => true, 'message' => 'Text deleted successfully from code']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }


    }
}
