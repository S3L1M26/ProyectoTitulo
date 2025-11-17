<?php

namespace App\Jobs;

use App\Mail\RecordatorioMentoriaMail;
use App\Models\Mentoria;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatorioMentoriaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mentoria $mentoria
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Cargar relaciones necesarias (solo mentor y aprendiz)
            $this->mentoria->load(['mentor', 'aprendiz']);

            // Enviar recordatorio al mentor
            if ($this->mentoria->mentor && $this->mentoria->mentor->email) {
                Mail::to($this->mentoria->mentor->email)
                    ->send(new RecordatorioMentoriaMail($this->mentoria, 'mentor'));
                
                Log::info('📧 Recordatorio enviado al mentor', [
                    'mentoria_id' => $this->mentoria->id,
                    'mentor_email' => $this->mentoria->mentor->email,
                ]);
            }

            // Enviar recordatorio al estudiante
            if ($this->mentoria->aprendiz && $this->mentoria->aprendiz->email) {
                Mail::to($this->mentoria->aprendiz->email)
                    ->send(new RecordatorioMentoriaMail($this->mentoria, 'estudiante'));
                
                Log::info('📧 Recordatorio enviado al estudiante', [
                    'mentoria_id' => $this->mentoria->id,
                    'estudiante_email' => $this->mentoria->aprendiz->email,
                ]);
            }

            // Marcar recordatorio como enviado
            $this->mentoria->update(['recordatorio_enviado' => true]);

            Log::info('✅ Recordatorios de mentoría enviados exitosamente', [
                'mentoria_id' => $this->mentoria->id,
                'fecha' => $this->mentoria->fecha,
                'hora' => $this->mentoria->hora,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al enviar recordatorio de mentoría', [
                'mentoria_id' => $this->mentoria->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-lanzar la excepción para que Laravel reintente el job
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('❌ Job de recordatorio falló definitivamente', [
            'mentoria_id' => $this->mentoria->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
