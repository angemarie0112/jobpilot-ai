<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        /*
        |--------------------------------------------------------------------------
        | Laravel Sanctum SPA Authentication
        |--------------------------------------------------------------------------
        |
        | Enable cookie-based authentication for our Vue 3 frontend.
        |
        | This allows Laravel to authenticate requests using sessions
        | and cookies instead of requiring API tokens.
        |
        */

        $middleware->statefulApi();

    })
    ->withExceptions(function (Exceptions $exceptions): void {

        /*
        |--------------------------------------------------------------------------
        | API Exception Handling
        |--------------------------------------------------------------------------
        |
        | Ensure API requests return JSON responses instead of HTML.
        |
        | This is important for our Vue frontend because it needs
        | structured error messages when requests fail.
        |
        */

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

    })->create();