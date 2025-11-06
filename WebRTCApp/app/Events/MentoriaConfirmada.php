<?php

namespace App\Events;

use App\Models\Mentoria;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MentoriaConfirmada
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Mentoria $mentoria;

    public function __construct(Mentoria $mentoria)
    {
        $this->mentoria = $mentoria;
    }
}
