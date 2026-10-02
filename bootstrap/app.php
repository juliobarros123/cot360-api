<?php

use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middlewares GLOBAIS
        $middleware->append([
            \Illuminate\Http\Middleware\HandleCors::class,
            ForceJsonResponse::class,
        ]);

        // Middlewares específicos para API
        $middleware->api(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->statefulApi();

        // Adicione esta linha para garantir que o Sanctum está sendo carregado
        $middleware->trustProxies(at: [
            '127.0.0.1',
            'localhost'
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'sso' => \App\Http\Middleware\ValidateSSOToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Configurações de tratamento de exceções
    })->create();