<?php

declare(strict_types=1);

namespace Bootstrap\Composition;

use Illuminate\Support\ServiceProvider;
use Lemonfiber\Native\Screen;
use Modules\Design\Api\Theme;
use Modules\Device\Api\PlatformAuth;
use Modules\Device\Api\PlatformNotifier;
use Modules\Device\Api\PlatformScreen;
use Modules\Device\Api\SystemClock;
use Modules\Device\Api\SystemEntropy;
use Modules\Kernel\Api\Capture;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\SecureStorage;
use Modules\Vault\Api\PlatformKeychain;
use Native\Mobile\Edge\TailwindParser;
use Native\Mobile\PushNotifications;
use Native\Mobile\SecureStorage as PlatformStore;
use NativePHP\LocalNotifications\LocalNotifications;

/**
 * The composition root.
 *
 * Named for what it is rather than for the framework slot it fills, and living
 * beside `bootstrap/providers.php` — the file that names it — rather than under
 * an `app/` directory that held nothing else. Laravel does not require the `App`
 * namespace anywhere; `providers.php` returns a class list, and nothing in this
 * application has models to discover.
 *
 * This is the one place in the application permitted to name both a port and
 * the adapter that implements it. Every other class receives what it needs
 * through its constructor and never learns which implementation it got — which
 * is what makes a capability module testable without a device, a network or a
 * stack to talk to.
 */
final class CompositionRoot extends ServiceProvider
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

        // Bound, not a singleton, for the same reason the store above is not:
        // the plugin's centre is a handle to something outside this process,
        // and this runtime is persistent (I1).
        //
        // Local rather than pushed, which is the decision this line records. A
        // push payload reaches the handset through Google's or Apple's relay —
        // a third party reading what a stack said about somebody's home — and
        // NothingLeavesThisDeviceTest is the rule that refuses it. A local
        // notification is composed and shown on the device and never leaves it.
        $this->app->bind(
            Notifier::class,
            static fn(): Notifier => new PlatformNotifier(new LocalNotifications(), new PushNotifications()),
        );

        // Bound for the same reason: a window is outside this process, and an
        // app holding one from launch would go on answering with the state it
        // saw then.
        //
        // `N4-R9` is not reached through this binding at all. The native half
        // installs a lifecycle observer as the app starts and protects a
        // backgrounded app whether or not anything here is ever resolved — a
        // requirement with no exceptions should not depend on a container entry.
        $this->app->bind(
            Capture::class,
            static fn(): Capture => new PlatformScreen(new Screen()),
        );

        // The same handle again, and bound for the same reason. `N4-R7` locks
        // the app on backgrounding and `N4-R19` asks again after a period the
        // operator sets — both of which are decisions about *when*, made in
        // `LockRule` on the native side where they can be tested without a
        // handset. This is only how an answer becomes a Lock.
        $this->app->bind(
            DeviceAuth::class,
            static fn(): DeviceAuth => new PlatformAuth(new Screen()),
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
