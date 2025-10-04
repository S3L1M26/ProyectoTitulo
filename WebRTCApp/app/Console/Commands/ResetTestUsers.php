<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Mentor;
use App\Models\Aprendiz;

class ResetTestUsers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:reset-users 
                            {--force : Saltar confirmación}';

    /**
     * The console command description.
     */
    protected $description = 'Elimina usuarios de prueba y ejecuta seeders (solo en desarrollo local)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Solo permitir en entorno local
        if (!app()->environment('local')) {
            $this->error('❌ Este comando solo está disponible en entorno local');
            $this->line('   Entorno actual: ' . app()->environment());
            $this->line('');
            $this->info('💡 Para otros entornos usa los comandos nativos:');
            $this->line('   • php artisan db:seed');
            $this->line('   • php artisan migrate:fresh --seed');
            return 1;
        }

        $this->info('🔄 RESET DE USUARIOS DE PRUEBA (LOCAL)');
        $this->line('');

        // Mostrar qué usuarios serían eliminados
        $testUsers = $this->getTestUsers();
        
        if ($testUsers->isEmpty()) {
            $this->info('✅ No se encontraron usuarios de prueba para eliminar');
            $this->line('');
            $this->info('🌱 ¿Quieres ejecutar los seeders?');
            if ($this->confirm('Ejecutar seeders de usuarios')) {
                $this->runSeeders();
            }
            return 0;
        }

        $this->line('👥 Usuarios que serán eliminados:');
        foreach ($testUsers as $user) {
            // Verificar el rol del usuario consultando las tablas directamente
            $isMentor = Mentor::where('user_id', $user->id)->exists();
            $isAprendiz = Aprendiz::where('user_id', $user->id)->exists();
            $role = $isMentor ? 'Mentor' : ($isAprendiz ? 'Aprendiz' : 'Usuario');
            $this->line("   • {$user->name} ({$user->email}) - {$role}");
        }
        $this->line('');

        // Confirmación
        if (!$this->option('force')) {
            if (!$this->confirm('¿Confirmas que quieres eliminar estos usuarios de prueba?')) {
                $this->info('❌ Operación cancelada');
                return 0;
            }
        }

        // Eliminar usuarios de prueba
        $this->resetTestUsers($testUsers);

        // Ejecutar seeders
        $this->runSeeders();

        $this->info('✅ Reset de usuarios completado exitosamente');
        return 0;
    }

    /**
     * Obtener usuarios de prueba basado en patrones
     */
    private function getTestUsers()
    {
        return User::where(function($query) {
            $query->where('email', 'like', '%.test@%')
                  ->orWhere('email', 'like', '%@example.com')
                  ->orWhere('email', 'like', 'mentor@%')
                  ->orWhere('email', 'like', 'aprendiz@%')
                  ->orWhere('email', 'like', 'estudiante%@%')
                  ->orWhere('name', 'like', 'Mentor %')
                  ->orWhere('name', 'like', 'Estudiante %')
                  ->orWhere('name', 'like', 'Test %');
        })->get();
    }

    /**
     * Eliminar usuarios de prueba y sus relaciones
     */
    private function resetTestUsers($testUsers)
    {
        $this->line('🗑️  Eliminando usuarios de prueba...');
        
        // Deshabilitar foreign key checks temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        $userIds = $testUsers->pluck('id');
        $count = $userIds->count();
        
        // Eliminar relaciones pivot de áreas de interés
        DB::table('aprendiz_area_interes')
            ->whereIn('aprendiz_id', function($query) use ($userIds) {
                $query->select('id')->from('aprendices')->whereIn('user_id', $userIds);
            })->delete();
            
        DB::table('mentor_area_interes')
            ->whereIn('mentor_id', function($query) use ($userIds) {
                $query->select('id')->from('mentors')->whereIn('user_id', $userIds);
            })->delete();

        // Eliminar perfiles específicos
        DB::table('aprendices')->whereIn('user_id', $userIds)->delete();
        DB::table('mentors')->whereIn('user_id', $userIds)->delete();
        
        // Eliminar usuarios
        DB::table('users')->whereIn('id', $userIds)->delete();
        
        // Rehabilitar foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->line("   ✅ Eliminados {$count} usuarios de prueba");
    }

    /**
     * Ejecutar seeders de usuarios
     */
    private function runSeeders()
    {
        $this->line('🌱 Ejecutando seeders de usuarios...');
        
        try {
            // Ejecutar el seeder principal que incluye usuarios
            Artisan::call('db:seed', ['--force' => true]);
            $this->line('   ✅ Seeders ejecutados correctamente');
        } catch (\Exception $e) {
            $this->error('   ❌ Error al ejecutar seeders: ' . $e->getMessage());
        }
    }
}