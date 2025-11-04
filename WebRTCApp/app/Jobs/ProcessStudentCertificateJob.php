<?php

namespace App\Jobs;

use App\Models\StudentDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProcessStudentCertificateJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public StudentDocument $document
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        logger()->info('Certificate processing started', ['document_id' => $this->document->id]);
        
        try {
            $startTime = microtime(true);
            $extractedText = '';
            $imagePath = null;
            
            // 1. Intentar extracción directa de texto del PDF (si tiene texto seleccionable)
            $stepStart = microtime(true);
            $extractedText = $this->extractTextDirectly($this->document->file_path);
            
            if (!empty(trim($extractedText))) {
                logger()->info('PDF text extracted directly', [
                    'document_id' => $this->document->id,
                    'text_length' => strlen($extractedText),
                    'time_ms' => round((microtime(true) - $stepStart) * 1000)
                ]);
            } else {
                // 2. Si no tiene texto seleccionable, usar OCR
                logger()->info('PDF has no selectable text, using OCR', [
                    'document_id' => $this->document->id
                ]);
                
                // 2a. Convertir PDF a imagen
                $stepStart = microtime(true);
                $imagePath = $this->convertPdfToImage($this->document->file_path);
                logger()->info('PDF converted to image', [
                    'document_id' => $this->document->id,
                    'time_ms' => round((microtime(true) - $stepStart) * 1000)
                ]);
                
                // 2b. Extraer texto con OCR
                $stepStart = microtime(true);
                $extractedText = $this->extractTextFromImage($imagePath);
                logger()->info('OCR text extracted', [
                    'document_id' => $this->document->id,
                    'text_length' => strlen($extractedText),
                    'time_ms' => round((microtime(true) - $stepStart) * 1000)
                ]);
            }
            
            // 3. Validar por palabras clave y calcular score
            $stepStart = microtime(true);
            $validationResult = $this->validateCertificate($extractedText);
            logger()->info('Certificate validated', [
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
            
            // Limpiar imagen temporal si se creó
            if ($imagePath && Storage::exists($imagePath)) {
                Storage::delete($imagePath);
            }
            
            $totalTime = round((microtime(true) - $startTime) * 1000);
            logger()->info('Certificate processed successfully', [
                'document_id' => $this->document->id,
                'status' => $validationResult['status'],
                'score' => $validationResult['score'],
                'total_time_ms' => $totalTime,
            ]);
            
        } catch (Exception $e) {
            // Si falla el procesamiento, marcar como invalid
            $this->document->update([
                'status' => 'invalid',
                'rejection_reason' => 'Error al procesar el archivo: ' . $e->getMessage(),
                'processed_at' => now(),
            ]);
            
            $totalTime = round((microtime(true) - $startTime) * 1000);
            logger()->error('Certificate processing failed', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
                'total_time_ms' => $totalTime,
            ]);
        }
    }

    /**
     * Intentar extraer texto directamente del PDF sin OCR
     * (si el PDF tiene texto seleccionable)
     */
    private function extractTextDirectly(string $pdfPath): string
    {
        $fullPath = Storage::path($pdfPath);
        
        try {
            $output = [];
            $returnVar = 0;
            
            // Usar pdftotext de poppler-utils para extraer texto
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
     * Convertir PDF a imagen (primera página) usando pdftoppm
     */
    private function convertPdfToImage(string $pdfPath): string
    {
        $fullPath = Storage::path($pdfPath);
        $outputBaseName = uniqid('cert_');
        $outputDir = Storage::path('temp');
        
        // Crear directorio temp si no existe
        if (!Storage::exists('temp')) {
            Storage::makeDirectory('temp');
        }
        
        try {
            // Método 1: Usar pdftoppm (poppler-utils) - Recomendado
            $command = sprintf(
                'pdftoppm -jpeg -r 300 -f 1 -l 1 -singlefile %s %s',
                escapeshellarg($fullPath),
                escapeshellarg($outputDir . '/' . $outputBaseName)
            );
            
            exec($command . ' 2>&1', $output, $returnVar);
            
            // pdftoppm genera el archivo sin el .jpg en el nombre base, lo agrega automáticamente
            $outputPath = 'temp/' . $outputBaseName . '.jpg';
            
            if ($returnVar !== 0 || !Storage::exists($outputPath)) {
                // Método 2: Fallback con ImageMagick convert
                $outputPath = 'temp/' . $outputBaseName . '_fallback.jpg';
                $fullOutputPath = Storage::path($outputPath);
                
                $command = sprintf(
                    'convert -density 300 %s[0] -quality 90 %s',
                    escapeshellarg($fullPath),
                    escapeshellarg($fullOutputPath)
                );
                
                exec($command . ' 2>&1', $output2, $returnVar2);
                
                if ($returnVar2 !== 0 || !Storage::exists($outputPath)) {
                    throw new Exception("No se pudo convertir el PDF a imagen con ningún método");
                }
            }
            
            return $outputPath;
            
        } catch (Exception $e) {
            throw new Exception("No se pudo convertir el PDF a imagen: " . $e->getMessage());
        }
    }

    /**
     * Extraer texto de la imagen usando OCR
     */
    private function extractTextFromImage(string $imagePath): string
    {
        $fullPath = Storage::path($imagePath);
        
        try {
            // Preprocesar imagen para mejorar OCR
            $preprocessedPath = $this->preprocessImage($fullPath);
            
            // Usar tesseract-ocr vía exec con configuraciones optimizadas
            $output = [];
            $returnVar = 0;
            
            // Ejecutar tesseract con idioma español y PSM 6 (bloque uniforme de texto)
            // PSM 6 funciona mejor para certificados y documentos estructurados
            $command = sprintf(
                'tesseract %s stdout -l spa --psm 6 --oem 3 2>&1',
                escapeshellarg($preprocessedPath)
            );
            
            exec($command, $output, $returnVar);
            
            // Limpiar imagen preprocesada temporal
            if (file_exists($preprocessedPath) && $preprocessedPath !== $fullPath) {
                @unlink($preprocessedPath);
            }
            
            if ($returnVar !== 0) {
                throw new Exception("Tesseract falló con código: " . $returnVar);
            }
            
            $text = implode("\n", $output);
            
            if (empty(trim($text))) {
                throw new Exception("No se pudo extraer texto del documento");
            }
            
            return strtolower($text); // Convertir a minúsculas para búsqueda
            
        } catch (Exception $e) {
            throw new Exception("Error en OCR: " . $e->getMessage());
        }
    }

    /**
     * Preprocesar imagen para mejorar precisión del OCR
     */
    private function preprocessImage(string $imagePath): string
    {
        try {
            $preprocessedPath = dirname($imagePath) . '/preprocessed_' . basename($imagePath);
            
            // Usar ImageMagick para mejorar la imagen:
            // -normalize: ajustar contraste automáticamente
            // -sharpen 0x1: aumentar nitidez
            // -type Grayscale: convertir a escala de grises
            // -depth 8: profundidad de color
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
     * Validar certificado por palabras clave
     * Sistema de puntuación:
     * - Palabras de institución: +20 puntos
     * - Tipo de documento: +15 puntos
     * - Estado del alumno: +15 puntos
     * - Información complementaria: +10 puntos
     * - Mínimo para aprobar: 40 puntos
     */
    private function validateCertificate(string $text): array
    {
        // Palabras de institución educativa (20 pts - una sola coincidencia)
        $institutionKeywords = [
            'universidad',
            'instituto',
            'colegio',
            'escuela',
            'unab', // Universidad Andrés Bello
            'andrés bello',
            'andres bello',
        ];
        
        // Tipo de documento (15 pts - una sola coincidencia)
        $documentTypeKeywords = [
            'constancia',
            'certificado',
            'certificación',
            'comprobante',
            'documento',
        ];
        
        // Estado de alumno (15 pts - una sola coincidencia)
        $studentStatusKeywords = [
            'alumno regular',
            'estudiante regular',
            'alumno',
            'estudiante',
            'matriculado',
            'inscrito',
        ];
        
        // Palabras complementarias (10 pts c/u, múltiples posibles)
        $complementaryKeywords = [
            '2024',
            '2025',
            'semestre',
            'carrera',
            'ingeniería',
            'ingenieria',
            'programa',
            'matrícula',
            'matricula',
            'vigente',
        ];

        $score = 0;
        $foundKeywords = [];

        // Buscar institución (solo una puntuación)
        $institutionFound = false;
        foreach ($institutionKeywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                if (!$institutionFound) {
                    $score += 20;
                    $institutionFound = true;
                }
                $foundKeywords[] = $keyword;
            }
        }

        // Buscar tipo de documento (solo una puntuación)
        $documentTypeFound = false;
        foreach ($documentTypeKeywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                if (!$documentTypeFound) {
                    $score += 15;
                    $documentTypeFound = true;
                }
                $foundKeywords[] = $keyword;
            }
        }

        // Buscar estado de alumno (solo una puntuación)
        $studentStatusFound = false;
        foreach ($studentStatusKeywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                if (!$studentStatusFound) {
                    $score += 15;
                    $studentStatusFound = true;
                }
                $foundKeywords[] = $keyword;
            }
        }

        // Buscar palabras complementarias (múltiples posibles, 10 pts c/u)
        foreach ($complementaryKeywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                $score += 10;
                $foundKeywords[] = $keyword;
            }
        }

        // Determinar estado y razón de rechazo
        // Bajamos el umbral a 40 puntos (institución + tipo documento + status = 50)
        if ($score >= 40) {
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
        ];
    }

    /**
     * Generar razón de rechazo basada en score y palabras encontradas
     */
    private function generateRejectionReason(int $score, array $foundKeywords): string
    {
        if ($score === 0) {
            return 'El documento no parece ser un certificado de alumno regular. Por favor, sube un certificado oficial emitido por tu universidad o instituto.';
        }
        
        if ($score < 20) {
            return 'El documento no contiene información suficiente. Asegúrate de que sea un certificado oficial que incluya: nombre de la institución educativa, tipo de documento (constancia/certificado), y condición de alumno regular.';
        }
        
        if ($score < 40) {
            return 'Falta información importante en el certificado. Verifica que el documento incluya: nombre de la institución, tipo de documento, y tu condición como alumno regular o estudiante activo.';
        }
        
        // Score entre 45 y 59
        $missingElements = [];
        
        if (!in_array('universidad', $foundKeywords) && !in_array('instituto', $foundKeywords)) {
            $missingElements[] = 'nombre de la institución educativa';
        }
        
        if (!in_array('constancia', $foundKeywords) && !in_array('certificado', $foundKeywords)) {
            $missingElements[] = 'tipo de documento (constancia o certificado)';
        }
        
        if (!in_array('alumno regular', $foundKeywords)) {
            $missingElements[] = 'condición de alumno regular';
        }
        
        if (!in_array('2024', $foundKeywords) && !in_array('2025', $foundKeywords)) {
            $missingElements[] = 'año vigente (2024 o 2025)';
        }
        
        if (!empty($missingElements)) {
            return 'El certificado está incompleto. Falta: ' . implode(', ', $missingElements) . '. Por favor, sube un certificado que incluya toda la información requerida.';
        }
        
        return 'El certificado no cumple con todos los requisitos de validación. Asegúrate de subir un documento oficial, legible y vigente.';
    }
}
