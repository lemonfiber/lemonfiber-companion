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
    ->ignoreErrorsOnPackage('nativephp/mobile', [ErrorType::UNUSED_DEPENDENCY]);
