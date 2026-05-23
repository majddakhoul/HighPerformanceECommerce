<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PerformanceMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $startMemory = memory_get_usage();
        $traceId = Str::uuid()->toString();

        $request->headers->set('X-Trace-Id', $traceId);

        $queryCount = 0;
        $totalQueryTime = 0;
        $slowestQuery = null;
        $slowestTime = 0;

        DB::listen(function ($query) use (&$queryCount, &$totalQueryTime, &$slowestQuery, &$slowestTime) {
            $queryCount++;
            $totalQueryTime += $query->time;
            if ($query->time > $slowestTime) {
                $slowestTime = $query->time;
                $slowestQuery = [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                ];
            }
        });

        Log::channel('performance')->info('REQUEST_START', [
            'trace_id' => $traceId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route' => Route::currentRouteName() ?? 'unnamed',
            'action' => optional(Route::currentRouteAction()) ?? 'closure',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_size' => strlen($request->getContent()) ?: 0,
            'memory_start_kb' => round($startMemory / 1024, 2),
        ]);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);
        $endMemory = memory_get_usage();
        $memoryUsed = round(($endMemory - $startMemory) / 1024, 2);
        $peakMemory = round(memory_get_peak_usage() / 1024, 2);

        $responseContent = $response->getContent();
        $responseSize = $responseContent !== false ? strlen($responseContent) : 0;

        $logData = [
            'trace_id' => $traceId,
            'duration_ms' => $duration,
            'status' => $response->getStatusCode(),
            'memory_used_kb' => $memoryUsed,
            'peak_memory_kb' => $peakMemory,
            'query_count' => $queryCount,
            'total_query_time_ms' => round($totalQueryTime, 2),
            'slowest_query' => $slowestQuery,
            'response_size_kb' => round($responseSize / 1024, 2),
            'response_content_type' => $response->headers->get('Content-Type'),
        ];

        Log::channel('performance')->info('REQUEST_END', $logData);

        $response->headers->set('X-Trace-Id', $traceId);
        $response->headers->set('X-Response-Time-ms', $duration);
        $response->headers->set('X-Memory-Used-kb', $memoryUsed);

        if (app()->environment('local') && $this->isHtmlResponse($response)) {
            $debugInfo = json_encode([
                'trace' => $traceId,
                'time' => $duration . 'ms',
                'memory' => $memoryUsed . 'KB',
                'queries' => $queryCount,
                'db_time' => round($totalQueryTime, 2) . 'ms',
            ]);

            $script = "<script>console.info('Performance: ', {$debugInfo});</script>";
            $content = str_replace('</body>', $script . '</body>', $responseContent);
            $response->setContent($content);
        }

        return $response;
    }
    private function isHtmlResponse($response): bool
    {
        $contentType = $response->headers->get('Content-Type');
        return $contentType && str_contains($contentType, 'text/html');
    }
}
