<?php

namespace App\Jobs;

use App\Mail\MentoriaConfirmadaMail;
use App\Models\Mentoria;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarCorreoMentoria implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Mentoria $mentoria;

    /**
     * Número de intentos
     */
    public $tries = 3;

    /**
     * Timeout en segundos
     */
    public $timeout = 15;

    public function backoff(): array
    {
        // Exponential backoff
        return [3, 10, 30];
    }

    public function __construct(Mentoria $mentoria)
    {
        $this->mentoria = $mentoria;
    }

    public function handle(): void
    {
        try {
            $aprendiz = $this->mentoria->aprendiz;
            if (!$aprendiz) {
                throw new \RuntimeException('Aprendiz no encontrado para la mentoría');
            }

            Mail::to($aprendiz->email)
                ->send(new MentoriaConfirmadaMail($this->mentoria));

            Log::info('Correo de mentoría enviado', [
                'mentoria_id' => $this->mentoria->id,
                'email' => $aprendiz->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al enviar correo de mentoría', [
                'mentoria_id' => $this->mentoria->id ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Permite reintentos
        }
    }
}
