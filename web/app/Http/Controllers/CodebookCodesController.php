<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Codebook;
use App\Models\Code;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use SimpleXMLElement;
use Exception;
use Illuminate\Support\Str;

class CodebookCodesController extends Controller
{
    public function import(Request $request)
    {
        // Validate and retrieve the uploaded file
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:qde,xml',
            'project_id' => 'required|exists:projects,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            // Load the XML file
            $file = $request->file('file');
            $xmlContent = file_get_contents($file->getRealPath());
            $xml = new SimpleXMLElement($xmlContent);

            // Ensure the CodeBook element is present
            if (!isset($xml->CodeBook) || !isset($xml->CodeBook->Codes)) {
                throw new Exception('Invalid XML format: CodeBook or Codes element missing.');
            }

            ray($xml);

            DB::beginTransaction();

            // Create the codebook
            $codebook = new Codebook();
            $codebook->project_id = $request->project_id;
            $codebook->creating_user_id = auth()->id();
            $codebook->name = (string)$xml->CodeBook['name'] ?? 'Unnamed Codebook';
            $codebook->description = (string)$xml->CodeBook->Description ?? '';
            $codebook->save();

            // Helper function to recursively process codes
            $processCodes = function($xmlCodes, $codebookId, $parentId = null) use (&$processCodes) {
                foreach ($xmlCodes->Code as $xmlCode) {
                    $code = new Code();
                    $code->id = Str::uuid(); // Generate a new UUID
                    $code->name = (string)$xmlCode['name'];
                    $code->color = (string)$xmlCode['color'];
                    $code->codebook_id = $codebookId;
                    $code->description = (string)$xmlCode->Description ?? '';
                    $code->parent_id = $parentId;
                    $code->save();

                    // Recursively process sub-codes
                    if (isset($xmlCode->Code)) {
                        $processCodes($xmlCode->Code, $codebookId, $code->id);
                    }
                }
            };

            // Process all codes in the codebook
            $processCodes($xml->CodeBook->Codes, $codebook->id);

            DB::commit();

            // Return a response
            return response()->json([
                'message' => 'Codebook and codes imported successfully',
                'codebook' => $codebook
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
