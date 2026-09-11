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
 * Every module package the composition root does not name.
 *
 * The namespace is derived the same way the module manifests derive it —
 * `modules/design` is `Modules\Design` — so a module added tomorrow is covered
 * without anyone editing this file.
 *
 * @return list<string>
 */
function unwiredModules(): array
{
    /** @var array{require?: array<string, string>} $manifest */
    $manifest = json_decode((string) file_get_contents(__DIR__ . '/composer.json'), true);

    $root = '';

    foreach ((array) glob(__DIR__ . '/app/*/*.php') as $file) {
        $root .= (string) file_get_contents((string) $file);
    }

    $unwired = [];

    foreach (array_keys($manifest['require'] ?? []) as $package) {
        if (! str_starts_with($package, 'modules/')) {
            continue;
        }

        $name = str_replace(' ', '', ucwords(str_replace('-', ' ', substr($package, strlen('modules/')))));

        if (! str_contains($root, sprintf('Modules\\%s\\', $name))) {
            $unwired[] = $package;
        }
    }

    return $unwired;
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
    ->ignoreErrorsOnPackages(unwiredModules(), [ErrorType::UNUSED_DEPENDENCY])
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
