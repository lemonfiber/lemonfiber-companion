<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Design\Api\Theme;
use Native\Mobile\Edge\TailwindParser;

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
        // EDGE ships no theme resolver, and without one every `bg-theme-*` and
        // `text-theme-*` class is parsed, found to mean nothing, and dropped —
        // silently, at render, on somebody's phone. The design module owns what
        // those tokens mean; this is the line that connects the two.
        //
        // No dark resolver is registered, which is the decision rather than the
        // gap: the parser emits a dark companion only when one exists, and the
        // accent pair is legible either way round without one. ThemeToken::hex()
        // carries the reasoning and the measurement.
        TailwindParser::setThemeResolver(Theme::resolver());
    }
}
