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
    public string $cid; // Correlation ID para rastrear toda la cadena

    public function __construct(Mentoria $mentoria, string $cid)
    {
        $this->mentoria = $mentoria;
        $this->cid = $cid;
    }
}
