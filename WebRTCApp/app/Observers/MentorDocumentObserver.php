<?php

namespace App\Observers;

use App\Models\MentorDocument;

class MentorDocumentObserver
{
    /**
     * Handle the MentorDocument "created" event.
     */
    public function created(MentorDocument $mentorDocument): void
    {
        //
    }

    /**
     * Handle the MentorDocument "updated" event.
     * 
     * Actualiza automáticamente cv_verified cuando el CV es aprobado
     */
    public function updated(MentorDocument $mentorDocument): void
    {
        logger()->info('Observer updated called', [
            'document_id' => $mentorDocument->id,
            'status' => $mentorDocument->status,
            'wasChanged' => $mentorDocument->wasChanged('status'),
            'changes' => $mentorDocument->getChanges(),
        ]);

        // Verificar si el estado cambió a 'approved'
        if ($mentorDocument->wasChanged('status') && $mentorDocument->status === 'approved') {
            // Obtener el mentor asociado al usuario
            $mentor = $mentorDocument->user->mentor;
            
            logger()->info('Status changed to approved', [
                'user_id' => $mentorDocument->user_id,
                'mentor_found' => $mentor ? 'yes' : 'no',
                'mentor_id' => $mentor?->id,
            ]);
            
            if ($mentor) {
                $mentor->update([
                    'cv_verified' => true,
                ]);
                
                logger()->info('CV verified for mentor', [
                    'mentor_id' => $mentor->id,
                    'user_id' => $mentorDocument->user_id,
                    'document_id' => $mentorDocument->id,
                    'cv_verified' => $mentor->fresh()->cv_verified,
                ]);
            }
        }
        
        // Si el CV es rechazado o invalidado, quitar verificación
        if ($mentorDocument->wasChanged('status') && 
            in_array($mentorDocument->status, ['rejected', 'invalid'])) {
            
            $mentor = $mentorDocument->user->mentor;
            
            if ($mentor && $mentor->cv_verified) {
                // Solo remover si no hay otro CV aprobado
                $hasOtherApprovedCV = $mentorDocument->user
                    ->mentorDocuments()
                    ->where('id', '!=', $mentorDocument->id)
                    ->where('status', 'approved')
                    ->exists();
                
                if (!$hasOtherApprovedCV) {
                    $mentor->update([
                        'cv_verified' => false,
                    ]);
                    
                    logger()->info('CV verification removed for mentor', [
                        'mentor_id' => $mentor->id,
                        'user_id' => $mentorDocument->user_id,
                        'document_id' => $mentorDocument->id,
                    ]);
                }
            }
        }
    }

    /**
     * Handle the MentorDocument "deleted" event.
     * 
     * Remover verificación si se elimina un CV aprobado
     */
    public function deleted(MentorDocument $mentorDocument): void
    {
        if ($mentorDocument->status === 'approved') {
            $mentor = $mentorDocument->user->mentor;
            
            if ($mentor && $mentor->cv_verified) {
                // Solo remover si no hay otro CV aprobado
                $hasOtherApprovedCV = $mentorDocument->user
                    ->mentorDocuments()
                    ->where('id', '!=', $mentorDocument->id)
                    ->where('status', 'approved')
                    ->exists();
                
                if (!$hasOtherApprovedCV) {
                    $mentor->update([
                        'cv_verified' => false,
                    ]);
                    
                    logger()->info('CV verification removed on deletion', [
                        'mentor_id' => $mentor->id,
                        'user_id' => $mentorDocument->user_id,
                        'document_id' => $mentorDocument->id,
                    ]);
                }
            }
        }
    }

    /**
     * Handle the MentorDocument "restored" event.
     */
    public function restored(MentorDocument $mentorDocument): void
    {
        // Si se restaura un CV aprobado, restaurar verificación
        if ($mentorDocument->status === 'approved') {
            $mentor = $mentorDocument->user->mentor;
            
            if ($mentor) {
                $mentor->update([
                    'cv_verified' => true,
                ]);
                
                logger()->info('CV verification restored', [
                    'mentor_id' => $mentor->id,
                    'user_id' => $mentorDocument->user_id,
                    'document_id' => $mentorDocument->id,
                ]);
            }
        }
    }

    /**
     * Handle the MentorDocument "force deleted" event.
     */
    public function forceDeleted(MentorDocument $mentorDocument): void
    {
        // Mismo comportamiento que deleted
        $this->deleted($mentorDocument);
    }
}
