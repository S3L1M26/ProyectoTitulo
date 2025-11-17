<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoringMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Habilitar query log solo en desarrollo
        if (config('app.debug')) {
            DB::enableQueryLog();
            
            // SLOW QUERY LISTENER: Log individual queries >300ms
            DB::listen(function ($query) use ($request) {
                $time = $query->time; // milliseconds
                
                if ($time > 300) {
                    Log::warning('🐌 SLOW QUERY DETECTED', [
                        'route' => $request->route()?->getName(),
                        'url' => $request->fullUrl(),
                        'time_ms' => round($time, 2),
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'connection' => $query->connectionName,
                    ]);
                }
            });
        }
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;
        $queryCount = config('app.debug') ? count(DB::getQueryLog()) : 0;
        
        // Detectar rutas críticas que deben mantenerse optimizadas
        $criticalRoutes = [
            'student.dashboard',
            'mentor.dashboard', 
            'profile.show'
        ];
        
        $routeName = $request->route()?->getName();
        $isCriticalRoute = in_array($routeName, $criticalRoutes);
        
        // Umbrales de performance
        $thresholds = [
            'execution_time' => $isCriticalRoute ? 500 : 1000, // ms
            'query_count' => $isCriticalRoute ? 5 : 10,
            'memory_usage' => 50 * 1024 * 1024 // 50MB
        ];
        
        // Log performance issues
        $issues = [];
        
        if ($executionTime > $thresholds['execution_time']) {
            $issues[] = "Slow execution: {$executionTime}ms";
        }
        
        if ($queryCount > $thresholds['query_count']) {
            $issues[] = "Too many queries: {$queryCount}";
        }
        
        if ($memoryUsage > $thresholds['memory_usage']) {
            $memoryMB = round($memoryUsage / 1024 / 1024, 2);
            $issues[] = "High memory usage: {$memoryMB}MB";
        }
        
        if (!empty($issues) && config('app.debug')) {
            $queries = DB::getQueryLog();
            
            // Get top 3 slowest queries for additional context
            $slowestQueries = collect($queries)
                ->sortByDesc('time')
                ->take(3)
                ->map(fn($q) => [
                    'time_ms' => round($q['time'], 2),
                    'sql' => $q['query'],
                    'bindings' => $q['bindings']
                ])
                ->values()
                ->all();
            
            Log::warning('⚠️ PERFORMANCE DEGRADATION DETECTED', [
                'route' => $routeName,
                'url' => $request->fullUrl(),
                'execution_time_ms' => round($executionTime, 2),
                'query_count' => $queryCount,
                'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'issues' => $issues,
                'is_critical_route' => $isCriticalRoute,
                'slowest_queries' => $slowestQueries
            ]);
        }
        
        // Add performance headers for debugging
        if (config('app.debug')) {
            $response->headers->set('X-Performance-Time', round($executionTime, 2));
            $response->headers->set('X-Performance-Queries', $queryCount);
            $response->headers->set('X-Performance-Memory', round($memoryUsage / 1024 / 1024, 2));
        }
        
        return $response;
    }
}
