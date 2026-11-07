<?php

use App\Http\Controllers\HealthController;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Endpoint operasional di root (tanpa middleware session/web).
            Route::get('/health', HealthController::class);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Project ini tidak punya halaman web login: untuk /api/* jangan redirect (null),
        // biar langsung jadi respons 401 JSON (lihat handler di bawah).
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Semua error di /api/* selalu dijawab JSON (401, 404, 422, dst.), bukan redirect/HTML.
        $exceptions->shouldRenderJsonWhen(
            fn ($request, $e) => $request->is('api/*') || $request->expectsJson()
        );

        // Tanpa/tidak valid token -> 401 JSON (Postman default tidak mengirim "Accept: application/json",
        // tanpa ini Laravel mencoba redirect ke route "login" yang tidak ada dan berujung 500).
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        // Validasi gagal: catat siapa, endpoint apa, dan field mana yang salah.
        // Nilai input (termasuk password) tidak pernah dimasukkan ke context.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                $sensitiveFields = ['email', 'password', 'password_confirmation', 'token', 'access_token'];

                Log::warning('Request validation failed', [
                    'event' => 'request.validation_failed',
                    'actor_id' => $request->user()?->id,
                    'action' => $request->method().' '.$request->path(),
                    'reason' => 'invalid request payload',
                    'field_count' => count($e->errors()),
                    'fields' => array_values(array_diff(array_keys($e->errors()), $sensitiveFields)),
                ]);
            }
        });

        // Kegagalan query/koneksi database juga dicatat tanpa membocorkan SQL
        // atau nilai data yang mungkin sensitif.
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*')) {
                Log::error('Database operation failed', [
                    'event' => 'database.operation_failed',
                    'actor_id' => $request->user()?->id,
                    'action' => $request->method().' '.$request->path(),
                    'reason' => 'database query failed',
                    'error_class' => $e::class,
                    'sql_state' => $e->errorInfo[0] ?? null,
                ]);
            }
        });
    })->create();
