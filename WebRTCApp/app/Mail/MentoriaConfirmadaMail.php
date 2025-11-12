<?php

namespace App\Mail;

use App\Models\Mentoria;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MentoriaConfirmadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public Mentoria $mentoria;

    public function __construct(Mentoria $mentoria)
    {
        $this->mentoria = $mentoria;
    }

    public function build()
    {
        $area = null;
        try {
            $area = $this->mentoria->solicitud?->aprendiz?->areaInteres?->nombre ?? null;
        } catch (\Throwable $e) {
            $area = null;
        }

        $subject = '✅ Mentoría confirmada' . ($area ? ' - ' . $area : '');

        return $this->subject($subject)
            ->view('emails.mentoria-confirmada')
            ->with([
                'mentoria' => $this->mentoria,
            ]);
    }
}
