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
return (new Configuration())
    // A dev tool invoked from a composer script is never named in PHP, so the
    // default — reporting only unused production dependencies — cannot tell one
    // apart from a package nothing uses at all. With this on, every dev
    // dependency must be either referenced in code, included in phpstan.neon,
    // or listed below with the reason it is installed.
    ->enableAnalysisOfUnusedDevDependencies()
    ->addPathToScan(__DIR__ . '/app', isDev: false)
    ->addPathToScan(__DIR__ . '/app-modules', isDev: false)
    ->addPathToScan(__DIR__ . '/config', isDev: false)
    ->addPathToScan(__DIR__ . '/routes', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // A module is required structurally, not because root code names it: the
    // framework discovers each one's service provider. That is true for every
    // module permanently, so it is stated once by prefix rather than as twelve
    // exemptions that would need a thirteenth line per module added.
    ->ignoreErrorsOnPackages(
        array_values(array_filter(
            array_keys(
                json_decode((string) file_get_contents(__DIR__ . '/composer.json'), true)['require'] ?? [],
            ),
            static fn(string $package): bool => str_starts_with($package, 'modules/'),
        )),
        [ErrorType::UNUSED_DEPENDENCY],
    )
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
            // Discovered at runtime: Collision renders a failure readably and
            // the Pest plugin is what boots the application for a test.
            'nunomaduro/collision',
            'pestphp/pest-plugin-laravel',
        ],
        [ErrorType::UNUSED_DEPENDENCY],
    )
    // The only file naming nativephp/mobile is the arch rule that reflects over
    // NativeComponent to ask whether a screen declares how it paints, and a test
    // is a dev path — so the runtime the application is built on reads as a dev
    // dependency. It is not: the first screen makes it a production use and this
    // line stops applying. The analyser fails on an ignore that never fires,
    // which is what makes the line remove itself rather than linger.
    ->ignoreErrorsOnPackage('nativephp/mobile', [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV]);
