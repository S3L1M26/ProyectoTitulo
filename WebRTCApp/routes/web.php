<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Mentor\MentorController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    // Rutas para estudiantes con monitoreo de performance
    Route::middleware(['role:student', 'performance'])->group(function () {
        Route::get('/student/dashboard', [StudentController::class, 'index'])->name('student.dashboard');
    });

    // Rutas para mentores con monitoreo de performance
    Route::middleware(['role:mentor', 'performance'])->group(function () {
        Route::get('/mentor/dashboard', [MentorController::class, 'index'])->name('mentor.dashboard');
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