<?php

namespace App\Observers;

use App\Models\StudentDocument;

class StudentDocumentObserver
{
    /**
     * Handle the StudentDocument "created" event.
     */
    public function created(StudentDocument $studentDocument): void
    {
        //
    }

    /**
     * Handle the StudentDocument "updated" event.
     * 
     * Actualiza automáticamente certificate_verified cuando el certificado es aprobado
     */
    public function updated(StudentDocument $studentDocument): void
    {
        // Verificar si el estado cambió a 'approved'
        if ($studentDocument->wasChanged('status') && $studentDocument->status === 'approved') {
            // Obtener el aprendiz asociado al usuario
            $aprendiz = $studentDocument->user->aprendiz;
            
            if ($aprendiz) {
                $aprendiz->update([
                    'certificate_verified' => true,
                ]);
                
                logger()->info('Certificate verified for aprendiz', [
                    'aprendiz_id' => $aprendiz->id,
                    'user_id' => $studentDocument->user_id,
                    'document_id' => $studentDocument->id,
                ]);
            }
        }
        
        // Si el certificado es rechazado o invalidado, quitar verificación
        if ($studentDocument->wasChanged('status') && 
            in_array($studentDocument->status, ['rejected', 'invalid'])) {
            
            $aprendiz = $studentDocument->user->aprendiz;
            
            if ($aprendiz && $aprendiz->certificate_verified) {
                // Solo remover si no hay otro certificado aprobado
                $hasOtherApprovedCertificate = $studentDocument->user
                    ->studentDocuments()
                    ->where('id', '!=', $studentDocument->id)
                    ->where('status', 'approved')
                    ->exists();
                
                if (!$hasOtherApprovedCertificate) {
                    $aprendiz->update([
                        'certificate_verified' => false,
                    ]);
                    
                    logger()->info('Certificate verification removed for aprendiz', [
                        'aprendiz_id' => $aprendiz->id,
                        'user_id' => $studentDocument->user_id,
                        'document_id' => $studentDocument->id,
                    ]);
                }
            }
        }
    }

    /**
     * Handle the StudentDocument "deleted" event.
     * 
     * Remover verificación si se elimina un certificado aprobado
     */
    public function deleted(StudentDocument $studentDocument): void
    {
        if ($studentDocument->status === 'approved') {
            $aprendiz = $studentDocument->user->aprendiz;
            
            if ($aprendiz && $aprendiz->certificate_verified) {
                // Solo remover si no hay otro certificado aprobado
                $hasOtherApprovedCertificate = $studentDocument->user
                    ->studentDocuments()
                    ->where('id', '!=', $studentDocument->id)
                    ->where('status', 'approved')
                    ->exists();
                
                if (!$hasOtherApprovedCertificate) {
                    $aprendiz->update([
                        'certificate_verified' => false,
                    ]);
                    
                    logger()->info('Certificate verification removed after deletion', [
                        'aprendiz_id' => $aprendiz->id,
                        'user_id' => $studentDocument->user_id,
                        'document_id' => $studentDocument->id,
                    ]);
                }
            }
        }
    }

    /**
     * Handle the StudentDocument "restored" event.
     */
    public function restored(StudentDocument $studentDocument): void
    {
        //
    }

    /**
     * Handle the StudentDocument "force deleted" event.
     */
    public function forceDeleted(StudentDocument $studentDocument): void
    {
        //
    }
}
