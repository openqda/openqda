<?php

namespace App\Http\Controllers;

use SimpleXMLElement;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use STS\ZipStream\Facades\Zip;
use Illuminate\Support\Facades\Log;

/**
 * Controller to handle exporting a project and its related data.
 * Implements a REFI compliant export format.
 * It creates a zip file containing a .qde file (xml) and all source files.
 * This process itself is split into different steps and methods for better maintainability:
 * 1. prepare folders and files that will be added to the zip
 * 2. create a map of users with guids for linking sources and codes
 * 3. create the XML structure for the .qde file
 */
class ExportController extends Controller
{
    public function index() {}

    /**
     * Run the export process for a project.
     *
     * @return \Inertia\Response|never
     */
    public function run(Request $request,  Project $project)
    {
        $rootPath = 'projects/'.$project->id;
        $basePath = $rootPath.'/export';
        $this->prepareFolder($basePath, $project);
        $options = $request->only(['sources', 'users']);

        // first we create a users map that creates a uuid for the
        // user and allows to look for that user by id
        // because in REFI we need to link users by a guid (uuid)
        $team = $project->team->load('owner', 'users');
        $users = $team->users->map(function ($user) {
            return [
                'id' => $user->id,
                'guid' => Str::uuid()->toString(),
                'name' => $user->name,
                'email' => $user->email,
            ];
        });

        $users->push([
            'id' => $team->owner->id,
            'guid' => Str::uuid()->toString(),
            'name' => $team->owner->name,
            'email' => $team->owner->email,
        ]);

        // building the XML structure for qde file
        $qde = $this->createRoot($project);
        // add users
        $this->addUsersElement($qde, $users, $options);

        // add sources
        //$sources = $this->copySources($rootPath, $basePath, $project);
        //$this->createSourcesElement($qde, $sources, $basePath);

        // add ...

        // store qde file
        $qdeFileName = $basePath.'/project.qde';
        $this->storeQDEFile($qdeFileName, $project, $qde->asXML());
        $zipName = 'OpenQDA Project '.$project->name.'.zip';

        $zip = Zip::create($zipName, [
            Storage::path($qdeFileName)
        ]);

        // add sources, if defined, into own subfolder '/sources' in zip
        //foreach ($sources as $sourceFileName) {
          //$sourcePath = $basePath.'/sources/'.$sourceFileName;
          //$zip->add(Storage::path($sourcePath), 'sources/'.$sourceFileName);
        //}

        return $zip;
    }

    /**
     * Makes sure every export starts with a clean directory.
     */
    protected function prepareFolder(String $basePath, Project $project) {
        if (Storage::exists($basePath)) {
            Storage::deleteDirectory($basePath);
        }
        Storage::makeDirectory($basePath);
    }

    protected function storeQDEFile(String $filename, Project $project, $content)
    {
        Storage::put($filename, $content);
    }

    protected function createRoot ($project) {
        $element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><Project></Project>');
        $element->addAttribute('name', $project->name);
        $element->addAttribute('description', $project->description);
        $element->addAttribute('origin', 'OpenQDA-x.y.z-commit-hash');
        $element->addAttribute('creatingUserId', $project->creating_user_id);
        $element->addAttribute('creationDateTime', $project->created_at->toIso8601String());
        $element->addAttribute('modifiedDateTime', $project->updated_at->toIso8601String());
        return $element;
    }

    protected function addUsersElement ($qde, $users, $options)
    {
        $usersElement = $qde->addChild('Users');
        foreach ($users as $user) {
            $userElement = $usersElement->addChild('User');
            $userElement->addAttribute('guid', $user['guid']);
            $userElement->addAttribute('name', $user['name']);
            $userElement->addAttribute('email', $user['email']);
        }
        return $qde;
    }

    protected function copySources ($rootPath, $basePath, $project, $options = [])
    {
        $sources = $project->sources()->with('creatingUser')->get();
        $sourcePath = $basePath.'/sources';
        Storage::makeDirectory($sourcePath);

        return $sources->map(function ($source) use ($sourcePath) {
            // source file does not exist, skip it
            if (!Storage::exists($source->upload_path)) {
                return null;
            }
            $newFileName = $source->id.'.txt';
            Storage::copy($source->upload_path, $sourcePath.'/'.$newFileName);
            return ['zip_name' => $newFileName, 'source' => $source];
        })->filter(); // remove null values
    }

    protected function createSourcesElement ($qde, $sources, $basePath) {
        $sourcesElement = $qde->addChild('Sources');
        $sourcePath = $basePath.'/sources';
        foreach($mapped as $source) {
            $source = $mapped->source;
            $element = $sourcesElement->addChild('TextSource');
            $element->addAttribute('guid', $source->id);
            $element->addAttribute('name', $source->name);
            $element->addAttribute('plainTextPath', $sourcePath.'/'.$source->id.'.txt');
            $element->addAttribute('creationDateTime', $source->created_at->toIso8601String());
            $element->addAttribute('modifiedDateTime', $source->updated_at->toIso8601String());
        }
    }
}
