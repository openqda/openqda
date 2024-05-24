<?php

namespace App\Events;

use App\Models\Codebook;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CodebookDeleting
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $codebook;

    public function __construct(Codebook $codebook)
    {
        $this->codebook = $codebook;
    }
}
