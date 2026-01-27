<?php

use App\Exceptions\ClientException;
// use App\Http\Middleware\EnsureRoleMiddleware;
// use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // $middleware->alias([
        //     "profile.type" => EnsureRoleMiddleware::class
        // ]);
        // $middleware->append(SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ClientException $e, $request) {
            if ($e instanceof ClientException) {
                return response()->json([
                    "status" => false,
                    "error" => [
                        "code" => $e->keyCode,
                        "message" => $e->getMessage(),
                    ]
                ], $e->status);
            }
            report($e);
            return response()->json([
                "status" => false,
                "error" => [
                    "code" => "INTERNAL_SERVER_ERROR",
                    "message" => "An internal server error occurred.",
                ]
            ], 500);
        });
    })->create();
