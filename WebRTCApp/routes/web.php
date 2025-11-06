<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\CertificateController;
use App\Http\Controllers\Mentor\MentorController;
use App\Http\Controllers\SolicitudMentoriaController;
use App\Http\Controllers\MentoriaController;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Ruta pública para ver CV de mentor (con rate limiting)
Route::get('/mentor/{mentor}/cv', [\App\Http\Controllers\Mentor\CVController::class, 'show'])
    ->middleware('throttle:10,1') // 10 peticiones por minuto
    ->name('mentor.cv.show');

Route::middleware(['auth', 'verified'])->group(function () {
    // Rutas para estudiantes con monitoreo de performance
    Route::middleware(['role:student', 'performance'])->group(function () {
        Route::get('/student/dashboard', [StudentController::class, 'index'])->name('student.dashboard');
        Route::post('/student/certificate/upload', [CertificateController::class, 'upload'])->name('student.certificate.upload');
    });

    // Rutas para mentores con monitoreo de performance
    Route::middleware(['role:mentor', 'performance'])->group(function () {
        Route::get('/mentor/dashboard', [MentorController::class, 'index'])->name('mentor.dashboard');
        Route::post('/mentor/cv/upload', [\App\Http\Controllers\Mentor\CVController::class, 'upload'])->name('mentor.cv.upload');
        Route::post('/mentor/cv/toggle-visibility', [\App\Http\Controllers\Mentor\CVController::class, 'toggleVisibility'])->name('mentor.cv.toggle-visibility');
    });
});

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Ruta para obtener áreas de interés
    Route::get('/profile/areas-interes', [ProfileController::class, 'getAreasInteres'])
        ->name('profile.areas-interes');
    
    // Rutas para perfil de aprendiz
    Route::patch('/profile/aprendiz', [ProfileController::class, 'updateAprendizProfile'])
        ->name('profile.update-aprendiz');
    
    // Rutas para perfil de mentor
    Route::patch('/profile/mentor', [ProfileController::class, 'updateMentorProfile'])
        ->name('profile.update-mentor');
    
    Route::post('/profile/mentor/toggle-disponibilidad', [ProfileController::class, 'toggleMentorDisponibilidad'])
        ->name('profile.mentor.toggle-disponibilidad');
    
    Route::get('/api/areas-interes', [ProfileController::class, 'getAreasInteres'])
        ->name('api.areas-interes');
});

// Rutas para solicitudes de mentoría
Route::middleware(['auth', 'verified'])->group(function () {
    // Ruta para estudiantes: crear solicitud de mentoría
    Route::post('/solicitud-mentoria', [SolicitudMentoriaController::class, 'store'])
        ->middleware('role:student')
        ->name('solicitud-mentoria.store');
    
    // Rutas para estudiantes: ver sus solicitudes y notificaciones
    Route::middleware('role:student')->group(function () {
        Route::get('/student/solicitudes', [SolicitudMentoriaController::class, 'misSolicitudes'])
            ->name('student.solicitudes');
        
        Route::get('/student/notifications', [SolicitudMentoriaController::class, 'misNotificaciones'])
            ->name('student.notifications');
        
        Route::post('/student/notifications/{id}/read', [SolicitudMentoriaController::class, 'marcarComoLeida'])
            ->name('student.notifications.read');
        
        Route::post('/student/notifications/read-all', [SolicitudMentoriaController::class, 'marcarTodasComoLeidas'])
            ->name('student.notifications.read-all');
    });
    
    // Rutas para mentores: gestionar solicitudes
    Route::middleware('role:mentor')->group(function () {
        Route::get('/mentor/solicitudes', [SolicitudMentoriaController::class, 'index'])
            ->name('mentor.solicitudes.index');
        
        Route::post('/mentor/solicitudes/{id}/accept', [SolicitudMentoriaController::class, 'accept'])
            ->name('mentor.solicitudes.accept');
        
        Route::post('/mentor/solicitudes/{id}/reject', [SolicitudMentoriaController::class, 'reject'])
            ->name('mentor.solicitudes.reject');

        // Confirmar mentoría (crea reunión Zoom y guarda)
        Route::post('/mentorias/solicitudes/{solicitud}/confirmar', [MentoriaController::class, 'confirmar'])
            ->name('mentorias.confirmar');
    });
});

require __DIR__.'/auth.php';

// Rutas admin
Route::middleware(['auth', 'adminMiddleware'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/users', [AdminController::class, 'index'])->name('admin.users');
    Route::get('/admin/users/{user}', [AdminController::class, 'show'])->name('admin.users.show');
    Route::get('/admin/users/{user}/edit', [AdminController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [AdminController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
});

// Endpoint público autenticado para generar enlace (preview) con rate limiting
Route::middleware(['auth:sanctum', 'throttle:10,1'])->group(function () {
    Route::post('/api/mentorias/generar-enlace', [MentoriaController::class, 'generarEnlacePreview'])
        ->name('api.mentorias.generar-enlace');
});

// Unirse a una mentoría (mentor o aprendiz)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/mentorias/{mentoria}/unirse', [MentoriaController::class, 'unirse'])
        ->name('mentorias.unirse');
});