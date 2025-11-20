<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $role = $user->role ?? null;

        $redirectRoute = match($role) {
            'mentor' => 'mentor.dashboard',
            'student' => 'student.dashboard',
            'admin' => 'admin.dashboard',
            default => 'login'
        };

        try {
            if ($user->hasVerifiedEmail()) {
                return redirect()->intended(route($redirectRoute, absolute: false).'?verified=1');
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            return redirect()->intended(route($redirectRoute, absolute: false).'?verified=1');
        } catch (\Throwable $e) {
            // Log full exception to help diagnose production-only failures
            \Illuminate\Support\Facades\Log::error('Email verification handler failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fail gracefully: send user to login with an error message
            return redirect()->route('login')->with('error', 'Ocurrió un error al verificar el correo. Por favor, inicia sesión e intenta nuevamente.');
        }
    }
}
