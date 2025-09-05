<?php

namespace App\Builder;

use App\Enums\ContentType;
use Illuminate\Support\Str;
use SimpleXMLElement;

/**
 * Class to build a REFI compliant .qde file (xml) for exporting a project.
 * Implements the builder pattern where you first add the content
 * and then run the build method to create the final product.
 */
class QdeFileBuilder
{
    private $xml;

    private $project;

    private $users = [];

    private $sources = [];

    private $selections = [];

    private $codebooks = [];

    private $codes = [];

    private $rootPath = '';

    private $basePath = '';

    private $sourcePath = '';

    public function __construct($rootPath, $basePath, $sourcePath)
    {
        $this->$rootPath = $rootPath;
        $this->$basePath = $basePath;
        $this->$sourcePath = $sourcePath;
    }

    // -------------------------------------------------------------------------
    // PUBLIC
    // -------------------------------------------------------------------------

    public function project($project)
    {
        $this->project = $project;

        return $this;
    }

    public function users($users)
    {
        $this->users = [];
        $this->usersCollection = $users;
        foreach ($users as $user) {
            $this->users[$user->id] = $user;
        }

        return $this;
    }

    public function sources($sources)
    {
        $this->sources = $sources;

        return $this;
    }

    public function codebooks($codebooks)
    {
        $this->codebooks = $codebooks;

        return $this;
    }

    public function build()
    {
        $this->xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><Project></Project>');
        $this->buildProject($this->xml, $this->project, $this->users);

        $users = $this->xml->addChild('Users');
        foreach ($this->usersCollection as $user) {
            $this->buildUser($users, $user);
        }

        $sources = $this->xml->addChild('Sources');
        foreach ($this->sources as $source) {
            $this->buildSource($sources, $source);
        }

        foreach ($this->codebooks as $codebook) {
            $this->buildCodebook($this->xml, $codebook);
        }

        return $this;
    }

    public function toXml()
    {
        return $this->xml->asXML();
    }

    // -------------------------------------------------------------------------
    // PRIVATE
    // -------------------------------------------------------------------------

    protected function buildProject($root, $project, $users)
    {
        $crateUser = $this->users[$project->creating_user_id];
        $root->addAttribute('name', $project->name);
        $root->addAttribute('description', $project->description);
        $root->addAttribute('origin', 'OpenQDA-x.y.z-commit-hash');
        $root->addAttribute('creatingUserId', $crateUser->guid);
        $root->addAttribute('creationDateTime', $project->created_at->toIso8601String());
        $root->addAttribute('modifiedDateTime', $project->updated_at->toIso8601String());

        return $this;
    }

    protected function buildUser($users, $user)
    {
        $userElement = $users->addChild('User');
        $userElement->addAttribute('id', $user['id']);
        $userElement->addAttribute('guid', $user['guid']);
        $userElement->addAttribute('name', $user['name']);
        $userElement->addAttribute('email', $user['email']);

        return $this;
    }

    protected function buildCodebook($root, $codebook)
    {
        $codebookElement = $root->addChild('Codebook');
        $codebookElement->addAttribute('name', $codebook->name);
        $codebookElement->addAttribute('description', $codebook->description);
        $crateUser = $this->users[$codebook->creating_user_id];
        $codebookElement->addAttribute('creatingUserId', $crateUser->guid);
        $codebookElement->addAttribute('creationDateTime', $codebook->created_at->toIso8601String());
        $codebookElement->addAttribute('modifiedDateTime', $codebook->updated_at->toIso8601String());
        foreach ($codebook->codes as $code) {
            $this->buildCode($codebookElement, $code);
        }

        return $this;
    }

    protected function buildCode($root, $code)
    {
        $codeElement = $root->addChild('Code');
        $codeElement->addAttribute('guid', $code->id);
        $codeElement->addAttribute('name', $code->name);
        $codeElement->addAttribute('description', $code->description);
        $codeElement->addAttribute('isCodable', 'true');
        $codeElement->addAttribute('color', $code->color);

        if ($code->children->count() > 0) {
            foreach ($code->children as $child) {
                $this->buildCode($codeElement, $child);
            }
        }

        return $this;
    }

    protected function buildSource($sources, $source)
    {
        $type = ContentTypeMap::type($source->type);
        $sourceElement = $sources->addChild($type);
        $sourceElement->addAttribute('guid', $source->id);
        $sourceElement->addAttribute('name', $source->name);

        $extension = pathinfo($source->upload_path, PATHINFO_EXTENSION);
        $isRich = in_array(strtolower($extension), ['rtf', 'doc', 'docx', 'odt']);
        $pathName = ContentTypeMap::path($source->type, $isRich);
        $sourceElement->addAttribute($pathName, 'internal://sources/'.$source->id.'.'.$extension);
        $sourceElement->addAttribute('creationDateTime', $source->created_at->toIso8601String());
        $sourceElement->addAttribute('modifiedDateTime', $source->updated_at->toIso8601String());
        $crateUser = $this->users[$source->creating_user_id];
        $sourceElement->addAttribute('creatingUserId', $crateUser->guid);
        foreach ($source->selections as $selection) {
            $this->addSelection($sourceElement, $selection);
        }

        return $this;
    }

    protected function addSelection($sourceElement, $selection, $type = 'PlainTextSelection')
    {
        $crateUser = $this->users[$selection->creating_user_id];
        $selectionElement = $sourceElement->addChild($type);
        $selectionElement->addAttribute('guid', $selection->id);
        $selectionElement->addAttribute('startPosition', $selection->start_position);
        $selectionElement->addAttribute('endPosition', $selection->end_position);
        $selectionElement->addAttribute('creatingUser', $crateUser->guid);
        $creationDateTime = $selection->created_at->toIso8601String();
        $selectionElement->addAttribute('creationDateTime', $creationDateTime);
        $selectionElement->addAttribute('modifiedDateTime', $selection->updated_at->toIso8601String());
        $coding = $selectionElement->addChild('Coding');
        $coding->addAttribute('guid', Str::uuid()->toString());
        $coding->addAttribute('creatingUser', $crateUser->guid);
        $coding->addAttribute('creationDateTime', $creationDateTime);
        $code = $coding->addChild('CodeRef');
        $code->addAttribute('targetGUID', $selection->code_id);

        return $this;
    }

    protected function getUser($id) {}
}

/**
 * Utility methods to map content types to xml node and attribute names
 */
class ContentTypeMap
{
    /**
     * Get the xml node name for a given content type
     *
     * @param  ContentType  $type  The content type
     */
    public static function type($type)
    {
        return match ($type) {
            ContentType::TEXT => 'TextSource',
            ContentType::PICTURE => 'Picture',
            ContentType::PDF => 'PDFDocument',
            ContentType::AUDIO => 'Audio',
            ContentType::VIDEO => 'Video',
            default => 'UnknownSource',
        };
    }

    /**
     * Get the xml attribute name for the path of a given content type
     *
     * @param  ContentType  $type  The content type
     * @param  bool  $isRich  Whether the text is rich text (e.g., RTF, DOCX) or plain text (TXT)
     */
    public static function path($type, $isRich = false)
    {
        return match ($type) {
            ContentType::TEXT => $isRich ? 'richTextPath' : 'plainTextPath',
            default => 'path',
        };
    }
}
