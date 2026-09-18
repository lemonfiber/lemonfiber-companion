<?php

declare(strict_types=1);

use Tests\Support\Kind;
use Tests\Support\Module;
use Tests\Support\Tree;

// A stand-in cannot be in a release, and a gate says so.
//
// Both requirements ask for the same guarantee and both say explicitly how it
// must be kept. `N1-R61` wants it *structural rather than a setting the app
// reads*; `Q-R72` wants the absence *enforced by a gate rather than by
// convention*. A comment saying the module is a development dependency is the
// convention both refuse.
//
// The structure is a single fact: `modules/dx` sits under `require-dev` in the
// root manifest. A release installs with `--no-dev`, so the package is not
// installed, its provider is not discovered, and its classes are not in the
// bundle. There is nothing to switch on because there is nothing there — which
// is why `config('dx.stands_in')` is not the thing being relied on. That
// setting chooses between implementations that are all present; it cannot
// conjure one that is absent.
//
// So this asserts the fact the structure rests on, and it is one line of a
// manifest away from being false at any time: moving a module from
// `require-dev` to `require` is a small, plausible edit — it is exactly what
// somebody does to make an autoloader complaint go away — and nothing else in
// this repository would notice.

/**
 * What the root manifest requires, by section.
 *
 * @return array{require: list<string>, require-dev: list<string>}
 */
function whatTheRootRequires(): array
{
    $manifest = json_decode((string) file_get_contents(Tree::at('composer.json')), associative: true);

    if (! is_array($manifest)) {
        return ['require' => [], 'require-dev' => []];
    }

    return [
        'require' => namedUnder($manifest, 'require'),
        'require-dev' => namedUnder($manifest, 'require-dev'),
    ];
}

/**
 * The packages one section of a manifest names.
 *
 * @param  array<mixed, mixed> $manifest
 * @return list<string>
 */
function namedUnder(array $manifest, string $section): array
{
    if (! array_key_exists($section, $manifest) || ! is_array($manifest[$section])) {
        return [];
    }

    return array_values(array_filter(array_keys($manifest[$section]), is_string(...)));
}

/**
 * Every module whose kind says it only exists while somebody is working.
 *
 * @return list<string> package names
 */
function theStandInModules(): array
{
    $found = [];

    foreach (Module::all() as $module) {
        if ($module->kind === Kind::StandIn) {
            $found[] = sprintf('modules/%s', $module->name);
        }
    }

    return $found;
}

it('finds a stand-in to check', function (): void {
    // The floor, and here it carries more than usual: every assertion below is
    // written over this list, so an empty one makes the whole file pass while
    // saying nothing — in exactly the state where a stand-in has been
    // reclassified as something else, which is one of the two ways this
    // guarantee can be lost.
    expect(theStandInModules())->not->toBeEmpty();
});

it('Q-R72, N1-R61 — no stand-in is a dependency of a release', function (): void {
    $required = whatTheRootRequires();
    $shipping = array_values(array_intersect(theStandInModules(), $required['require']));

    expect($shipping)->toBe([], sprintf(
        "These stand-ins would be installed into a release:\n  %s\n\n"
        . 'A release installs with `--no-dev`, and that is the whole of what keeps a '
        . 'stand-in out of it: the package is absent, so its provider is never discovered '
        . "and its classes are not in the bundle.\n"
        . 'Moved to `require`, the module ships — and then whether the app runs against a '
        . 'stand-in is decided by a config value, which is precisely what `N1-R61` refuses '
        . "and what `Q-R72` asks this gate to prevent.\n"
        . 'Move it back to `require-dev` (Q-R72, N1-R61).',
        implode("\n  ", $shipping),
    ));
});

it('Q-R72 — every stand-in is installed for development', function (): void {
    // The other half, and not the same assertion inverted: a module absent from
    // both sections is absent from the build entirely, so its tests do not run
    // and this file's own floor above is the only thing that would notice. That
    // is a module quietly switched off rather than one shipped by mistake —
    // less dangerous, equally silent.
    $required = whatTheRootRequires();
    $missing = array_values(array_diff(theStandInModules(), $required['require-dev']));

    expect($missing)->toBe([], sprintf(
        "These stand-ins are in neither section of the root manifest:\n  %s\n\n"
        . 'A module the root does not require is not installed, so nothing it declares is '
        . 'discovered and nothing it holds is autoloaded — including its own tests, which '
        . "is why nothing else here would report it.\n"
        . 'Require it under `require-dev` (Q-R72).',
        implode("\n  ", $missing),
    ));
});

it('N1-R61 — no composition names a stand-in', function (): void {
    // The failure this catches is a boot crash rather than a leak, and that is
    // worth stating: a release that names a class it did not install does not
    // quietly run against a stand-in, it fails to start. `bootstrap/` is where
    // that would be written, because it is the one place allowed to name an
    // adapter at all.
    $named = [];

    foreach (Tree::filesUnder(Tree::at('bootstrap'), '.php') as $path) {
        $said = (string) file_get_contents($path);

        foreach (Module::all() as $module) {
            if ($module->kind === Kind::StandIn && str_contains($said, $module->namespace)) {
                $named[] = sprintf('%s names %s', $path, $module->namespace);
            }
        }
    }

    sort($named);

    expect($named)->toBe([], sprintf(
        "These name a stand-in from a path that ships:\n  %s\n\n"
        . 'A stand-in is absent from a release install, so a composition that names one '
        . "resolves a class that is not there and the application does not boot.\n"
        . 'A stand-in takes its place through its own provider, which package discovery '
        . 'finds when the package is installed and does not find when it is not — so '
        . 'nothing outside the module has to know it exists (N1-R61, Q-R72).',
        implode("\n  ", $named),
    ));
});
