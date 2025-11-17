<?php

namespace App\Observers;

use App\Models\MentorReview;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MentorReviewObserver
{
    public function created(MentorReview $review): void
    {
        $review->mentor?->updateAverageRating();
        $this->invalidateMentorCache();
    }

    public function updated(MentorReview $review): void
    {
        $review->mentor?->updateAverageRating();
        $this->invalidateMentorCache();
    }

    public function deleted(MentorReview $review): void
    {
        $review->mentor?->updateAverageRating();
        $this->invalidateMentorCache();
    }

    /**
     * Invalidar caché de sugerencias de mentores usando SCAN
     * (mejor que KEYS para prod, no bloquea Redis)
     */
    private function invalidateMentorCache(): void
    {
        try {
            $redis = Cache::store('redis')->getRedis();
            $prefix = config('cache.prefix') ?: 'laravel_cache';
            
            // Obtener todas las claves con patrón mentor_*
            $pattern = $prefix . ':mentor_*';
            $cursor = 0;
            $cleared = 0;
            
            do {
                // SCAN es mejor que KEYS en producción
                $result = $redis->scan($cursor, 'MATCH', $pattern, 'COUNT', 100);
                $cursor = (int)$result[0];
                $keys = $result[1] ?? [];
                
                foreach ($keys as $key) {
                    $redis->del($key);
                    $cleared++;
                }
            } while ($cursor !== 0);
            
            if ($cleared > 0) {
                Log::debug("Observer: Cleared $cleared mentor cache keys");
            }
        } catch (\Exception $e) {
            Log::warning('Observer error limpiando cache de mentores: ' . $e->getMessage());
        }
    }
}
