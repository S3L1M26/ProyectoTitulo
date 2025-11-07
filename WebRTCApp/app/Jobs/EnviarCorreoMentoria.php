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
    public string $cid;
    public string $jobDispatchId;

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

    public function __construct(Mentoria $mentoria, string $cid, string $jobDispatchId)
    {
        $this->mentoria = $mentoria;
        $this->cid = $cid;
        $this->jobDispatchId = $jobDispatchId;
    }

    public function handle(): void
    {
        try {
            Log::info('🚀 JOB START', [
                'mentoria_id' => $this->mentoria->id,
                'cid' => $this->cid,
                'job_dispatch_id' => $this->jobDispatchId,
                'attempt' => method_exists($this, 'attempts') ? $this->attempts() : null,
                'timestamp' => microtime(true),
            ]);
            $aprendiz = $this->mentoria->aprendiz;
            if (!$aprendiz) {
                throw new \RuntimeException('Aprendiz no encontrado para la mentoría');
            }

            Mail::to($aprendiz->email)
                ->send(new MentoriaConfirmadaMail($this->mentoria));

            Log::info('✅ JOB SENT EMAIL', [
                'mentoria_id' => $this->mentoria->id,
                'email' => $aprendiz->email,
                'cid' => $this->cid,
                'job_dispatch_id' => $this->jobDispatchId,
                'timestamp' => microtime(true),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al enviar correo de mentoría', [
                'mentoria_id' => $this->mentoria->id ?? null,
                'cid' => $this->cid ?? null,
                'job_dispatch_id' => $this->jobDispatchId ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Permite reintentos
        }
    }
}
