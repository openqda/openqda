<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNavigated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;

    public $url;

    public $teamId;

    public $userProfilePhoto;

    public function __construct($userId, $url, $teamId, $userProfilePhoto)
    {
        $this->userId = $userId;
        $this->url = $url;
        $this->teamId = $teamId;
        $this->userProfilePhoto = $userProfilePhoto;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('team.'.$this->teamId);
    }

    public function broadcastWith(): array
    {
        return ['userId' => $this->userId, 'url' => $this->url, 'profile_photo' => $this->userProfilePhoto];
    }
}
