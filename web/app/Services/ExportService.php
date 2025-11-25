<?php

namespace App\Services;

use App\Builder\OQDAFileBuilder;
use App\Models\Project;
use App\Models\Source;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Storage;

class ExportService
{
    /**
     * Exports full OpenQDA project including all data and metadata.
     * Assumes, user has permission to export the project.
     * Check permissions before calling this method!
     */
    public function exportOpenQDAProject(Project $project, User $user)
    {
        // 1. prepare folder structure
        $rootPath = 'projects/'.$project->id;
        $basePath = $rootPath.'/export';
        $this->prepareFolder($basePath, $project);

        // 2. gather data
        $data = $this->gatherProjectData($project, $user);

        // 3. build OQDA files
        $builder = app(OQDAFileBuilder::class);
        $builder
            ->by($user)
            ->basePath($basePath)
            ->sourcePath('/sources')
            ->project($project)
            ->users($data['users'])
            ->codebooks($data['codebooks'])
            ->sources($data['sources'])
            ->selections($data['selections'])
            ->build();
        // save files
        Storage::put($basePath.'/meta.json', json_encode($builder->meta()));
        Storage::put($basePath.'/data.json', json_encode($builder->data()));

        // create archive
        $zipName = 'OpenQDA Project '.$project->name.'.oqda';
        $filesMap = [
            'meta.json' => $basePath.'/meta.json',
            'data.json' => $basePath.'/data.json',
        ].concat($data['sources']->mapWithKeys(function ($source) use ($rootPath) {
            $extension = pathinfo($source->name, PATHINFO_EXTENSION);

            return [$rootPath.'/sources/'.$source->name => 'sources/'.$source->id.'.'.$extension];
        }))->toArray();
    }

    public function exportREFIProject(Project $project, User $user)
    {
        throw new \Exception('Not implemented yet');
    }

    /*--------------------------------------------------------------
     | Helper Methods / internal use only
     |------------------------------------------------------------*/

    protected function createArchive($basePath, $filesMap, $title)
    {
        // create zip with qde file and source files
        $zip = Zip::create($zipName, [Storage::path($qdeFileName)]);

        // add files from files map
        foreach ($filesMap as $sourcePath => $zipPath) {
            $zip->add(Storage::path($sourcePath), $zipPath);
        }

        return $zip;
    }

    protected function gatherProjectData(Project $project, User $user)
    {
        $codebooks = $project->codebooks()->with(['creatingUser:id,name,email', 'codes'])
            ->withCount('codes')->get();
        $team = $project->team->load('owner', 'users');
        $sources = $project->sources()->with('creatingUser:id')->get();
        $selections = $project->selections()->with('creatingUser:id')->get();

        // TODO audit log when implemented within Queue

        $data = [
            'users' => $team->users->concat([$team->owner]),
            'codebooks' => $codebooks,
            'sources' => $sources,
            'selections' => $selections,
        ];

        return $data;
    }

    /**
     * Makes sure every export starts with a clean directory.
     */
    protected function prepareFolder(string $basePath, Project $project)
    {
        $this->cleanup($basePath);
        Storage::makeDirectory($basePath);
        Storage::makeDirectory($basePath.'/sources');

        return $basePath;
    }

    protected function storeQDEFile(string $filename, Project $project, $content)
    {
        Storage::put($filename, $content);
    }

    protected function cleanup(string $basePath)
    {
        if (Storage::exists($basePath)) {
            Storage::deleteDirectory($basePath);
        }
    }
}
