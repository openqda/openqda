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

    public $name;

    public function __construct($projectId, $sourceId, $name)
    {
        $this->projectId = $projectId;
        $this->sourceId = $sourceId;
        $this->name = $name;
    }

    /**
     * The name of the queue on which to place the broadcasting job.
     */
    public function broadcastQueue(): string
    {
        return 'conversion';
    }

    public function broadcastOn()
    {
        return new PrivateChannel('conversion.'.$this->projectId);
    }

    public function broadcastWith(): array
    {
        return [
            'projectId' => $this->projectId,
            'sourceId' => $this->sourceId,
            'name' => $this->name,
        ];
    }
}
