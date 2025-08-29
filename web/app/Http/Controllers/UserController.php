<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function getOwnedTeams(Request $request, User $user)
    {
        if (Auth::user()->id !== $user->id && ! in_array(Auth::user()->email, config('app.admins'))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $ownedTeams = $user->ownedTeams()->where('id', '!=', $user->personalTeam()->id ?? null)->with('Projects', 'users')->has('users')->get();

        return response()->json([
            'ownTeams' => $ownedTeams,
        ]);
    }
}
