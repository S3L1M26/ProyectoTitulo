<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Aprendiz;
use App\Models\Mentor;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentosSeeder extends Seeder
{
    /**
     * Seed para cargar documentos de prueba (CVs, certificados, avatars)
     * 
     * INSTRUCCIONES:
     * 1. Este seeder crea archivos dummy/falsos para desarrollo
     * 2. Para usar archivos reales, coloca los PDFs en: storage/app/seeders/
     * 3. Descomenta la sección de archivos reales más abajo
     */
    public function run(): void
    {
        $this->command->info('📄 Creando documentos de prueba...');

        // ==========================================
        // OPCIÓN 1: Archivos DUMMY (Falsos)
        // ==========================================
        $this->crearDocumentosDummy();

        // ==========================================
        // OPCIÓN 2: Archivos REALES (Descomenta para usar)
        // ==========================================
        // $this->cargarDocumentosReales();

        $this->command->info('✅ Documentos creados exitosamente');
    }

    /**
     * Crear archivos dummy (falsos) para testing
     */
    private function crearDocumentosDummy(): void
    {
        // CVs para mentores
        $mentores = User::where('role', 'mentor')->get();
        foreach ($mentores as $mentor) {
            $mentorProfile = Mentor::where('user_id', $mentor->id)->first();
            
            if ($mentorProfile && !$mentorProfile->cv_path) {
                // Crear PDF falso
                $fakeCv = UploadedFile::fake()->create(
                    'cv_' . $mentor->name . '.pdf',
                    500, // 500 KB
                    'application/pdf'
                );

                // Guardar en storage/app/public/cvs/
                $cvPath = $fakeCv->store('cvs', 'public');
                
                $mentorProfile->update(['cv_path' => $cvPath]);
                
                $this->command->info("  ✓ CV dummy creado para: {$mentor->name}");
            }
        }

        // Certificados para estudiantes (opcional)
        $estudiantes = User::where('role', 'aprendiz')->take(3)->get();
        foreach ($estudiantes as $estudiante) {
            $aprendiz = Aprendiz::where('user_id', $estudiante->id)->first();
            
            if ($aprendiz) {
                $fakeCert = UploadedFile::fake()->create(
                    'certificado_' . $estudiante->name . '.pdf',
                    300,
                    'application/pdf'
                );

                $certPath = $fakeCert->store('certificados', 'public');
                
                // Si tu modelo Aprendiz tiene campo de certificado, úsalo aquí
                // $aprendiz->update(['certificado_path' => $certPath]);
                
                $this->command->info("  ✓ Certificado dummy creado para: {$estudiante->name}");
            }
        }
    }

    /**
     * Cargar archivos REALES desde storage/app/seeders/
     * 
     * Estructura esperada:
     * storage/app/seeders/
     *   ├── cvs/
     *   │   ├── cv_template.pdf
     *   │   └── cv_senior.pdf
     *   └── certificados/
     *       └── certificado_template.pdf
     */
    private function cargarDocumentosReales(): void
    {
        $seedersPath = 'seeders';

        // Verificar que existe la carpeta de seeders
        if (!Storage::disk('local')->exists($seedersPath)) {
            $this->command->warn("⚠️  La carpeta storage/app/{$seedersPath} no existe.");
            $this->command->info("   Creándola ahora...");
            Storage::disk('local')->makeDirectory($seedersPath . '/cvs');
            Storage::disk('local')->makeDirectory($seedersPath . '/certificados');
            return;
        }

        // Obtener CV template
        $cvTemplatePath = $seedersPath . '/cvs/cv_template.pdf';
        
        if (Storage::disk('local')->exists($cvTemplatePath)) {
            $mentores = User::where('role', 'mentor')->get();
            
            foreach ($mentores as $mentor) {
                $mentorProfile = Mentor::where('user_id', $mentor->id)->first();
                
                if ($mentorProfile && !$mentorProfile->cv_path) {
                    // Copiar el CV template con nuevo nombre
                    $newCvPath = 'cvs/mentor_' . $mentor->id . '_cv.pdf';
                    
                    Storage::disk('public')->put(
                        $newCvPath,
                        Storage::disk('local')->get($cvTemplatePath)
                    );
                    
                    $mentorProfile->update(['cv_path' => $newCvPath]);
                    
                    $this->command->info("  ✓ CV real copiado para: {$mentor->name}");
                }
            }
        } else {
            $this->command->warn("⚠️  No se encontró: storage/app/{$cvTemplatePath}");
            $this->command->info("   Coloca un PDF llamado 'cv_template.pdf' en esa ubicación.");
        }
    }

    /**
     * Bonus: Descargar avatares de internet
     */
    private function descargarAvatares(): void
    {
        $usuarios = User::all();
        
        foreach ($usuarios as $user) {
            try {
                // Usar pravatar.cc para generar avatares únicos
                $avatarUrl = 'https://i.pravatar.cc/300?u=' . urlencode($user->email);
                
                $imageContent = file_get_contents($avatarUrl);
                
                if ($imageContent) {
                    $filename = 'avatars/user_' . $user->id . '.jpg';
                    Storage::disk('public')->put($filename, $imageContent);
                    
                    $user->update(['avatar' => $filename]);
                    
                    $this->command->info("  ✓ Avatar descargado para: {$user->name}");
                }
            } catch (\Exception $e) {
                $this->command->warn("  ⚠️  Error descargando avatar para {$user->name}");
            }
        }
    }
}
