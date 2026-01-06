<?php

namespace App\Builder;

use App\Models\User;

/**
 * Build OpenQDA export file structure.
 */
class OQDAFileBuilder
{
    private $meta;

    private $data;

    private $by;

    private $project;

    private $users = [];

    private $sources = [];

    private $selections = [];

    private $codebooks = [];

    private $codes = [];

    private $rootPath = '';

    private $basePath = '';

    private $sourcePath = '';

    // -------------------------------------------------------------------------
    // PUBLIC
    // -------------------------------------------------------------------------
    public function by(User $user)
    {
        $this->by = $user;

        return $this;
    }

    public function basePath(string $path)
    {
        $this->basePath = $path;

        return $this;
    }

    public function sourcePath(string $path)
    {
        $this->sourcePath = $path;

        return $this;
    }

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

    public function selections($selections)
    {
        $this->selections = $selections;

        return $this;
    }

    public function build()
    {
        $this->meta = [
            'exported_by' => $this->by ? [
                'id' => $this->by->id,
                'name' => $this->by->name,
                'email' => $this->by->email,
            ] : null,
            'exported_at' => now()->toIso8601String(),
            'exported_with' => 'OpenQDA-'.config('app.version'),
            'paths' => [
                'sources' => $this->sourcePath,
            ],
        ];
        $this->data = [
            'project' => $this->project,
            'users' => $this->usersCollection,
            'codebooks' => $this->codebooks,
            'selections' => $this->selections,
            'sources' => $this->sources,
        ];
    }

    public function meta()
    {
        return $this->meta;
    }

    public function data()
    {
        return $this->data;
    }
}
