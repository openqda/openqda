<?php

namespace App\Http\Controllers;

use App\Events\UserNavigated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserNavigationController extends Controller
{
    public function update(Request $request)
    {
        // Validate the request data
        $data = $request->validate([
            'url' => 'required|url',
        ]);

        // Dispatch the event
        event(new UserNavigated(auth()->id(), $data['url'], $request->input('team'), Auth::user()->profile_photo_url));

        return response()->json(['status' => 'success']);
    }
}
