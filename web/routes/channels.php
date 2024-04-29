<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('team.{team}', function ($user, \App\Models\Team $team) {

    if ($team->hasUser($user)) {

        return ['name' => $user->name, 'id' => $user->id];
    }

});

Broadcast::channel('conversion.{project}', function ($user, \App\Models\Project $project) {
    $userTeamIds = $user->teams->pluck('id');
    return $user->id === $project->creating_user_id || $userTeamIds->contains($project->team_id);
});
