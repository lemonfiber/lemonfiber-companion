<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Lemonfiber\Native\NativeServiceProvider as OurOwnExpansion;
use Native\Mobile\UI\NativeUIServiceProvider as Controls;

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
 * - **`lemonfiber/bridge`** carries the notification capability — nine
 *   functions under `Lemonfiber.Telling.*`, which replaced
 *   `nativephp/mobile-local-notifications` once they were watched working on a
 *   handset — `Lemonfiber.Scanning.Read`, which replaced
 *   `nativephp/mobile-scanner` the same way — `Lemonfiber.Storage.*`, which
 *   replaced `nativephp/mobile-secure-storage` and carries every value this
 *   application retains: the session for each stack, the stacks themselves and
 *   what each one last came to — `Lemonfiber.Link.Status`, which replaced
 *   `nativephp/mobile-network` and is the one question a launch asks before it
 *   asks anything of a stack — `Lemonfiber.Handover.Offer`, which replaced
 *   `nativephp/mobile-share` and puts a diagnostic report in front of somebody
 *   who can help without this application learning where it went — and `Lemonfiber.Conceal`,
 *   `Lemonfiber.Reveal` and `Lemonfiber.IsProtected`, the protection for a
 *   screen showing a session token or pairing material — and
 *   `Lemonfiber.Authenticate` / `Lemonfiber.CanAuthenticate`, the device's own
 *   authentication and the app lock. The PHP half is discovered by Laravel and
 *   binds normally, so every test passes; the Kotlin and the Swift are
 *   collected by `AndroidPluginCompiler` and `IOSPluginCompiler`, which read
 *   this list. A build made without it has the adapters and not the functions
 *   they call.
 * - **`nativephp/mobile-ui`**, which is where a text input comes from.
 *   `nativephp/mobile` registers `pressable` and no field: `text_input`,
 *   `toggle` and the rest are left to this plugin by name, in a comment in
 *   `registerCoreElements()`. Without it there is no way to type anything into
 *   this application at all — which means no `StackName`, so a stack cannot be
 *   given the name it is listed under and pairing cannot complete by either
 *   road.
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
            Controls::class,
        ];
    }
}
