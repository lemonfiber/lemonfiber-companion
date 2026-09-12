<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the window with the container.
 *
 * Bound rather than a singleton, for the same reason the keychain and the
 * notification centre are: this is a handle to something outside the process,
 * and the runtime here is persistent — a long-running app that held one from
 * launch would go on answering with the state it saw then.
 */
final class NativeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Screen::class, static fn(): Screen => new Screen());
    }
}
