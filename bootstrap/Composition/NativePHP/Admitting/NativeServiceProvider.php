<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Lemonfiber\Native\NativeServiceProvider as OurOwnExpansion;
use Native\Mobile\Providers\NetworkServiceProvider as WhetherThereIsANetwork;
use Native\Mobile\Providers\ScannerServiceProvider as ReadingACode;
use Native\Mobile\Providers\SecureStorageServiceProvider as SecureStore;
use Native\Mobile\Providers\ShareServiceProvider as HandingOver;
use Native\Mobile\UI\NativeUIServiceProvider as Controls;
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
 * - **`lemonfiber/bridge`** carries `Lemonfiber.Conceal`, `Lemonfiber.Reveal`
 *   and `Lemonfiber.IsProtected` — the protection for a screen showing a
 *   session token or pairing material — and
 *   `Lemonfiber.Authenticate` / `Lemonfiber.CanAuthenticate`, which is the app
 *   lock. The PHP half is discovered by Laravel and binds normally,
 *   so every test passes; the Kotlin and the Swift are collected by
 *   `AndroidPluginCompiler` and `IOSPluginCompiler`, which read this list.
 *   A build made without it has the adapters and not the functions they call.
 * - **`nativephp/mobile-local-notifications`**, which `Modules\Device\Api\
 *   PlatformNotifier` is written against.
 * - **`nativephp/mobile-secure-storage`**, which is where the keychain comes
 *   from. It was core until NativePHP 4 and is a plugin now, and the facade
 *   `Native\Mobile\SecureStorage` stayed behind in `nativephp/mobile` — so the
 *   PHP half resolves, the adapters bind, every test passes against a fake, and
 *   the device answers *function not found* to every read. Nothing this app
 *   retains works without it: no session kept, no stack remembered, no verdict
 *   shown.
 * - **`nativephp/mobile-scanner`**, which is the camera road into pairing. The
 *   typed road is the other one and works without it, but a device offering a
 *   control that does nothing at all is worse than a device offering one road.
 * - **`nativephp/mobile-network`**, which answers whether this device has one.
 *   The launch asks before it asks anything of a stack, so without it the app
 *   cannot tell *no network here* from *that machine is not answering* — the
 *   two sentences with the two different remedies.
 * - **`nativephp/mobile-share`**, which is the sheet a diagnostic report is
 *   handed to. Both were core until NativePHP 4 and are plugins now.
 * - **`nativephp/mobile-ui`**, which is where a text input comes from.
 *   `nativephp/mobile` registers `pressable` and no field: `text_input`,
 *   `toggle` and the rest are left to this plugin by name, in a comment in
 *   `registerCoreElements()`. Without it there is no way to type anything into
 *   this application at all — which means no `StackName`, which means two
 *   machines cannot be told apart and pairing cannot complete by either road.
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
            ReadingACode::class,
            SecureStore::class,
            WhetherThereIsANetwork::class,
            HandingOver::class,
            Controls::class,
        ];
    }
}
