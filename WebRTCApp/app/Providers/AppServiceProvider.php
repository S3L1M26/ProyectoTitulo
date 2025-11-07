<?php

namespace App\Providers;

use App\Models\MentorDocument;
use App\Models\StudentDocument;
use App\Observers\MentorDocumentObserver;
use App\Observers\StudentDocumentObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use App\Policies\MentoriaPolicy;
use App\Models\Mentoria;
use App\Models\SolicitudMentoria;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        
        // Registrar observers
        StudentDocument::observe(StudentDocumentObserver::class);
        MentorDocument::observe(MentorDocumentObserver::class);

        // Gates de mentorías
        Gate::define('mentoria.confirmar', [MentoriaPolicy::class, 'confirmar']); // SolicitudMentoria
        Gate::define('mentoria.unirse', [MentoriaPolicy::class, 'unirse']); // Mentoria
    }
}
