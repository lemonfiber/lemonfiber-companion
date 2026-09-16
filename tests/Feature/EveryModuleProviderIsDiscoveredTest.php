<?php

declare(strict_types=1);

use Tests\Support\Module;

// Every provider a module's manifest declares is one the application loaded.
//
// A module does not register itself. It declares a provider under
// `extra.laravel.providers`, composer copies that declaration into
// `vendor/composer/installed.json` **at install time**, `package:discover`
// writes `bootstrap/cache/packages.php` from that copy, and Laravel reads the
// cache at boot. Four steps, three of them cached, and nothing between them
// says a word when one goes stale.
//
// What that looks like when it breaks: the manifest is right, the provider
// exists, the class is autoloadable, every test that constructs it directly
// passes — and the application never registers it. A module simply does
// nothing. There is no error, because nothing tried and failed; the wiring was
// never asked for.
//
// It has broken twice here in opposite directions. Once a manifest named a
// provider that did not exist, which took CI down in three hundred places at
// once. Once a manifest gained a provider and `installed.json` kept the copy it
// had, so the module was inert while its own tests were green — found by
// booting the real application and watching a port resolve to the wrong
// adapter, which is not a thing a test that registers the provider by hand can
// ever see.
//
// So this asks the container, after boot, what it actually loaded. It is the
// one question that cannot be answered by anything a test sets up itself.

/**
 * Every provider a module's manifest declares, and which module declares it.
 *
 * Read off the manifests rather than off `packages.php`, on purpose: the
 * manifest is what somebody wrote and the cache is what the toolchain
 * remembered, and the whole failure is the two disagreeing. Comparing the cache
 * against itself would pass in exactly the state this rule exists to catch.
 *
 * @return array<string, string> provider class => the module that declares it
 */
function theProvidersTheManifestsDeclare(): array
{
    $declared = [];

    foreach (Module::all() as $module) {
        $manifest = json_decode(
            (string) file_get_contents(sprintf('%s/composer.json', $module->path)),
            associative: true,
        );

        if (! is_array($manifest)) {
            continue;
        }

        foreach (providersNamedIn($manifest) as $provider) {
            $declared[$provider] = $module->name;
        }
    }

    return $declared;
}

/**
 * The providers one decoded manifest names, and nothing for one that names none.
 *
 * Its own function so the walk above reads as a walk. Written without `??` on a
 * subscript, which `C9` refuses and is right to: three of these keys are
 * genuinely optional and stepping through them says so.
 *
 * @param  array<mixed, mixed> $manifest
 * @return list<string>
 */
function providersNamedIn(array $manifest): array
{
    $extra = array_key_exists('extra', $manifest) ? $manifest['extra'] : [];

    if (! is_array($extra) || ! array_key_exists('laravel', $extra) || ! is_array($extra['laravel'])) {
        return [];
    }

    $laravel = $extra['laravel'];

    if (! array_key_exists('providers', $laravel) || ! is_array($laravel['providers'])) {
        return [];
    }

    return array_values(array_filter($laravel['providers'], is_string(...)));
}

it('finds manifests that declare a provider', function (): void {
    // The floor. A reader that found none would make the rule below pass with
    // no iterations, which is the shape of silence every rule in this
    // repository is written against — and would do it in the exact state the
    // rule is for, because a manifest whose declaration went missing is what it
    // is looking for.
    expect(theProvidersTheManifestsDeclare())->not->toBeEmpty();
});

it('loads every provider a module declares', function (): void {
    $loaded = app()->getLoadedProviders();
    $missing = [];

    foreach (theProvidersTheManifestsDeclare() as $provider => $module) {
        if (! array_key_exists($provider, $loaded)) {
            $missing[] = sprintf('%s declares %s and the application did not load it', $module, $provider);
        }
    }

    sort($missing);

    expect($missing)->toBe([], sprintf(
        "These providers are declared and were never registered:\n  %s\n\n"
        . 'The module is inert: nothing it binds is bound, nothing it listens for is heard, '
        . "and no error is raised, because nothing tried.\n"
        . 'The usual cause is a stale copy rather than a wrong manifest — composer snapshots '
        . '`extra` into vendor/composer/installed.json when the package is installed, so a '
        . 'declaration added afterwards is invisible until the package is installed again. '
        . "Run `composer update <module>` and then `php artisan package:discover`.\n"
        . 'If the provider is genuinely new, check the class exists at the name written in '
        . 'the manifest: a name with no class takes the whole application down at boot '
        . 'rather than leaving it inert.',
        implode("\n  ", $missing),
    ));
});
