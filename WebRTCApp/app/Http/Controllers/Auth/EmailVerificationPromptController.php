<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $redirectRoute = match(Auth::user()->role) {
            'mentor' => 'mentor.dashboard',
            'student' => 'student.dashboard',
            'admin' => 'admin.dashboard',
            default => 'login'
        };
        
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route($redirectRoute, absolute: false));
        }

        // Detectar si es un usuario de seeder y enviar automáticamente
        $autoSend = $this->isSeederUser($request->user());
        
        return Inertia::render('Auth/VerifyEmail', [
            'status' => session('status'),
            'auto_send' => $autoSend
        ]);
    }

    /**
     * Determinar si es un usuario generado por seeders
     */
    private function isSeederUser($user): bool
    {
        $email = strtolower($user->email);
        $name = strtolower($user->name);

        // Emails específicos de seeders
        $seederEmails = [
            'mentor@gmail.com',
            'aprendiz@gmail.com',
            'admin@gmail.com',
            'estudiante.test@example.com',
            'mentor.test@example.com',
            'estudiante.incompleto@example.com'
        ];

        // Verificar emails exactos de seeders
        if (in_array($email, $seederEmails)) {
            return true;
        }

        // Patrones para detectar usuarios de seeder
        $testPatterns = [
            '.test@',
            '@example.com',
            '@test.com',
            'test.',
            '.test'
        ];

        // Verificar si el email contiene patrones de test
        foreach ($testPatterns as $pattern) {
            if (str_contains($email, $pattern)) {
                return true;
            }
        }

        // Verificar nombres que indican usuarios de test
        $testNamePatterns = [
            'test',
            'mentor',
            'aprendiz',
            'estudiante',
            'admin'
        ];

        foreach ($testNamePatterns as $pattern) {
            if (str_contains($name, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
