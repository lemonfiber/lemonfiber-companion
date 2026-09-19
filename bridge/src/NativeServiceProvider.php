<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use Illuminate\Support\ServiceProvider;

/**
 * Registers this plugin's faces with the container.
 *
 * Bound rather than singletons, and for the same reason in each case: every one
 * of these is a handle to something outside the process, and the runtime here
 * is persistent — a long-running application that held one from launch would go
 * on answering with the state it saw then.
 */
final class NativeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Screen::class, static fn(): Screen => new Screen());
        $this->app->bind(Telling::class, static fn(): Telling => new Telling());
        $this->app->bind(Scanning::class, static fn(): Scanning => new Scanning());
    }
}
