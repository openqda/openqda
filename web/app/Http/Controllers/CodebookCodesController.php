<?php

namespace App\Http\Controllers;

use App\Models\Code;
use App\Models\Codebook;
use Exception;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use SimpleXMLElement;


class CodebookCodesController extends Controller
{
    public function import(Request $request)
    {
        // Validate and retrieve the uploaded file
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:qde,xml,qdc',
            'project_id' => 'required|exists:projects,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            // Load the XML content from the .qde file
            $file = $request->file('file');
            $xmlContent = file_get_contents($file->getRealPath());
            $xml = new SimpleXMLElement($xmlContent);

            // Ensure the CodeBook element is present
            if (!isset($xml->CodeBook) || !isset($xml->CodeBook->Codes)) {
                throw new Exception('Invalid XML format: CodeBook or Codes element missing.');
            }

            DB::beginTransaction();

            // Create the codebook with auto-incrementing ID
            $codebook = new Codebook();
            $codebook->project_id = $request->project_id;
            ray($xml->CodeBook['name']);
            $codebook->name = (string)$xml->CodeBook['name'] ?: 'Unnamed Codebook'; // Ensure name is not empty
            $codebook->description = (string)$xml->CodeBook->Description ?: ''; // Ensure description is not null
            $codebook->properties = [
                'sharedWithPublic' => false,
                'sharedWithTeams' => false
            ];
            $codebook->creating_user_id = auth()->id();
            ray($xml->CodeBook->Description);
            $codebook->save();

            // Helper function to recursively process codes
            $processCodes = function ($xmlCodes, $codebookId, $parentId = null) use (&$processCodes) {
                foreach ($xmlCodes as $xmlCode) {
                    $code = new Code();
                    $code->id = (string)Str::uuid(); // Generate a new UUID
                    $code->name = (string)$xmlCode['name'] ?: 'Unnamed Code'; // Ensure name is not empty
                    $code->color = (string)$xmlCode['color'] ?: ''; // Ensure color is not null
                    $code->codebook_id = $codebookId;
                    $code->description = (string)$xmlCode->Description ?: ''; // Ensure description is not null
                    $code->parent_id = $parentId;
                    $code->save();

                    // Recursively process sub-codes
                    if ($xmlCode->Code) {
                        $processCodes($xmlCode->Code, $codebookId, $code->id);
                    }
                }
            };

            // Process all codes in the codebook
            $processCodes($xml->CodeBook->Codes->children(), $codebook->id);

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

     public function export($id)
    {
        $codebook = Codebook::with('codes')->findOrFail($id);

        $xml = new SimpleXMLElement('<CodeBook xmlns="urn:QDA-XML:codebook:1.0"/>');
        $xml->addAttribute('origin', config('app.name')); // Set the origin attribute
        $codesXml = $xml->addChild('Codes');

        $this->addCodesToXml($codesXml, $codebook->codes);

        $xmlContent = $xml->asXML();

        return Response::make($xmlContent, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="codebook.xml"',
        ]);
    }

    private function addCodesToXml($codesXml, $codes, $parentId = null)
    {
        foreach ($codes as $code) {
            if ($code->parent_id == $parentId) {
                $codeXml = $codesXml->addChild('Code');
                $codeXml->addAttribute('guid', $code->id);
                $codeXml->addAttribute('name', $code->name);
                $codeXml->addAttribute('isCodable', 'true'); // Assuming all codes are codable
                $codeXml->addAttribute('color', $code->color ?: '#000000'); // Default color if not set
                if (!empty($code->description)) {
                    $codeXml->addChild('Description', $code->description);
                }
                $this->addCodesToXml($codeXml, $codes, $code->id);
            }
        }
    }
}