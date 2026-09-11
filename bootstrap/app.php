<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
 * This application answers nothing from the network, and still needs routing.
 *
 * The distinction cost me an hour, so it is written down. The device does not
 * run a render loop — it drives the application through the HTTP kernel, and
 * `Route::native()` registers each screen as an ordinary GET route so the
 * runtime can ask for one by URI. There is no web server and no port open;
 * `public/index.php` is gone because nothing outside the device ever connects.
 *
 * So `routes/native.php` is loaded in the position Laravel calls `web`. It is
 * named for what it holds rather than for the slot it occupies.
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/native.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(static function (Middleware $middleware): void {
        //
    })
    ->withExceptions(static function (Exceptions $exceptions): void {
        //
    })->create();
