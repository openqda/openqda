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
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:qde,xml,qdc',
            'project_id' => 'required|exists:projects,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('file');
            $xmlContent = file_get_contents($file->getRealPath());

            // Validate XML content with DOMDocument
            $dom = new \DOMDocument();
            if (! @$dom->loadXML($xmlContent)) {
                throw new Exception('Invalid XML format: Not well-formed.');
            }

            $xml = new SimpleXMLElement($xmlContent);
            // Register necessary XML namespaces
            $this->registerNamespaces($xml);

            $codeBookElement = $this->getCodeBookElement($xml);
            if (empty($codeBookElement) || empty($codeBookElement->Codes)) {
                throw new Exception('Invalid XML format: CodeBook or Codes element missing.');
            }

            $origin = (string) $codeBookElement['origin'];
            $codesToProcess = $this->checkForUniqueProblemsInsideSoftwares($origin, $codeBookElement->Codes->children());

            DB::beginTransaction();

            $codebook = $this->createCodebook($request, $codeBookElement, $origin);
            $this->processCodes($codesToProcess, $codebook->id);

            DB::commit();

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
     * Registers necessary XML namespaces.
     *
     * @return void
     */
    private function registerNamespaces(SimpleXMLElement $xml)
    {
        $xml->registerXPathNamespace('ns', 'urn:QDA-XML:codebook:0:4');
        $xml->registerXPathNamespace('proj', 'urn:QDA-XML:project:1.0');
    }

    /**
     * Retrieves the CodeBook element from the XML.
     *
     * @return SimpleXMLElement|null
     */
    private function getCodeBookElement(SimpleXMLElement $xml)
    {
        $paths = ['//ns:CodeBook', '//proj:CodeBook', '//CodeBook', '//Project/CodeBook', '//*[local-name()="CodeBook"]'];
        foreach ($paths as $path) {
            $elements = $xml->xpath($path);
            if (! empty($elements)) {
                return $elements[0];
            }
        }

        return null;
    }

    /**
     * Creates a new Codebook model instance.
     *
     * @param  string  $origin
     * @return Codebook
     */
    private function createCodebook(Request $request, SimpleXMLElement $codeBookElement, $origin)
    {
        $codebook = new Codebook();
        $codebook->project_id = $request->project_id;
        $codebook->name = (string) $origin ? 'Codebook from '.$origin : 'Unnamed Codebook';
        $codebook->description = (string) $codeBookElement->Description ?: '';
        $codebook->properties = [
            'sharedWithPublic' => false,
            'sharedWithTeams' => false,
        ];
        $codebook->creating_user_id = auth()->id();
        $codebook->save();

        return $codebook;
    }

    /**
     * Recursively processes and saves codes.
     *
     * @param  SimpleXMLElement[]  $xmlCodes
     * @param  int  $codebookId
     * @param  string|null  $parentId
     * @return void
     */
    private function processCodes($xmlCodes, $codebookId, $parentId = null)
    {
        foreach ($xmlCodes as $xmlCode) {
            $code = new Code();
            $code->id = (string) Str::uuid();
            $code->name = (string) $xmlCode['name'] ?: 'Unnamed Code';
            $code->color = (string) $xmlCode['color'] ?: '';
            $code->codebook_id = $codebookId;
            $code->description = (string) $xmlCode->Description ?: '';
            $code->parent_id = $parentId;
            $code->save();

            if ($xmlCode->Code) {
                $this->processCodes($xmlCode->Code, $codebookId, $code->id);
            }
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
     * @param  int  $id
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
            'Content-Disposition' => 'attachment; filename="'.$codebook->name.'.qdc"',
        ]);
    }

    /**
     * Helper function to recursively add codes to an XML element.
     *
     * @param  SimpleXMLElement  $codesXml
     * @param  \Illuminate\Database\Eloquent\Collection  $codes
     * @param  string|null  $parentId
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
}
