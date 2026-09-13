<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Lemonfiber\Native\NativeServiceProvider as OurOwnExpansion;
use NativePHP\LocalNotifications\LocalNotificationsServiceProvider as Notifications;

/**
 * Which NativePHP plugins this application admits.
 *
 * **Without this file, none of them.** `PluginDiscovery::getAllowedPlugins()`
 * looks for exactly this class, and where it does not exist it returns an empty
 * allow-list and `isPluginAllowed()` refuses everything — deliberately, so that
 * a package pulled in three levels down cannot register native code in somebody
 * else's app by being installed. That is a good default and it is silent: a
 * plugin that is required, installed, autoloaded and never discovered looks
 * exactly like a plugin that works, right up until the build.
 *
 * This application was in that state. Both plugins it ships are
 * `type: nativephp-plugin`, both declare native halves, and neither was reaching
 * a build:
 *
 * - **`lemonfiber/native`** carries `Lemonfiber.Conceal`, `Lemonfiber.Reveal`
 *   and `Lemonfiber.IsProtected` — which is `N4-R18`, the protection for a
 *   screen showing a session token or pairing material — and
 *   `Lemonfiber.Authenticate` / `Lemonfiber.CanAuthenticate`, which is `N4-R8`
 *   and the app lock. The PHP half is discovered by Laravel and binds normally,
 *   so every test passes; the Kotlin and the Swift are collected by
 *   `AndroidPluginCompiler` and `IOSPluginCompiler`, which read this list.
 *   A build made without it has the adapters and not the functions they call.
 * - **`nativephp/mobile-local-notifications`**, which `Modules\Device\Api\
 *   PlatformNotifier` is written against.
 *
 * **Named by class rather than by string.** The vendor compares against the
 * first entry of a package's `extra.laravel.providers`, which is a string — so
 * `::class` is converted at the boundary and nothing else. What it buys is that
 * a provider renamed upstream stops the build here, rather than dropping out of
 * this list and taking a plugin with it.
 *
 * **`App\Providers\` because the vendor hard-codes the name, and this
 * directory because the name is the only part it fixes.**
 * `PluginDiscovery` asks `class_exists()` and calls `plugins()`; where the file
 * sits is decided by PSR-4, and one line of `autoload` maps the namespace here.
 * NativePHP's own installer publishes it to `app/`, which this application
 * deliberately does not have — the composition root is `bootstrap/Composition`,
 * and a second top-level directory holding one class would be a place for the
 * next stray to land. It belongs under `NativePHP/` for the reason everything
 * else in that directory does: it exists because of one package, and the day
 * that package stops asking for it, the fix is to delete a directory.
 *
 * `EveryPluginThisAppShipsIsAdmittedTest` is what keeps the list current: a
 * plugin added to `composer.json` and not to this file fails there, by name,
 * rather than being installed and never discovered.
 */
final class NativeServiceProvider extends ServiceProvider
{
    /**
     * The plugins this application admits, by their service provider.
     *
     * Written out rather than derived from what is installed. Deriving it would
     * answer "everything present", which is the question the allow-list exists
     * not to ask — a transitive dependency would admit itself. Admission is a
     * decision, and this is where it is recorded.
     *
     * @return list<string>
     */
    public function plugins(): array
    {
        return [
            OurOwnExpansion::class,
            Notifications::class,
        ];
    }
}
