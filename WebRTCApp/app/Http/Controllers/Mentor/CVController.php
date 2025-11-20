<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMentorCVJob;
use App\Models\MentorDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CVController extends Controller
{
    /**
     * Upload mentor CV.
     */
    public function upload(Request $request)
    {
        // Validación
        $request->validate([
            'cv' => 'required|file|mimes:pdf|max:10240', // 10MB en kilobytes
            'is_public' => 'nullable|boolean',
        ], [
            'cv.required' => 'Debes seleccionar un archivo.',
            'cv.mimes' => 'Solo se permiten archivos PDF.',
            'cv.max' => 'El archivo no debe superar los 10MB.',
        ]);

        $user = $request->user();

        // Validar que el usuario sea mentor
        if ($user->role !== 'mentor' || !$user->mentor) {
            return back()->withErrors(['cv' => 'Solo los mentores pueden subir CVs.']);
        }

        // Guardar archivo en storage/app/mentor_cvs/{user_id}/
        $file = $request->file('cv');
        $fileName = time() . '_cv.pdf';
        $disk = config('filesystems.default', 'local');
        $filePath = $file->storeAs(
            "mentor_cvs/{$user->id}",
            $fileName,
            $disk
        );

        // Crear registro en mentor_documents con status pending
        $document = MentorDocument::create([
            'user_id' => $user->id,
            'file_path' => $filePath,
            'status' => 'pending',
            'is_public' => $request->boolean('is_public', true), // Default true
        ]);

        // Disparar Job asíncrono para procesamiento OCR
        ProcessMentorCVJob::dispatch($document);

        return back()->with('success', 'CV recibido. Lo estamos procesando...');
    }

    /**
     * Show mentor CV (public view).
     * 
     * @param int $mentorId
     * @return BinaryFileResponse|Response
     */
    public function show(int $mentorId)
    {
        // Buscar el mentor por ID
        $mentor = User::where('role', 'mentor')
            ->whereHas('mentor')
            ->findOrFail($mentorId);

        // Buscar el CV más reciente aprobado y público
        $document = $mentor->mentorDocuments()
            ->where('status', 'approved')
            ->where('is_public', true)
            ->latest('processed_at')
            ->first();

        // Si no hay CV aprobado y público, retornar 404
        if (!$document) {
            abort(404, 'CV no disponible');
        }

        $disk = config('filesystems.default', 'local');

        // Verificar que el archivo existe en el disco configurado
        if (!Storage::disk($disk)->exists($document->file_path)) {
            abort(404, 'Archivo no encontrado');
        }

        // Stream the file from the configured disk to the response
        $stream = Storage::disk($disk)->readStream($document->file_path);
        if ($stream === false) {
            abort(500, 'No se pudo abrir el archivo para lectura');
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="CV_Mentor_' . $mentor->name . '.pdf"',
        ]);
    }

    /**
     * Toggle CV visibility (public/private).
     */
    public function toggleVisibility(Request $request)
    {
        $request->validate([
            'is_public' => 'required|boolean',
        ]);

        $user = $request->user();

        // Validar que el usuario sea mentor
        if ($user->role !== 'mentor' || !$user->mentor) {
            return back()->withErrors(['cv' => 'Solo los mentores pueden modificar la visibilidad del CV.']);
        }

        // Buscar el CV más reciente aprobado
        $document = $user->mentorDocuments()
            ->where('status', 'approved')
            ->latest('processed_at')
            ->first();

        if (!$document) {
            return back()->withErrors(['cv' => 'No tienes un CV aprobado.']);
        }

        // Actualizar visibilidad
        $document->update([
            'is_public' => $request->boolean('is_public'),
        ]);

        $message = $request->boolean('is_public') 
            ? 'Tu CV ahora es visible públicamente.' 
            : 'Tu CV ahora está oculto.';

        return back()->with('success', $message);
    }
}
