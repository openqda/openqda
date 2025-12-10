<?php

namespace Tests\Controllers;

use App\Models\Code;
use App\Models\Codebook;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use SimpleXMLElement;
use Tests\TestCase;

class CodebookCodesControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generate XML dynamically based on structure array.
     */
    private function generateXml(array $structure): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><CodeBook xmlns="urn:QDA-XML:codebook:1.0"/>');
        
        if (isset($structure['origin'])) {
            $xml->addAttribute('origin', $structure['origin']);
        }
        
        if (isset($structure['description'])) {
            $xml->addChild('Description', $structure['description']);
        }
        
        if (isset($structure['codes'])) {
            $codesElement = $xml->addChild('Codes');
            $this->addCodesToXml($codesElement, $structure['codes']);
        }
        
        return $xml->asXML();
    }

    /**
     * Recursively add codes to XML element.
     */
    private function addCodesToXml(SimpleXMLElement $parent, array $codes): void
    {
        foreach ($codes as $codeData) {
            $code = $parent->addChild('Code');
            
            if (isset($codeData['guid'])) {
                $code->addAttribute('guid', $codeData['guid']);
            }
            if (isset($codeData['name'])) {
                $code->addAttribute('name', $codeData['name']);
            }
            if (isset($codeData['isCodable'])) {
                $code->addAttribute('isCodable', $codeData['isCodable']);
            }
            if (isset($codeData['color'])) {
                $code->addAttribute('color', $codeData['color']);
            }
            if (isset($codeData['description'])) {
                $code->addChild('Description', $codeData['description']);
            }
            
            // Handle nested codes
            if (isset($codeData['children'])) {
                $this->addCodesToXml($code, $codeData['children']);
            }
        }
    }

    /**
     * Create XML file from structure and keep it persistent.
     */
    private function createXmlFileFromStructure(array $structure, string $filename = 'test.xml'): UploadedFile
    {
        $content = $this->generateXml($structure);
        
        // Use sys_get_temp_dir() instead of tmpfile() to keep file persistent
        $tempPath = sys_get_temp_dir().'/'.uniqid('test_xml_', true).'.xml';
        file_put_contents($tempPath, $content);
        
        return new UploadedFile($tempPath, $filename, 'text/xml', null, true);
    }

    /**
     * Create raw XML file for malformed/invalid tests.
     */
    private function createRawXmlFile(string $content, string $filename = 'test.xml'): UploadedFile
    {
        // Use sys_get_temp_dir() instead of tmpfile() to keep file persistent
        $tempPath = sys_get_temp_dir().'/'.uniqid('test_xml_', true).'.xml';
        file_put_contents($tempPath, $content);
        
        return new UploadedFile($tempPath, $filename, 'text/xml', null, true);
    }

    /**
     * Test successful import of a valid codebook XML file.
     */
    public function test_import_codebook_with_valid_xml()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $structure = [
            'origin' => 'TestSoftware',
            'description' => 'Sample codebook for testing',
            'codes' => [
                [
                    'guid' => 'code-1',
                    'name' => 'Code 1',
                    'isCodable' => 'true',
                    'color' => '#FF5733',
                    'description' => 'First code description',
                ],
                [
                    'guid' => 'code-2',
                    'name' => 'Code 2',
                    'isCodable' => 'true',
                    'color' => '#33FF57',
                    'description' => 'Second code description',
                    'children' => [
                        [
                            'guid' => 'code-2-1',
                            'name' => 'Code 2.1',
                            'isCodable' => 'true',
                            'color' => '#3357FF',
                            'description' => 'Nested code',
                        ],
                    ],
                ],
            ],
        ];

        $this->actingAs($user);

        $response = $this->postJson(route('codebook-codes.import', ['project' => $project->id]), [
            'project_id' => $project->id,
            'file' => $this->createXmlFileFromStructure($structure),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'codebook' => [
                'id',
                'name',
                'description',
                'properties',
                'created_at',
                'updated_at',
            ],
        ]);

        $this->assertDatabaseHas('codebooks', [
            'name' => 'Codebook from TestSoftware',
            'project_id' => $project->id,
        ]);

        $this->assertDatabaseHas('codes', ['name' => 'Code 1']);
        $this->assertDatabaseHas('codes', ['name' => 'Code 2']);
        $this->assertDatabaseHas('codes', ['name' => 'Code 2.1']);
    }

    /**
     * Test import with invalid XML format.
     */
    public function test_import_codebook_with_invalid_xml_structure()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $invalidXml = '<?xml version="1.0" encoding="UTF-8"?><InvalidRoot><NotACodeBook>Invalid</NotACodeBook></InvalidRoot>';

        $this->actingAs($user);

        $response = $this->postJson(route('codebook-codes.import', ['project' => $project->id]), [
            'project_id' => $project->id,
            'file' => $this->createRawXmlFile($invalidXml),
        ]);

        $response->assertStatus(500);
        $response->assertJsonStructure(['error']);
        $this->assertStringContainsString('Invalid XML format', $response->json('error'));
    }

    /**
     * Test import with malformed XML.
     */
    public function test_import_codebook_with_malformed_xml()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $malformedXml = '<?xml version="1.0"?><CodeBook xmlns="urn:QDA-XML:codebook:1.0"><Codes><Code name="Unclosed"</Codes></CodeBook>';

        $this->actingAs($user);

        $response = $this->postJson(route('codebook-codes.import', ['project' => $project->id]), [
            'project_id' => $project->id,
            'file' => $this->createRawXmlFile($malformedXml),
        ]);

        $response->assertStatus(500);
        $response->assertJsonStructure(['error']);
    }

    /**
     * Test NVivo-specific handling (removes "Nodes" wrapper).
     */
    public function test_import_nvivo_codebook_handles_nodes_wrapper()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $structure = [
            'origin' => 'NVivo 12',
            'description' => 'NVivo exported codebook',
            'codes' => [
                [
                    'guid' => 'nodes-root',
                    'name' => 'Nodes',
                    'isCodable' => 'false',
                    'children' => [
                        [
                            'guid' => 'code-1',
                            'name' => 'Actual Code 1',
                            'isCodable' => 'true',
                            'color' => '#FF0000',
                            'description' => 'Extracted from wrapper',
                        ],
                        [
                            'guid' => 'code-2',
                            'name' => 'Actual Code 2',
                            'isCodable' => 'true',
                            'color' => '#00FF00',
                            'description' => 'Also extracted',
                        ],
                    ],
                ],
                [
                    'guid' => 'code-3',
                    'name' => 'Regular Code',
                    'isCodable' => 'true',
                    'color' => '#0000FF',
                    'description' => 'Outside wrapper',
                ],
            ],
        ];

        $this->actingAs($user);

        $response = $this->postJson(route('codebook-codes.import', ['project' => $project->id]), [
            'project_id' => $project->id,
            'file' => $this->createXmlFileFromStructure($structure),
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('codes', ['name' => 'Actual Code 1']);
        $this->assertDatabaseHas('codes', ['name' => 'Actual Code 2']);
        $this->assertDatabaseHas('codes', ['name' => 'Regular Code']);
        $this->assertDatabaseMissing('codes', ['name' => 'Nodes']);
    }

    /**
     * Test export functionality generates valid XML.
     *
     * @return void
     */
    public function test_export_codebook_generates_valid_xml()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'name' => 'Test Codebook',
            'creating_user_id' => $user->id,
        ]);

        $code1 = Code::factory()->create([
            'codebook_id' => $codebook->id,
            'name' => 'Parent Code',
            'color' => '#FF0000',
            'description' => 'Parent description',
        ]);

        $code2 = Code::factory()->create([
            'codebook_id' => $codebook->id,
            'name' => 'Child Code',
            'color' => '#00FF00',
            'description' => 'Child description',
            'parent_id' => $code1->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('codebook-codes.export', [
            'project' => $project->id,
            'codebook' => $codebook->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertHeader('Content-Disposition', 'attachment; filename="Test Codebook.qdc"');

        $xml = $response->getContent();
        $this->assertStringContainsString('<CodeBook', $xml);
        $this->assertStringContainsString('origin="'.config('app.name').'"', $xml);
        $this->assertStringContainsString('name="Parent Code"', $xml);
        $this->assertStringContainsString('name="Child Code"', $xml);
        $this->assertStringContainsString('color="#FF0000"', $xml);
    }

    /**
     * Test export with empty codebook.
     *
     * @return void
     */
    public function test_export_codebook_with_no_codes()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'name' => 'Empty Codebook',
            'creating_user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('codebook-codes.export', [
            'project' => $project->id,
            'codebook' => $codebook->id,
        ]));

        $response->assertStatus(200);
        $xml = $response->getContent();
        $this->assertStringContainsString('<Codes/>', $xml);
    }

    /**
     * Test updateCodeOrder successfully updates code order.
     *
     * @return void
     */
    public function test_update_code_order_successfully()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
        ]);

        $code1 = Code::factory()->create(['codebook_id' => $codebook->id]);
        $code2 = Code::factory()->create(['codebook_id' => $codebook->id]);
        $code3 = Code::factory()->create(['codebook_id' => $codebook->id]);

        $newOrder = [$code3->id, $code1->id, $code2->id];

        $this->actingAs($user);

        $response = $this->patchJson(route('codebook-codes.update-order', [
            'project' => $project->id,
            'codebook' => $codebook->id,
        ]), [
            'code_order' => $newOrder,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Code order updated successfully',
            'code_order' => $newOrder,
        ]);

        // Verify the code order was updated in the database
        $codebook->refresh();
        $this->assertEquals($newOrder, $codebook->getCodeOrder());
    }

    /**
     * Test updateCodeOrder with invalid data type.
     *
     * @return void
     */
    public function test_update_code_order_with_invalid_format()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->patchJson(route('codebook-codes.update-order', [
            'project' => $project->id,
            'codebook' => $codebook->id,
        ]), [
            'code_order' => 'not-an-array',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'Invalid code order format. Expected an array, got string',
        ]);
    }

    /**
     * Test updateCodeOrder with non-existent codebook.
     *
     * @return void
     */
    public function test_update_code_order_with_nonexistent_codebook()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->patchJson(route('codebook-codes.update-order', [
            'project' => $project->id,
            'codebook' => 'non-existent-id',
        ]), [
            'code_order' => [],
        ]);

        // Laravel will throw ModelNotFoundException which returns 404/500
        $this->assertTrue($response->status() >= 400);
    }

    /**
     * Test import preserves code hierarchy.
     */
    public function test_import_preserves_code_hierarchy()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $structure = [
            'origin' => 'Test',
            'codes' => [
                [
                    'name' => 'Parent Code',
                    'color' => '#FF0000',
                    'children' => [
                        [
                            'name' => 'Child Code',
                            'color' => '#00FF00',
                        ],
                    ],
                ],
            ],
        ];

        $this->actingAs($user);

        $response = $this->postJson(route('codebook-codes.import', ['project' => $project->id]), [
            'project_id' => $project->id,
            'file' => $this->createXmlFileFromStructure($structure),
        ]);

        $response->assertStatus(200);

        $parentCode = Code::where('name', 'Parent Code')->first();
        $childCode = Code::where('name', 'Child Code')->first();

        $this->assertNotNull($parentCode);
        $this->assertNotNull($childCode);
        $this->assertEquals($parentCode->id, $childCode->parent_id);
    }

    /**
     * Test import generates random colors for codes without colors.
     */
    public function test_import_generates_random_color_when_missing()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $structure = [
            'origin' => 'Test',
            'codes' => [
                [
                    'guid' => 'test-1',
                    'name' => 'No Color Code',
                    'isCodable' => 'true',
                    'description' => 'Code without color',
                ],
            ],
        ];

        $this->actingAs($user);

        $response = $this->postJson(route('codebook-codes.import', ['project' => $project->id]), [
            'project_id' => $project->id,
            'file' => $this->createXmlFileFromStructure($structure),
        ]);

        $response->assertStatus(200);

        $code = Code::where('name', 'No Color Code')->first();
        $this->assertNotNull($code);
        $this->assertStringStartsWith('rgba(', $code->color);
        $this->assertStringEndsWith(')', $code->color);
    }

    /**
     * Test export includes code descriptions.
     */
    public function test_export_includes_code_descriptions()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);
        $codebook = Codebook::factory()->create([
            'project_id' => $project->id,
            'creating_user_id' => $user->id,
        ]);

        Code::factory()->create([
            'codebook_id' => $codebook->id,
            'name' => 'Described Code',
            'description' => 'This is a test description',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('codebook-codes.export', [
            'project' => $project->id,
            'codebook' => $codebook->id,
        ]));

        $response->assertStatus(200);
        $xml = $response->getContent();
        $this->assertStringContainsString('<Description>This is a test description</Description>', $xml);
    }

    /**
     * Test import with missing CodeBook element.
     */
    public function test_import_codebook_with_missing_codebook_element()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $invalidXml = '<?xml version="1.0" encoding="UTF-8"?><SomeOtherRoot xmlns="urn:QDA-XML:something:1.0"><NotCodeBook><Codes><Code name="Test"/></Codes></NotCodeBook></SomeOtherRoot>';

        $this->actingAs($user);

        $response = $this->postJson(route('codebook-codes.import', ['project' => $project->id]), [
            'project_id' => $project->id,
            'file' => $this->createRawXmlFile($invalidXml),
        ]);

        $response->assertStatus(500);
        $response->assertJsonStructure(['error']);
        $this->assertStringContainsString('CodeBook or Codes element missing', $response->json('error'));
    }

    /**
     * Test import with missing Codes element.
     */
    public function test_import_codebook_with_missing_codes_element()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['creating_user_id' => $user->id]);

        $structure = [
            'origin' => 'Test',
            'description' => 'No codes element',
        ];

        $this->actingAs($user);

        $response = $this->postJson(route('codebook-codes.import', ['project' => $project->id]), [
            'project_id' => $project->id,
            'file' => $this->createXmlFileFromStructure($structure),
        ]);

        $response->assertStatus(500);
        $response->assertJsonStructure(['error']);
        $this->assertStringContainsString('CodeBook or Codes element missing', $response->json('error'));
    }
}