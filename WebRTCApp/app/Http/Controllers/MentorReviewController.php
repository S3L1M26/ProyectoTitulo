<?php

namespace App\Http\Controllers;

use App\Models\Mentor;
use App\Models\MentorReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MentorReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
        // Se recomienda aplicar 'role:student' en la ruta
    }

    public function store(Request $request, Mentor $mentor)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $userId = Auth::id();

        MentorReview::updateOrCreate(
            [
                'mentor_id' => $mentor->id,
                'user_id' => $userId,
            ],
            [
                'rating' => (int) $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        // El promedio se actualiza vía Observer, pero se puede forzar por seguridad
        $mentor->updateAverageRating();

        return back()->with('success', '¡Gracias por tu valoración!');
    }
}
