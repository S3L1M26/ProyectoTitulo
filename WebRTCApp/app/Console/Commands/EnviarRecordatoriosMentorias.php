<?php

namespace App\Console\Commands;

use App\Jobs\EnviarRecordatorioMentoriaJob;
use App\Models\Mentoria;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarRecordatoriosMentorias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mentorias:enviar-recordatorios 
                            {--force : Forzar envío incluso si ya se envió}
                            {--debug : Mostrar información detallada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía recordatorios por email 24 horas antes de las mentorías confirmadas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Buscando mentorías para enviar recordatorios...');

        // Calcular el rango de fechas (mañana a esta misma hora)
        $ahora = Carbon::now();
        $manana = $ahora->copy()->addDay();
        
        // Buscar mentorías confirmadas para mañana que aún no tienen recordatorio
        $query = Mentoria::query()
            ->where('estado', 'confirmada')
            ->whereDate('fecha', $manana->toDateString());

        if (!$this->option('force')) {
            $query->where('recordatorio_enviado', false);
        }

        $mentorias = $query->with(['mentor', 'aprendiz', 'solicitud'])->get();

        if ($mentorias->isEmpty()) {
            $this->warn('⚠️  No se encontraron mentorías para enviar recordatorios.');
            Log::info('No hay mentorías para recordatorios', [
                'fecha_buscada' => $manana->toDateString(),
            ]);
            return Command::SUCCESS;
        }

        $this->info("📊 Encontradas {$mentorias->count()} mentoría(s) para mañana.");

        $enviados = 0;
        $errores = 0;

        foreach ($mentorias as $mentoria) {
            try {
                if ($this->option('debug')) {
                    $this->line("  → Procesando mentoría ID: {$mentoria->id}");
                    $this->line("    Fecha: {$mentoria->fecha} {$mentoria->hora}");
                    $this->line("    Mentor: " . ($mentoria->mentor->name ?? 'N/A'));
                    $this->line("    Estudiante: " . ($mentoria->aprendiz->name ?? 'N/A'));
                }

                // Despachar job a la cola
                EnviarRecordatorioMentoriaJob::dispatch($mentoria);
                $enviados++;

                $this->info("  ✅ Recordatorio programado para mentoría ID: {$mentoria->id}");

            } catch (\Exception $e) {
                $errores++;
                $this->error("  ❌ Error con mentoría ID: {$mentoria->id} - {$e->getMessage()}");
                
                Log::error('Error al programar recordatorio', [
                    'mentoria_id' => $mentoria->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("📬 Resumen:");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Mentorías encontradas', $mentorias->count()],
                ['Recordatorios enviados', $enviados],
                ['Errores', $errores],
            ]
        );

        Log::info('Comando de recordatorios ejecutado', [
            'total' => $mentorias->count(),
            'enviados' => $enviados,
            'errores' => $errores,
            'fecha_objetivo' => $manana->toDateString(),
        ]);

        return Command::SUCCESS;
    }
}
