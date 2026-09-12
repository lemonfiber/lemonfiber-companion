<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

/*
 * G4 — no dev dependency is reachable from production code.
 *
 * The failure this prevents only appears on a built device: a class that works
 * in every test because the package it needs is installed, and is absent from
 * the bundle because it was required under require-dev. There is no useful
 * error message at that point, because the app is on a phone.
 *
 * It also enforces the module boundaries from the far side. Each module's own
 * manifest declares what it may use, so a surface module that types
 * Lemonfiber\Sdk is a shadow dependency here (E3, N1-R16) — failing in
 * resolution rather than in review.
 */
/**
 * Every PHP file under a directory, at any depth.
 *
 * `glob` has no globstar, so a pattern would see one level and answer for the
 * rest by saying nothing — which here would call a module unused because the
 * file naming it sat one directory deeper.
 *
 * @return list<string>
 */
function filesUnder(string $directory): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $found = [];

    $walk = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $directory,
        FilesystemIterator::SKIP_DOTS,
    ));

    foreach ($walk as $file) {
        if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
            $found[] = $file->getPathname();
        }
    }

    sort($found);

    return $found;
}

/**
 * Every module package that holds no code yet.
 *
 * A module is required by the root structurally rather than because root code
 * names it: the framework discovers each one's service provider. While its
 * `src` is empty there is nothing for the analyser to find a use of, so it
 * reports the package as unused — truthfully, and about a fact that is going
 * to change.
 *
 * Derived rather than written out, so a module drops off this list by having
 * its first class written. Which is also why the test is "holds a class"
 * rather than a scan for who names it: once a module has code, the analyser
 * finds it used, and reproducing *how* it decides that would be a second
 * implementation of somebody else's rule — one that would disagree eventually
 * and ignore an error that does fire.
 *
 * Only `UNUSED_DEPENDENCY` is ignored, and only for these. An ignore that
 * never fires is an error here, so a module reaching some other state says so
 * by name rather than being covered by a blanket.
 *
 * @return list<string>
 */
function modulesWithNoCode(): array
{
    /** @var array{require?: array<string, string>} $manifest */
    $manifest = json_decode((string) file_get_contents(__DIR__ . '/composer.json'), true);

    $empty = [];

    foreach (array_keys($manifest['require'] ?? []) as $package) {
        if (! str_starts_with($package, 'modules/')) {
            continue;
        }

        $source = sprintf('%s/app-modules/%s/src', __DIR__, substr($package, strlen('modules/')));

        if (filesUnder($source) === []) {
            $empty[] = $package;
        }
    }

    return $empty;
}

return (new Configuration())
    // A dev tool invoked from a composer script is never named in PHP, so the
    // default — reporting only unused production dependencies — cannot tell one
    // apart from a package nothing uses at all. With this on, every dev
    // dependency must be either referenced in code, included in phpstan.neon,
    // or listed below with the reason it is installed.
    ->enableAnalysisOfUnusedDevDependencies()
    ->addPathToScan(__DIR__ . '/app', isDev: false)
    // Split rather than named as the parent, because a module's `tests/` sits
    // inside it and is a dev path exactly like the root suite. Scanned
    // wholesale, the first module test makes Pest a production dependency —
    // reported as G4's own failure, on a package that is only ever a dev one.
    // Globbed so a module added tomorrow is scanned without anyone editing
    // this file.
    ->addPathsToScan((array) glob(__DIR__ . '/app-modules/*/src'), isDev: false)
    ->addPathsToScan((array) glob(__DIR__ . '/app-modules/*/tests'), isDev: true)
    ->addPathToScan(__DIR__ . '/config', isDev: false)
    ->addPathToScan(__DIR__ . '/routes', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // Build tooling, so that a script reaching for a package nobody required is
    // caught here rather than on the machine that does not have it.
    ->addPathToScan(__DIR__ . '/scripts', isDev: true)
    // A module is required structurally, not because root code names it: the
    // framework discovers each one's service provider. That is true for every
    // module permanently, so it is stated once by prefix rather than as twelve
    // exemptions that would need a thirteenth line per module added.
    //
    // Narrowed to the modules the composition root does not yet name. A module
    // it does name is genuinely used, and an ignore that never fires is itself
    // an error here — the property the nativephp/mobile line below is built on.
    // So each module drops off this list by being wired up, which is the same
    // self-removal by a different route, and neither half is a list anybody
    // maintains by hand.
    ->ignoreErrorsOnPackages(modulesWithNoCode(), [ErrorType::UNUSED_DEPENDENCY])
    // The analyser reads `use` statements, so a module whose only classes name
    // each other from the same namespace is invisible to it: nothing imports
    // anything, and the only files that mention the package are its own tests,
    // which are a dev path. It reports the module as one to move to
    // require-dev — which cannot be done, because a module ships and its
    // service provider is discovered at boot.
    //
    // Written out rather than derived, and that is the decision rather than an
    // omission. The condition that actually produces this is "no prod-path file
    // imports one of the module's classes", which is the analyser's own rule —
    // health escapes it only because `Api\Queries\WorstFirst` happens to sit
    // one namespace down and therefore imports `Api\Finding`. A list computed
    // from a re-statement of somebody else's rule disagrees with it eventually
    // and ignores an error that does fire.
    //
    // It cannot go stale: this analyser reports an ignore that never applied,
    // so the day something outside connection names it, the gate fails and
    // names this line as the thing to delete.
    ->ignoreErrorsOnPackage('modules/connection', [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV])
    // Resolved through the container rather than named, so the analyser cannot
    // see the use. Narrower than disabling the check.
    ->ignoreErrorsOnPackage('internachi/modular', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('laravel/tinker', [ErrorType::UNUSED_DEPENDENCY])
    // Every dev tool that is real but never named in PHP, with what invokes it.
    // A package absent from both this list and the codebase is dead weight, and
    // that is the whole point of the switch above: captainhook sat in
    // require-dev shipping nothing, and nothing could see it.
    ->ignoreErrorsOnPackages(
        [
            // Included by phpstan.neon, which is configuration rather than code.
            'larastan/larastan',
            'phpstan/phpstan-strict-rules',
            'phpstan/phpstan-deprecation-rules',
            'ergebnis/phpstan-rules',
            'shipmonk/phpstan-rules',
            'spaze/phpstan-disallowed-calls',
            'tomasvotruba/type-coverage',
            'tomasvotruba/cognitive-complexity',
            // Binaries a composer script runs.
            'laravel/pint',
            'rector/rector',
            'ergebnis/composer-normalize',
            'shipmonk/composer-dependency-analyser',
            // S2 — roave/security-advisories is conflict-only. It ships no code
            // to name: what it does is fail resolution when a dependency
            // matches a published advisory, so the refusal arrives at
            // `composer update` rather than at `composer audit` in CI a week
            // later. There is nothing for a scanner to find, by design.
            'roave/security-advisories',
            // Discovered at runtime: Collision renders a failure readably and
            // the Pest plugin is what boots the application for a test.
            'nunomaduro/collision',
            'pestphp/pest-plugin-laravel',
        ],
        [ErrorType::UNUSED_DEPENDENCY],
    );
