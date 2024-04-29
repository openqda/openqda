<?php

namespace App\Console\Commands;

use App\Models\Codebook;
use Illuminate\Console\Command;

class DeleteCodebooksFromDeletedProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'codebooks:set-deleted-at';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete codebooks from deleted projects.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // get all the codebooks that have a trashed project
        $codebooks = Codebook::with('project')
            ->whereHas('project', function ($query) {
                $query->onlyTrashed();
            })
            ->get();
        if ($codebooks->isEmpty()) {
            $this->info('No codebooks found');
            return;
        }
        // print the result in a table
        $this->table(
            ['ID', 'Name', 'Project', 'Deleted At'],
            $codebooks->map(function ($codebook) {
                return [$codebook->id, $codebook->name, $codebook->project()->withTrashed()->first()->name, $codebook->project()->withTrashed()->first()->deleted_at];
            })
        );


        // ask the user if they want to delete the codebooks
        $deleteCodebooks = $this->confirm('Do you want to delete the codebooks?');

        // if the user confirms, delete the codebooks
        if ($deleteCodebooks) {
            $codebooks->each(function ($codebook) {
                $codebook->delete();
            });
            $this->info('Codebooks deleted successfully');
        } else {
            $this->info('Codebooks not deleted');
        }


    }
}
