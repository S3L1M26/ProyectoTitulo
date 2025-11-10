<?php

namespace App\Mail;

use App\Models\Mentoria;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecordatorioMentoriaMail extends Mailable
{
    use Queueable, SerializesModels;

    public Mentoria $mentoria;
    public string $tipoDestinatario; // 'mentor' o 'estudiante'

    /**
     * Create a new message instance.
     */
    public function __construct(Mentoria $mentoria, string $tipoDestinatario = 'estudiante')
    {
        $this->mentoria = $mentoria;
        $this->tipoDestinatario = $tipoDestinatario;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $area = null;
        try {
            $area = $this->mentoria->solicitud?->aprendiz?->areaInteres?->nombre ?? null;
        } catch (\Throwable $e) {
            $area = null;
        }

        $subject = '🔔 Recordatorio: Mentoría mañana' . ($area ? ' - ' . $area : '');

        return $this->subject($subject)
            ->view('emails.recordatorio-mentoria')
            ->with([
                'mentoria' => $this->mentoria,
                'tipoDestinatario' => $this->tipoDestinatario,
            ]);
    }
}
