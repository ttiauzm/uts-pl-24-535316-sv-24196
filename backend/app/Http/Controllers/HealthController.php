<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class HealthController extends Controller
{
    /**
     * GET /health
     *
     * Endpoint ini tidak hanya membalas teks: ia benar-benar mencoba
     * koneksi ke database dan melakukan write/read/delete pada cache.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => 'ok',
            'cache' => 'ok',
        ];

        try {
            DB::select('SELECT 1');
        } catch (Throwable $e) {
            $checks['database'] = 'down';
            $this->logFailure('database', $e);
        }

        try {
            $key = 'health:probe:'.Str::uuid();
            $value = 'ok';

            if (! Cache::put($key, $value, 10) || Cache::get($key) !== $value) {
                throw new RuntimeException('cache write/read mismatch');
            }

            Cache::forget($key);
        } catch (Throwable $e) {
            $checks['cache'] = 'down';
            $this->logFailure('cache', $e);
        }

        $failed = array_keys(array_filter(
            $checks,
            fn (string $status): bool => $status !== 'ok'
        ));
        $healthy = $failed === [];

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'failed_components' => $failed,
            'message' => $healthy
                ? 'Database and cache are healthy.'
                : 'One or more dependencies are unhealthy.',
            'checked_at' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function logFailure(string $component, Throwable $e): void
    {
        Log::error('Health check failed', [
            'event' => 'health.check_failed',
            'actor' => 'system/healthcheck',
            'action' => 'check '.$component,
            'component' => $component,
            'reason' => $this->safeReason($e),
            'error_class' => $e::class,
        ]);
    }

    /**
     * Jangan pernah menulis password/token/email mentah ke log.
     * Pesan exception tetap diambil agar penyebabnya terlihat, tetapi
     * bagian sensitif disamarkan terlebih dahulu.
     */
    private function safeReason(Throwable $e): string
    {
        $message = $e->getMessage();

        $message = preg_replace(
            '/(password|passwd|pwd)\s*[=:]\s*[^;\s]+/i',
            '$1=[REDACTED]',
            $message
        ) ?? $message;

        $message = preg_replace(
            '/(bearer\s+)[A-Za-z0-9\-._~+\/]+=*/i',
            '$1[REDACTED]',
            $message
        ) ?? $message;

        $message = preg_replace(
            '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
            '[EMAIL_REDACTED]',
            $message
        ) ?? $message;

        return Str::limit($message, 300);
    }
}
