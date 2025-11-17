<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
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

        // Verificar si hay un lock activo para prevenir spam
        $lockKey = 'verify_email_notification_' . $request->user()->id;
        
        if (Cache::has($lockKey)) {
            $ttl = Cache::get($lockKey . '_ttl', 60);
            return back()->with('status', 'verification-rate-limited');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
