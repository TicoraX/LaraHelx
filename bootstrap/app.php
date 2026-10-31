<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Seguro solo porque el puerto se publica en 127.0.0.1 y solo Caddy le habla (02 §3).
        $middleware->trustProxies(at: '*');
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Postman no manda Accept: sin esto, un 401 de la API sería un 302 al login (02 §6).
        $exceptions->shouldRenderJsonWhen(fn (Request $r, Throwable $e) => $r->is('api/*') || $r->expectsJson());
        // Laravel devuelve el mensaje de la excepción sin traducir; "Unauthenticated." está en lang/es.json (05 §4).
        $exceptions->render(function (AuthenticationException $e, Request $r) {
            if ($r->is('api/*')) {
                return response()->json(['message' => __($e->getMessage())], 401);
            }
        });
        $exceptions->render(function (NotFoundHttpException $e, Request $r) {
            if ($r->is('api/*')) {
                return response()->json(['message' => 'No encontrado'], 404);
            }
        });
        // "Too Many Attempts." viene fijo del middleware de throttle (05 §4: mensajes traducidos).
        $exceptions->render(function (ThrottleRequestsException $e, Request $r) {
            if ($r->is('api/*')) {
                return response()->json(['message' => 'Demasiados intentos.'], 429, $e->getHeaders());
            }
        });
    })->create();
