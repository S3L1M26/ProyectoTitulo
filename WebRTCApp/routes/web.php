<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Sip\SipUserController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Admin\AdminController;
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
    // Rutas para estudiantes
    Route::middleware('role:student')->group(function () {
        Route::get('/student/dashboard', function () {
            return Inertia::render('Student/Dashboard');
        })->name('student.dashboard');
    });

    // Rutas para mentores
    Route::middleware('role:mentor')->group(function () {
        Route::get('/mentor/dashboard', function () {
            return Inertia::render('Mentor/Dashboard');
        })->name('mentor.dashboard');
    });
});

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

//rutas usuario
Route::middleware(['auth', 'userMiddleware'])->group(function () {

    Route::get('/dashboard', [UserController::class, 'index'])->name('dashboard');

});

//rutas admin
Route::middleware(['auth', 'adminMiddleware'])->group(function () {

    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::delete('/admin/users/{id}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
    Route::get('/sip-users/create', [SipUserController::class, 'create'])->name('sip-users.create');
    Route::post('/sip-users', [SipUserController::class, 'store'])->name('sip-users.store');
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/admin/users/{id}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
    Route::put('/admin/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::put('/admin/users/{id}/password', [AdminController::class, 'resetPassword'])->name('admin.users.reset-password');
    Route::put('/admin/users/{id}/sip-password', [AdminController::class, 'resetSipPassword'])->name('admin.users.reset-sip-password');
});