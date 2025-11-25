<?php

namespace App\Http\Controllers;

use App\Builder\QdeFileBuilder;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Storage;
use STS\ZipStream\Facades\Zip;

/**
 * Controller to handle exporting a project and its related data.
 * Implements a REFI compliant export format.
 * It creates a zip file containing a .qde file (xml) and all source files.
 * This process itself is split into different steps and methods for better maintainability:
 * 1. prepare folders and files that will be added to the zip
 * 2. create a map of users with guids for linking sources and codes with generated guid
 * 3. create the XML structure for the .qde file
 * 4. create zip with qde file and source files
 */
class ExportController extends Controller
{
    public function index() {}

    /**
     * Run the export process for a project.
     *
     * @return \Inertia\Response|never
     */
    public function run(Request $request, Project $project)
    {
        $rootPath = 'projects/'.$project->id;
        $basePath = $rootPath.'/export';
        $this->prepareFolder($basePath, $project);
        $options = $request->only(['sources', 'users']);
        $codebooks = $project->codebooks()->with('codes')->with('creatingUser')->get();

        // first we create a users map that creates a uuid for the
        // user and allows to look for that user by id
        // because in REFI we need to link users by a guid (uuid)
        $team = $project->team->load('owner', 'users');
        $users = $team->users->map(function ($user) {
            $user->guid = Str::uuid()->toString();

            return $user;
        });

        $team->owner->guid = Str::uuid()->toString();
        $users->push($team->owner);

        $sources = $project->sources()->with('creatingUser')->get();

        // building the XML structure for qde file
        $qde = new QdeFileBuilder($rootPath, $basePath, $basePath.'/sources');
        $qde
            ->project($project)
            ->users($users)
            ->sources($sources)
            ->codebooks($codebooks)
            ->build();

        // store qde file
        $qdeFileName = $basePath.'/project.qde';
        Storage::put($qdeFileName, $qde->toXml());

        // create zip with qde file and source files
        $zipName = 'OpenQDA Project '.$project->name.'.zip';
        $zip = Zip::create($zipName, [Storage::path($qdeFileName)]);

        // add sources, if defined, into own subfolder '/sources' in zip
        foreach ($sources as $source) {
            $extension = pathinfo($source->name, PATHINFO_EXTENSION);
            $zip->add(Storage::path($rootPath.'/sources/'.$source->name), 'sources/'.$source->id.'.'.$extension);
        }

        return $zip;
    }

    /**
     * Makes sure every export starts with a clean directory.
     */
    protected function prepareFolder(string $basePath, Project $project)
    {
        if (Storage::exists($basePath)) {
            Storage::deleteDirectory($basePath);
        }
        Storage::makeDirectory($basePath);
        Storage::makeDirectory($basePath.'/sources');

        return $basePath;
    }

    protected function storeQDEFile(string $filename, Project $project, $content)
    {
        Storage::put($filename, $content);
    }
}
