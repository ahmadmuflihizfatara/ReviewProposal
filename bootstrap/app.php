<?php

use App\Http\Middleware\PreventRequestForgery;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as BasePreventRequestForgery;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global (bukan grup web) agar /up dan 404 non-rute juga menerima header keamanan.
        $middleware->append(SecurityHeaders::class);
        // ponytail: '*' karena IP proxy server tidak diketahui; persempit ke IP proxy bila ada.
        $middleware->trustProxies(at: '*');
        $middleware->web(
            replace: [BasePreventRequestForgery::class => PreventRequestForgery::class],
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
