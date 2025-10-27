<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class RoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot()
    {
        Gate::define('is-student', function(User $user) {
            return $user->role === 'student';
        });

        Gate::define('is-mentor', function(User $user) {
            return $user->role === 'mentor';
        });
    }
}