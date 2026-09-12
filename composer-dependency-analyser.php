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
 * The names a file actually references, with comments and strings left out.
 *
 * Matching the raw text would count a namespace written in a comment, and this
 * repository's comments name module namespaces constantly — the architecture
 * rules explain themselves in terms of `Modules\Health\Internal` and the like.
 * A comment is not a use, and treating it as one puts a module back on the
 * exemption list for a sentence about it.
 *
 * @return list<string>
 */
function namesIn(string $file): array
{
    $found = [];

    foreach (token_get_all((string) file_get_contents($file)) as $token) {
        if (! is_array($token)) {
            continue;
        }

        if (in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_STRING], true)) {
            $found[] = $token[1];
        }
    }

    return $found;
}

/**
 * The module packages nothing outside the module uses at all.
 *
 * A module is required by the root structurally rather than because root code
 * names it: the framework discovers each one's service provider. That holds
 * for as long as nothing has been wired to it, and stops holding the moment
 * something is — so the list is derived rather than written out, and a module
 * drops off it by being used.
 *
 * @return list<string>
 */
function unusedModules(): array
{
    return modulesWhere(static fn(string $name, string $own): bool
        => ! namedIn($name, $own, productionTrees()) && ! namedIn($name, $own, devTrees()));
}

/**
 * The module packages only a test names.
 *
 * The state every module passes through on its way to being wired up: it has
 * classes and a suite of its own, and no production code has reached for it
 * yet. The analyser reports that as a production dependency used only in dev,
 * which is true and is not a fault — the manifest requires it because the
 * framework loads it, not because `app/` types it.
 *
 * @return list<string>
 */
function modulesOnlyTestsUse(): array
{
    return modulesWhere(static fn(string $name, string $own): bool
        => ! namedIn($name, $own, productionTrees()) && namedIn($name, $own, devTrees()));
}

/**
 * Module packages matching a predicate over their namespace and own directory.
 *
 * Two lists rather than one because the analyser reports an ignore that never
 * fires as an error, which is the property that makes an exemption remove
 * itself. Ignoring both error types for every unwired module would mean one of
 * the two never fires for each of them, and the file would fail on its own
 * exemptions.
 *
 * @param callable(string, string): bool $matches
 *
 * @return list<string>
 */
function modulesWhere(callable $matches): array
{
    /** @var array{require?: array<string, string>} $manifest */
    $manifest = json_decode((string) file_get_contents(__DIR__ . '/composer.json'), true);

    $found = [];

    foreach (array_keys($manifest['require'] ?? []) as $package) {
        if (! str_starts_with($package, 'modules/')) {
            continue;
        }

        $short = substr($package, strlen('modules/'));
        $name = str_replace(' ', '', ucwords(str_replace('-', ' ', $short)));

        // A module's own files are excluded wherever this looks: declaring a
        // namespace is not using it. `app-modules/kernel/src` is what
        // `modules/kernel` provides, not a consumer of it.
        if ($matches($name, sprintf('%s/app-modules/%s/', __DIR__, $short))) {
            $found[] = $package;
        }
    }

    return $found;
}

/** @return list<string> */
function productionTrees(): array
{
    return [
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/routes',
        ...(array) glob(__DIR__ . '/app-modules/*/src'),
    ];
}

/** @return list<string> */
function devTrees(): array
{
    return [
        __DIR__ . '/tests',
        __DIR__ . '/scripts',
        ...(array) glob(__DIR__ . '/app-modules/*/tests'),
    ];
}

/**
 * Whether any file in these trees, outside `$own`, references `Modules\<name>`.
 *
 * @param list<string> $trees
 */
function namedIn(string $name, string $own, array $trees): bool
{
    $prefix = sprintf('Modules\\%s\\', $name);

    foreach ($trees as $tree) {
        foreach (filesUnder($tree) as $file) {
            if (str_starts_with($file, $own)) {
                continue;
            }

            foreach (namesIn($file) as $used) {
                if (str_starts_with($used, $prefix)) {
                    return true;
                }
            }
        }
    }

    return false;
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
    ->ignoreErrorsOnPackages(unusedModules(), [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackages(modulesOnlyTestsUse(), [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV])
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
