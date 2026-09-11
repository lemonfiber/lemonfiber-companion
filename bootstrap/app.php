<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
 * This application has no HTTP surface.
 *
 * It renders natively (ADR-0017), and the device boots it by requiring this
 * file directly — see the package's own ios/android bootstrap, which builds the
 * console kernel and never passes through `public/index.php`. So there are no
 * web routes to register, no health endpoint to expose, and no request to
 * decide a response format for.
 *
 * Console routing stays: `artisan` is how the build and the developer tooling
 * reach the application.
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(commands: __DIR__ . '/../routes/console.php')
    ->withMiddleware(static function (Middleware $middleware): void {
        //
    })
    ->withExceptions(static function (Exceptions $exceptions): void {
        //
    })->create();
