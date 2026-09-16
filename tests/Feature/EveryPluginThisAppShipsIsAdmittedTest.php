<?php

declare(strict_types=1);

use App\Providers\NativeServiceProvider;
use Illuminate\Support\Collection;
use Native\Mobile\Plugins\Plugin;
use Native\Mobile\Plugins\PluginRegistry;

// N4-R8 and N4-R18 — the native halves that implement them reach a build.
//
// NativePHP refuses every plugin unless `App\Providers\NativeServiceProvider`
// names it, which is a good default and a silent one: a plugin that is
// required, installed, autoloaded and never discovered is indistinguishable
// from one that works. Its PHP half is found by Laravel's own package discovery
// and binds; its Kotlin and Swift are collected by the plugin compilers, which
// read the allow-list. Every test passes either way.
//
// This application shipped in that state. `lemonfiber/bridge` carries the window
// protection N4-R18 needs and the device authentication N4-R8 needs, and
// `nativephp/mobile-local-notifications` is what `Modules\Device\Api\
// PlatformNotifier` is written against. Neither was being discovered.
//
// Two halves, and the second is what keeps this from rotting. The first asserts
// the plugins are admitted; the second asserts that nothing *installed* is
// missing from the list, so a plugin added to composer.json and forgotten here
// fails by name rather than by being absent from a device.

/**
 * The names in a collection of plugins, sorted.
 *
 * Written out rather than `->pluck()`, because the registry's collections carry
 * no generic type: every element arrives as `mixed`, and a closure that
 * declared `Plugin` would be the analyser's problem rather than a narrowing
 * anybody checked. The `instanceof` is the narrowing, and it is real.
 *
 * @param Collection<array-key, mixed> $plugins
 *
 * @return list<string>
 */
function pluginNames(Collection $plugins): array
{
    $found = [];

    foreach ($plugins as $plugin) {
        if ($plugin instanceof Plugin) {
            $found[] = $plugin->name;
        }
    }

    sort($found);

    return $found;
}

it('N4-R18 — every plugin this application installs is admitted', function (): void {
    $registry = app(PluginRegistry::class);

    $admitted = pluginNames($registry->all());
    $installed = pluginNames($registry->allInstalled());

    expect($admitted)->toBe($installed, sprintf(
        "These are installed and not admitted:\n  %s\n\n"
        . 'NativePHP blocks a plugin that App\Providers\NativeServiceProvider::plugins() '
        . 'does not name, so its native half is never collected into a build. The PHP '
        . 'side still binds and every test still passes, which is why this is checked '
        . 'here rather than met on a handset. Add the plugin\'s service provider to that '
        . 'list, or stop requiring the package (N4-R8, N4-R18).',
        implode("\n  ", array_values(array_diff($installed, $admitted))),
    ));
});

it('admits our own expansion, whose bridge functions are the two requirements', function (): void {
    // Named rather than counted. A list that happens to be the right length is
    // a list that stays green after somebody swaps an entry, and the entry that
    // matters here is the one carrying Lemonfiber.Conceal and
    // Lemonfiber.Authenticate.
    $found = app(PluginRegistry::class)->find('lemonfiber/bridge');

    expect($found)->toBeInstanceOf(Plugin::class)
        ->and($found instanceof Plugin && $found->hasAndroidCode())->toBeTrue()
        ->and($found instanceof Plugin && $found->hasIosCode())->toBeTrue();
});

it('records admission as a decision rather than reading it off what is installed', function (): void {
    // The allow-list exists so that a package pulled in three levels down
    // cannot register native code by being present. A list derived from what is
    // installed would answer "everything", which is the question it exists not
    // to ask — so the list is written out, and the first test above is what
    // keeps the written one honest.
    $named = new NativeServiceProvider(app())->plugins();

    expect($named)->not->toBeEmpty();

    foreach ($named as $provider) {
        expect(class_exists($provider))->toBeTrue(sprintf('%s is named and is not there', $provider));
    }
});
