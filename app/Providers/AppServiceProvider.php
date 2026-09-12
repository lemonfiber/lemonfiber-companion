<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Design\Api\Theme;
use Modules\Device\Api\SystemClock;
use Modules\Device\Api\SystemEntropy;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\SecureStorage;
use Modules\Vault\Api\PlatformKeychain;
use Native\Mobile\Edge\TailwindParser;
use Native\Mobile\SecureStorage as PlatformStore;

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
        // The one line that says which clock the application runs on, and the
        // only place in the codebase allowed to say it. Everything else takes a
        // `Clock` and never learns it got the platform's rather than a frozen
        // one — which is what makes a test about an expiring session a
        // statement rather than a wait (A3, B1, G8).
        //
        // Bound as a singleton because reading the time is stateless and a
        // second instance would answer identically; one object is the honest
        // description of that.
        $this->app->singleton(Clock::class, static fn(): Clock => new SystemClock());

        // The other hidden input, bound the same way and with the same
        // consequence: nothing that needs a value nobody can guess learns
        // whether it got the platform's randomness or a counter, which is what
        // makes a test about a retry a statement rather than a guess
        // (A3, B2, G8).
        $this->app->singleton(Entropy::class, static fn(): Entropy => new SystemEntropy());

        // Not a singleton. The platform's store is a handle to something
        // outside this process, and holding one for the life of a long-running
        // app (I1) is how a keychain that was unlocked at launch goes on
        // reading as unlocked after the device has locked.
        $this->app->bind(
            SecureStorage::class,
            static fn(): SecureStorage => new PlatformKeychain(new PlatformStore()),
        );
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
