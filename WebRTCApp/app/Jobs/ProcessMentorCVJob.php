<?php

namespace App\Jobs;

use App\Models\MentorDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProcessMentorCVJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MentorDocument $document
    ) {}

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120; // CVs pueden tener múltiples páginas

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        logger()->info('Mentor CV processing started', ['document_id' => $this->document->id]);
        
        try {
            $extractedText = '';
            $imagePaths = [];
            
            // 1. Intentar extracción directa de texto del PDF (TODAS las páginas)
            $stepStart = microtime(true);
            $extractedText = $this->extractTextDirectly($this->document->file_path);
            
            if (!empty(trim($extractedText))) {
                logger()->info('PDF text extracted directly', [
                    'document_id' => $this->document->id,
                    'text_length' => strlen($extractedText),
                    'time_ms' => round((microtime(true) - $stepStart) * 1000)
                ]);
            } else {
                // 2. Si no tiene texto seleccionable, usar OCR en todas las páginas
                logger()->info('PDF has no selectable text, using OCR for all pages', [
                    'document_id' => $this->document->id
                ]);
                
                // 2a. Convertir todas las páginas del PDF a imágenes
                $stepStart = microtime(true);
                $imagePaths = $this->convertPdfToImages($this->document->file_path);
                logger()->info('PDF converted to images', [
                    'document_id' => $this->document->id,
                    'pages' => count($imagePaths),
                    'time_ms' => round((microtime(true) - $stepStart) * 1000)
                ]);
                
                // 2b. Extraer texto con OCR de todas las páginas
                $stepStart = microtime(true);
                $extractedText = $this->extractTextFromImages($imagePaths);
                logger()->info('OCR text extracted from all pages', [
                    'document_id' => $this->document->id,
                    'text_length' => strlen($extractedText),
                    'time_ms' => round((microtime(true) - $stepStart) * 1000)
                ]);
            }
            
            // 3. Validar CV por palabras clave técnicas y calcular score
            $stepStart = microtime(true);
            $validationResult = $this->validateCV($extractedText);
            logger()->info('CV validated', [
                'document_id' => $this->document->id,
                'score' => $validationResult['score'],
                'time_ms' => round((microtime(true) - $stepStart) * 1000)
            ]);
            
            // 4. Actualizar documento con resultados
            $this->document->update([
                'extracted_text' => $extractedText,
                'keyword_score' => $validationResult['score'],
                'status' => $validationResult['status'],
                'rejection_reason' => $validationResult['rejection_reason'],
                'processed_at' => now(),
            ]);
            
            // Limpiar imágenes temporales si se crearon (local temp files)
            foreach ($imagePaths as $imagePath) {
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            
            $totalTime = round((microtime(true) - $startTime) * 1000);
            logger()->info('CV processed successfully', [
                'document_id' => $this->document->id,
                'status' => $validationResult['status'],
                'score' => $validationResult['score'],
                'total_time_ms' => $totalTime,
            ]);
            
        } catch (Exception $e) {
            $totalTime = round((microtime(true) - $startTime) * 1000);
            logger()->error('CV processing failed', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
                'total_time_ms' => $totalTime,
            ]);

            $this->document->update([
                'status' => 'invalid',
                'rejection_reason' => 'Error al procesar el CV: ' . $e->getMessage(),
                'processed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Download a remote storage file to a local temporary path and return local path.
     * Works with S3/Spaces or local disk.
     */
    private function downloadToLocal(string $storagePath): string
    {
        $disk = config('filesystems.default');

        // If the storage is local and Storage::path works, just return that path
        try {
            $local = Storage::disk($disk)->path($storagePath);
            if (file_exists($local)) {
                return $local;
            }
        } catch (Exception $e) {
            // ignore and fallback to streaming
        }

        // Otherwise stream the file from the configured disk to a local temp file
        $stream = Storage::disk($disk)->readStream($storagePath);
        if ($stream === false) {
            throw new Exception('No se pudo leer el archivo remoto para procesar');
        }

        $localPath = sys_get_temp_dir() . '/' . uniqid('cv_pdf_') . '_' . basename($storagePath);
        $out = fopen($localPath, 'w');
        if (!$out) {
            throw new Exception('No se pudo crear archivo temporal local');
        }

        while (!feof($stream)) {
            fwrite($out, fread($stream, 1024 * 8));
        }

        if (is_resource($stream)) {
            fclose($stream);
        }
        fclose($out);

        return $localPath;
    }

    /**
     * Intentar extraer texto directamente del PDF sin OCR (TODAS las páginas)
     */
    private function extractTextDirectly(string $pdfPath): string
    {
        $disk = config('filesystems.default', 'local');
        try {
            $fullPath = Storage::disk($disk)->path($pdfPath);
            if (!file_exists($fullPath)) {
                // Try downloading to local temp if path doesn't exist
                $fullPath = $this->downloadToLocal($pdfPath);
            }
        } catch (Exception $e) {
            $fullPath = $this->downloadToLocal($pdfPath);
        }
        
        try {
            $output = [];
            $returnVar = 0;
            
            // Usar pdftotext de poppler-utils para extraer texto de TODAS las páginas
            $command = sprintf('pdftotext %s - 2>&1', escapeshellarg($fullPath));
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                return ''; // No se pudo extraer, devolver vacío para que use OCR
            }
            
            $text = implode("\n", $output);
            
            // Si el texto es muy corto o vacío, probablemente sea una imagen
            if (strlen(trim($text)) < 50) {
                return '';
            }
            
            return strtolower($text);
            
        } catch (Exception $e) {
            // Si falla, devolver vacío para que use OCR
            return '';
        }
    }

    /**
     * Convertir TODAS las páginas del PDF a imágenes usando pdftoppm
     */
    private function convertPdfToImages(string $pdfPath): array
    {
        $disk = config('filesystems.default', 'local');
        try {
            $fullPath = Storage::disk($disk)->path($pdfPath);
            if (!file_exists($fullPath)) {
                $fullPath = $this->downloadToLocal($pdfPath);
            }
        } catch (Exception $e) {
            $fullPath = $this->downloadToLocal($pdfPath);
        }
        $outputBaseName = uniqid('cv_');
        $outputDir = sys_get_temp_dir();
        
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        
        try {
            // Usar pdftoppm para convertir TODAS las páginas (sin -f y -l)
            $command = sprintf(
                'pdftoppm -jpeg -r 300 %s %s 2>&1',
                escapeshellarg($fullPath),
                escapeshellarg($outputDir . '/' . $outputBaseName)
            );
            
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                throw new Exception("No se pudo convertir el PDF a imágenes");
            }
            
            // pdftoppm genera archivos con formato: outputBaseName-1.jpg, outputBaseName-2.jpg, etc.
            // Buscar todos los archivos generados en el directorio temporal
            $imagePaths = [];
            $files = scandir($outputDir);
            
            foreach ($files as $file) {
                if (str_starts_with($file, $outputBaseName) && pathinfo($file, PATHINFO_EXTENSION) === 'jpg') {
                    $imagePaths[] = $outputDir . '/' . $file;
                }
            }
            
            if (empty($imagePaths)) {
                throw new Exception("No se generaron imágenes del PDF");
            }
            
            // Ordenar por nombre para mantener el orden de las páginas
            sort($imagePaths);
            
            return $imagePaths;
            
        } catch (Exception $e) {
            throw new Exception("No se pudo convertir el PDF a imágenes: " . $e->getMessage());
        }
    }

    /**
     * Extraer texto de múltiples imágenes usando OCR
     */
    private function extractTextFromImages(array $imagePaths): string
    {
        $allText = [];
        
        foreach ($imagePaths as $imagePath) {
            // $imagePath is already a full local path from convertPdfToImages
            $fullPath = $imagePath;
            
            try {
                // Preprocesar imagen para mejorar OCR
                $preprocessedPath = $this->preprocessImage($fullPath);
                
                $output = [];
                $returnVar = 0;
                
                // Ejecutar tesseract con idioma español y PSM 6 (bloque uniforme de texto)
                $command = sprintf(
                    'tesseract %s stdout -l spa --psm 6 --oem 3 2>&1',
                    escapeshellarg($preprocessedPath)
                );
                
                exec($command, $output, $returnVar);
                
                // Limpiar imagen preprocesada temporal
                if (file_exists($preprocessedPath) && $preprocessedPath !== $fullPath) {
                    @unlink($preprocessedPath);
                }
                
                if ($returnVar === 0) {
                    $pageText = implode("\n", $output);
                    if (!empty(trim($pageText))) {
                        $allText[] = $pageText;
                    }
                }
                
            } catch (Exception $e) {
                // Continuar con la siguiente página si una falla
                logger()->warning('OCR failed for page', [
                    'document_id' => $this->document->id,
                    'image' => $imagePath,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        if (empty($allText)) {
            throw new Exception("No se pudo extraer texto de ninguna página del documento");
        }
        
        return strtolower(implode("\n\n", $allText));
    }

    /**
     * Preprocesar imagen para mejorar precisión del OCR
     */
    private function preprocessImage(string $imagePath): string
    {
        try {
            $preprocessedPath = dirname($imagePath) . '/preprocessed_' . basename($imagePath);
            
            // Usar ImageMagick para mejorar la imagen
            $command = sprintf(
                'convert %s -normalize -sharpen 0x1 -type Grayscale -depth 8 %s 2>&1',
                escapeshellarg($imagePath),
                escapeshellarg($preprocessedPath)
            );
            
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0 || !file_exists($preprocessedPath)) {
                // Si falla el preprocesamiento, usar imagen original
                return $imagePath;
            }
            
            return $preprocessedPath;
            
        } catch (Exception $e) {
            // Si falla, devolver imagen original
            return $imagePath;
        }
    }

    /**
     * Validar CV por palabras clave técnicas
     * Sistema de puntuación con bonificaciones
     */
    private function validateCV(string $text): array
    {
        // Palabras clave críticas (15 pts c/u)
        $criticalKeywords = [
            'experiencia',
            'php',
            'laravel',
            'javascript',
            'universidad',
        ];
        
        // Palabras importantes (10 pts c/u)
        $importantKeywords = [
            'desarrollador',
            'ingeniero',
            'años',
            'proyecto',
            'git',
        ];
        
        // Palabras opcionales (5 pts c/u)
        $optionalKeywords = [
            'docker',
            'aws',
            'react',
            'vue',
            'mysql',
            'python',
        ];

        $score = 0;
        $foundKeywords = [];

        // Buscar palabras críticas (15 pts c/u)
        foreach ($criticalKeywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                $score += 15;
                $foundKeywords[] = $keyword;
            }
        }

        // Buscar palabras importantes (10 pts c/u)
        foreach ($importantKeywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                $score += 10;
                $foundKeywords[] = $keyword;
            }
        }

        // Buscar palabras opcionales (5 pts c/u)
        foreach ($optionalKeywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                $score += 5;
                $foundKeywords[] = $keyword;
            }
        }

        // Bonificaciones
        $bonuses = [];
        
        // Bonus si contiene email (@): +10 puntos
        if (str_contains($text, '@')) {
            $score += 10;
            $bonuses[] = 'email (+10)';
        }
        
        // Bonus si contiene teléfono (+51 o variaciones): +5 puntos
        if (preg_match('/\+51|51\s*9|\(\+51\)/', $text)) {
            $score += 5;
            $bonuses[] = 'teléfono (+5)';
        }

        // Determinar estado y razón de rechazo
        // Mínimo para aprobar: 50 puntos
        if ($score >= 50) {
            $status = 'approved';
            $rejectionReason = null;
        } else {
            $status = 'rejected';
            $rejectionReason = $this->generateRejectionReason($score, $foundKeywords);
        }

        return [
            'score' => $score,
            'status' => $status,
            'rejection_reason' => $rejectionReason,
            'found_keywords' => $foundKeywords,
            'bonuses' => $bonuses,
        ];
    }

    /**
     * Generar razón de rechazo basada en score y palabras encontradas
     */
    private function generateRejectionReason(int $score, array $foundKeywords): string
    {
        if ($score === 0) {
            return 'El CV no contiene información técnica relevante. Asegúrate de incluir tu experiencia en desarrollo, tecnologías que dominas, y proyectos realizados.';
        }
        
        if ($score < 20) {
            return 'El CV tiene muy poca información técnica. Incluye detalles sobre: lenguajes de programación, frameworks, años de experiencia, y formación académica.';
        }
        
        if ($score < 35) {
            return 'Falta información importante en el CV. Asegúrate de mencionar: tecnologías específicas (PHP, Laravel, JavaScript), experiencia laboral, y proyectos destacados.';
        }
        
        if ($score < 50) {
            return 'El CV está incompleto. Para ser aprobado necesita al menos 50 puntos. Sugerencias: agrega más detalles técnicos, menciona herramientas (Git, Docker), y complementa con información de contacto.';
        }
        
        return 'El CV no cumple con los requisitos mínimos de información técnica.';
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        logger()->error('Mentor CV processing failed', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
        ]);

        // Marcar documento como inválido
        $this->document->update([
            'status' => 'invalid',
            'rejection_reason' => 'Error al procesar el CV: ' . $exception->getMessage(),
            'processed_at' => now(),
        ]);
    }
}
