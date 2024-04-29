<?php

namespace App\Events;

use App\Models\Source;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SourceDeleting
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $source;

    /**
     * Create a new event instance.
     *
     * @param Source $source
     * @return void
     */
    public function __construct(Source $source)
    {
        $this->source = $source;
    }
}

