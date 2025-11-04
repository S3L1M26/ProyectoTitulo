<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessStudentCertificateJob;
use App\Models\StudentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    /**
     * Upload student certificate.
     */
    public function upload(Request $request)
    {
        // Validación
        $request->validate([
            'certificate' => 'required|file|mimes:pdf|max:5120', // 5MB en kilobytes
        ], [
            'certificate.required' => 'Debes seleccionar un archivo.',
            'certificate.mimes' => 'Solo se permiten archivos PDF.',
            'certificate.max' => 'El archivo no debe superar los 5MB.',
        ]);

        $user = auth()->user();

        // Guardar archivo en storage/app/student_certificates/{user_id}/
        $file = $request->file('certificate');
        $fileName = time() . '_certificate.pdf';
        $filePath = $file->storeAs(
            "student_certificates/{$user->id}",
            $fileName,
            'local' // Usar disco local
        );

        // Crear registro en student_documents con status pending
        $document = StudentDocument::create([
            'user_id' => $user->id,
            'file_path' => $filePath,
            'status' => 'pending',
        ]);

        // Disparar Job asíncrono para procesamiento OCR
        ProcessStudentCertificateJob::dispatch($document);

        return back()->with('success', 'Certificado recibido. Lo estamos procesando...');
    }
}
