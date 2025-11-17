<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseQueryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only log in development environment
        if (config('app.debug')) {
            // Log all queries that take longer than 300ms
            DB::listen(function ($query) {
                $threshold = 300; // milliseconds
                
                if ($query->time > $threshold) {
                    Log::channel('daily')->warning('🐌 SLOW QUERY DETECTED', [
                        'time_ms' => round($query->time, 2),
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'connection' => $query->connectionName,
                        'caller' => $this->getCaller(),
                    ]);
                }
            });

            // Optional: Log all queries in extreme debug mode
            if (config('app.log_all_queries', false)) {
                DB::listen(function ($query) {
                    Log::channel('daily')->debug('📊 DB QUERY', [
                        'time_ms' => round($query->time, 2),
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                    ]);
                });
            }
        }
    }

    /**
     * Get the caller information from the stack trace
     */
    private function getCaller(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        
        // Find the first call outside of Laravel's internals
        foreach ($trace as $call) {
            if (isset($call['file']) && 
                !str_contains($call['file'], 'vendor/laravel') &&
                !str_contains($call['file'], 'vendor/illuminate')) {
                
                $file = str_replace(base_path(), '', $call['file']);
                $line = $call['line'] ?? 0;
                return "{$file}:{$line}";
            }
        }
        
        return 'Unknown';
    }
}
