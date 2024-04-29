<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversionCompleted implements ShouldBroadcast
{
    use Dispatchable,InteractsWithSockets, SerializesModels;

    public $projectId;
    public $sourceId;

    public function __construct($projectId, $sourceId)
    {
        $this->projectId = $projectId;
        $this->sourceId = $sourceId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('conversion.' . $this->projectId);
    }

    public function broadcastWith(): array
    {
        return [
            'projectId' => $this->projectId,
            'sourceId' => $this->sourceId
        ];
    }
}
