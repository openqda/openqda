<?php

namespace App\Http\Controllers;

use App\Events\UserNavigated;
use App\Http\Requests\SendFeedbackRequest;
use App\Mail\UserFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class UserNavigationController extends Controller
{
    /**
     * Update the location of the user by sending an event
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * submit feedback to the team
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function feedback(SendFeedbackRequest $request)
    {
        $data = $request->feedbackData();
        $userId = $data['userId'];
        Log::info('Feedback received from user '.$userId, $data);

        // In order to avoid spam and mail flooding
        // we rate-limit message sending
        $executed = RateLimiter::attempt(
            'send-feedback:'.$userId,
            $perMinute = config('mail.feedback.perMinute'),
            function () use ($data) {
                $ticketId = $data['userId'].time();
                $target = config('mail.feedback.address');
                Mail::to($target)->send(new UserFeedback($data, $ticketId));
            }
        );

        if (! $executed) {
            throw new \Exception('Too many messages sent!');
        }

        return response()->json(['sent' => true]);
    }
}
