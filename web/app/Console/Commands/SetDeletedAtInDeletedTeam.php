<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;

class SetDeletedAtInDeletedTeam extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:set-deleted-at';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set "Deleted At" in Deleted Teams that have no project.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $teams = Team::with('projects')
            ->withCount('projects as projects_count')
            ->where('personal_team', 0)
            ->whereNull('deleted_at')
            ->having('projects_count', '==', 0)
            ->get();

        if ($teams->isEmpty()) {
            $this->info('No teams found');

            return;
        }

        // show all the teams in a table
        $this->table(
            ['ID', 'user', 'Name', 'Deleted At'],
            $teams->map(function ($team) {
                return [$team->id, User::find($team->user_id)->email, $team->name, $team->deleted_at];
            })
        );

        // write in a list that you can write the comma separated id, all to deleted all or abort to abort
        $teamIds = $this->ask('Enter the team IDs to set deleted at now (comma-separated), all to delete all, or abort to abort');

        // if the user enters abort, stop the command
        if ($teamIds === 'abort') {
            $this->info('Aborted');

            return;
        }

        // if the user enters all, set the team IDs to all the team IDs
        if ($teamIds === 'all') {
            $teamIds = $teams->pluck('id')->join(',');
        }

        // convert the comma-separated string to an array
        $teamIds = explode(',', $teamIds);

        // loop through the array of team IDs
        foreach ($teamIds as $teamId) {
            // find the team by ID
            $team = Team::find($teamId);

            // if the team is not found, skip to the next iteration
            if (! $team) {
                $this->error("Team with ID $teamId not found");

                continue;
            }

            // if the team has projects, skip to the next iteration
            if (! $team->projects->isEmpty()) {
                $this->error("Team with ID $teamId has projects");

                continue;
            }

            // set the deleted_at column to the current date and time
            $team->deleted_at = now();
            $team->save();

            // display a success message
            $this->info("Team with ID $teamId set deleted at now");
        }

        //        foreach ($teams as $team) {
        //            if ($team->projects->isEmpty()) {
        //                $team->deleted_at = now();
        //                $team->save();
        //            }
        //        }
    }
}
