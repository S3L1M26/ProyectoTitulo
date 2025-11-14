<?php

namespace App\Http\Controllers;

use App\Models\Mentor;
use App\Models\MentorReview;
use App\Models\Mentoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MentorReviewController extends Controller
{
    public function store(Request $request, Mentor $mentor)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $userId = Auth::id();

        // Permitir actualizar si ya existe reseña del usuario para este mentor
        $existing = MentorReview::where('mentor_id', $mentor->id)
            ->where('user_id', $userId)
            ->first();

        if (!$existing) {
            // Primera reseña: exigir mentoría concluida entre este aprendiz (user) y el mentor (user_id del mentor)
            $hasCompletedMentoria = Mentoria::where('mentor_id', $mentor->user_id)
                ->where('aprendiz_id', $userId)
                ->where('estado', 'completada')
                ->exists();

            if (!$hasCompletedMentoria) {
                return response()->json([
                    'errors' => [
                        'rating' => 'Solo puedes reseñar tras concluir una mentoría con este mentor.'
                    ]
                ], 422);
            }
        }

        $review = MentorReview::updateOrCreate(
            [
                'mentor_id' => $mentor->id,
                'user_id' => $userId,
            ],
            [
                'rating' => (int) $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        // El promedio se actualiza vía Observer
        $mentor->updateAverageRating();

        // Invalidar caché de sugerencias de mentores para todos los estudiantes
        // ya que el promedio del mentor ha cambiado
        $this->invalidateMentorSuggestionsCache();

        return response()->json([
            'success' => true,
            'message' => 'Reseña guardada correctamente',
            'review' => $review
        ]);
    }

    /**
     * Invalidar todos los caches de sugerencias de mentores
     * para asegurar que se reflejen cambios en ratings
     */
    private function invalidateMentorSuggestionsCache()
    {
        try {
            $redis = Cache::store('redis')->getRedis();
            $prefix = config('cache.prefix') ?: 'laravel_cache';
            
            // Obtener todas las claves con patrón mentor_*
            $pattern = $prefix . ':mentor_*';
            $cursor = 0;
            $cleared = 0;
            
            do {
                // Usar SCAN para no bloquear Redis con KEYS (mejor para prod)
                $result = $redis->scan($cursor, 'MATCH', $pattern, 'COUNT', 100);
                $cursor = (int)$result[0];
                $keys = $result[1] ?? [];
                
                foreach ($keys as $key) {
                    $redis->del($key);
                    $cleared++;
                }
            } while ($cursor !== 0);
            
            if ($cleared > 0) {
                Log::info("Cache invalidation: Cleared $cleared mentor cache keys");
            }
        } catch (\Exception $e) {
            Log::warning('Error limpiando cache de mentores: ' . $e->getMessage());
        }
    }
}
