<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * The composition root.
 *
 * This is the one place in the application permitted to name both a port and
 * the adapter that implements it. Every other class receives what it needs
 * through its constructor and never learns which implementation it got — which
 * is what makes a capability module testable without a device, a network or a
 * stack to talk to.
 */
final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
