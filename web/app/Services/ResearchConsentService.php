<?php

namespace App\Services;

use App\Mail\ResearchConfirmation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ResearchConsentService
{
    /**
     * If user applied to research, we need to send an Email to confirm it.
     */
    public function sendResearchConfirmation($user)
    {
        // Generate a unique token using SHA256
        $token = hash('sha256', uniqid($user->id.microtime(), true));

        // Save the token to the user model
        $user->update(['research_token' => $token, 'research_requested' => now()]);

        // send mail with token to user
        $link = url()->query('/user/profile', ['action' => 'cfr', 'token' => $token]);
        $mail = new ResearchConfirmation(['name' => $user->name, 'link' => $link]);
        Mail::to($user->email)->send($mail);
    }

    /**
     * Confirm user research participation.
     */
    public function confirmResearch(User $user, string $token): void
    {
        if (! $token || $user->research_token !== $token) {
            throw new \Exception('Invalid token:'.$token);
        }

        $user->update(['research_consent' => now(), 'research_token' => null, 'research_requested' => null]);
    }

    /**
     * Withdraw user research participation.
     */
    public function withdrawResearch(User $user): void
    {
        $user->update(['research_consent' => null, 'research_token' => null, 'research_requested' => null]);
    }
}
