<?php

namespace App\Http\Controllers;

use App\Models\Code;
use App\Models\Codebook;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use SimpleXMLElement;

class CodebookCodesController extends Controller
{
    /**
     * Imports a codebook and its codes from an XML file.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request)
    {
        // Validate and retrieve the uploaded file
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:qde,xml,qdc',
            'project_id' => 'required|exists:projects,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            // Load the XML content from the .qde file
            $file = $request->file('file');
            $xmlContent = file_get_contents($file->getRealPath());

            $xml = new SimpleXMLElement($xmlContent);

            // Register the default namespace if present
            $xml->registerXPathNamespace('ns', 'urn:QDA-XML:codebook:0:4');
            $xml->registerXPathNamespace('proj', 'urn:QDA-XML:project:1.0');

            // Ensure the CodeBook element is present (handle both namespaced and non-namespaced)
            $codeBookElements = $xml->xpath('//ns:CodeBook');
            if (empty($codeBookElements)) {
                $codeBookElements = $xml->xpath('//proj:CodeBook');
            }
            if (empty($codeBookElements)) {
                $codeBookElements = $xml->xpath('//CodeBook');
            }
            if (empty($codeBookElements)) {
                $codeBookElements = $xml->xpath('//Project/CodeBook');
            }
            if (empty($codeBookElements)) {
                $codeBookElements = $xml->xpath('//*[local-name()="CodeBook"]');
            }

            if (empty($codeBookElements) || empty($codeBookElements[0]->Codes)) {
                throw new Exception('Invalid XML format: CodeBook or Codes element missing.');
            }

            $codeBookElement = $codeBookElements[0];

            // Filter codes based on the origin of the software
            $origin = (string) $codeBookElement['origin'];
            $codesToProcess = $this->checkForUniqueProblemsInsideSoftwares($origin, $codeBookElement->Codes->children());

            DB::beginTransaction();

            // Create the codebook with auto-incrementing ID
            $codebook = new Codebook();
            $codebook->project_id = $request->project_id;
            $codebook->name = (string) $origin ? 'Codebook from'.$origin : 'Unnamed Codebook'; // Ensure name is not empty
            $codebook->description = (string) $codeBookElement->Description ?: ''; // Ensure description is not null
            $codebook->properties = [
                'sharedWithPublic' => false,
                'sharedWithTeams' => false,
            ];
            $codebook->creating_user_id = auth()->id();
            $codebook->save();

            // Helper function to recursively process codes
            $processCodes = function ($xmlCodes, $codebookId, $parentId = null) use (&$processCodes) {
                foreach ($xmlCodes as $xmlCode) {
                    $code = new Code();
                    $code->id = (string) Str::uuid(); // Generate a new UUID
                    $code->name = (string) $xmlCode['name'] ?: 'Unnamed Code'; // Ensure name is not empty
                    $code->color = (string) $xmlCode['color'] ?: ''; // Ensure color is not null
                    $code->codebook_id = $codebookId;
                    $code->description = (string) $xmlCode->Description ?: ''; // Ensure description is not null
                    $code->parent_id = $parentId;
                    $code->save();

                    // Recursively process sub-codes
                    if ($xmlCode->Code) {
                        $processCodes($xmlCode->Code, $codebookId, $code->id);
                    }
                }
            };

            // Process all codes in the codebook
            $processCodes($codesToProcess, $codebook->id);

            DB::commit();

            // Return a response
            return response()->json([
                'message' => 'Codebook and codes imported successfully',
                'codebook' => $codebook,
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Checks for unique problems inside different software and handles them.
     *
     * @param  string  $origin
     * @param  SimpleXMLElement[]  $codesToProcess
     * @return SimpleXMLElement[]
     */
    private function checkForUniqueProblemsInsideSoftwares($origin, $codesToProcess)
    {
        if (stripos($origin, 'NVivo') !== false) {
            return $this->handleNvivoSpecificIssues($codesToProcess);
        }
        // Add more conditions here for other software in the future...

        return $codesToProcess;
    }

    /**
     * Handles NVivo-specific issues in the XML import process.
     *
     * @param  SimpleXMLElement[]  $codesToProcess
     * @return SimpleXMLElement[]
     */
    private function handleNvivoSpecificIssues($codesToProcess)
    {
        $filteredCodes = [];
        foreach ($codesToProcess as $code) {
            if ((string) $code['name'] === 'Nodes' && (string) $code['isCodable'] === 'false') {
                // Add child codes to the list to be processed
                foreach ($code->Code as $childCode) {
                    $filteredCodes[] = $childCode;
                }
            } else {
                $filteredCodes[] = $code;
            }
        }

        return $filteredCodes;
    }

    /**
     * Exports a codebook and its codes to an XML file.
     *
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Helper function to recursively add codes to an XML element.
     *
     * @return void
     */
    private function addCodesToXml($codesXml, $codes, $parentId = null)
    {
        foreach ($codes as $code) {
            if ($code->parent_id == $parentId) {
                $codeXml = $codesXml->addChild('Code');
                $codeXml->addAttribute('guid', $code->id);
                $codeXml->addAttribute('name', $code->name);
                $codeXml->addAttribute('isCodable', 'true'); // Assuming all codes are codable
                $codeXml->addAttribute('color', $code->color ?: '#000000'); // Default color if not set
                if (! empty($code->description)) {
                    $codeXml->addChild('Description', $code->description);
                }
                $this->addCodesToXml($codeXml, $codes, $code->id);
            }
        }
    }

    /**
     * Validates XML content for illegal characters.
     *
     *
     * @param  string  $xmlContent
     * @return bool
     */
    private function isValidXmlContent($xmlContent)
    {
        // Define a regex pattern for detecting illegal XML characters, excluding valid escaped '&' characters
        $pattern = '/&(?!amp;|lt;|gt;|quot;|apos;)/u';

        // Return false if illegal characters are found, true otherwise
        return ! preg_match($pattern, $xmlContent);
    }
}
