<?php

declare(strict_types=1);

use Tests\Support\Module;

// Runs the composition root rather than reading it, so its mutants are judged
// here: see `scripts/mutation.php`.
pest()->group('holds:bootstrap/Composition');

// G8 — every port the kernel publishes is bound, once, to something real.
//
// A port with no binding fails at the moment a screen first asks for it, which
// on a device is after the operator has tapped something and is waiting. The
// container raises, the frame does not arrive, and the report says a class
// could not be resolved — a message that names the port rather than the
// omission, in a place nobody can attach a debugger to.
//
// It is also the failure that unit tests structurally cannot find. Every module
// test hands its subject a fake directly, precisely because the module may not
// know the container exists, so nothing below this test ever asks the container
// for anything. The composition root is the one place where the answer is
// wrong, and this is the one test that looks there.

it('G8 — every port is bound to exactly one adapter', function (): void {
    $unbound = [];

    foreach (Module::all() as $module) {
        if ($module->kind->value !== 'kernel') {
            continue;
        }

        foreach ($module->classNames() as $name) {
            if (! interface_exists($name)) {
                continue;
            }

            if (! app()->bound($name)) {
                $unbound[] = $name;
            }
        }
    }

    expect($unbound)->toBe([], sprintf(
        "These ports have no adapter behind them:\n  %s\n\n"
        . 'A port with no binding resolves at the moment a screen first asks for it — on '
        . 'a device, after the operator has tapped something and is waiting. Bind it in '
        . 'bootstrap/Composition, which is the one place allowed to know which implementation a '
        . "port gets.\nNo test below this one can find it: a module test hands its "
        . 'subject a fake directly, because the module is not allowed to know the '
        . 'container exists (G8, A3).',
        implode("\n  ", $unbound),
    ));
});

/**
 * Every kernel port the container has a binding for.
 *
 * @return list<class-string>
 */
function everyBoundPort(): array
{
    $found = [];

    foreach (Module::all() as $module) {
        if ($module->kind->value !== 'kernel') {
            continue;
        }

        foreach ($module->classNames() as $name) {
            if (interface_exists($name) && app()->bound($name)) {
                $found[] = $name;
            }
        }
    }

    return $found;
}

/**
 * What the container hands back for one port.
 *
 * A named function rather than a `make()` at the call site, because the
 * analyser refuses a checked exception raised inside a closure and every Pest
 * body is one — the same rule that put the container behind a method in the
 * composition root. The exception is deliberately not caught: a port that
 * cannot be built is this rule failing, and it fails loudest by raising with
 * the container's own message, which names what was missing.
 */
function builtFromTheContainer(string $port): mixed
{
    return app()->make($port);
}

it('G8 — every port resolves to something that is actually that port', function (): void {
    // `bound()` answers true for a binding that raises the moment anybody asks
    // it for anything, which is the same failure at the same moment with an
    // extra step. A closure naming a class that is not there, an adapter whose
    // constructor gained an argument nothing supplies, a `make()` for a
    // contract no provider binds — all of them are bound and none of them is an
    // adapter.
    //
    // It also reaches the code the rule above is about. A binding closure that
    // nothing resolves is a line the composition root carries and no test runs,
    // and the composition root is the one file where an untested line is a
    // launch-time fatal in front of an operator.
    $broken = [];

    foreach (everyBoundPort() as $name) {
        $built = builtFromTheContainer($name);

        if (! $built instanceof $name) {
            $broken[] = sprintf('%s resolved to %s', $name, get_debug_type($built));
        }
    }

    expect($broken)->toBe([], sprintf(
        "These ports are bound to something that is not them:\n  %s\n\n"
        . 'A binding is not a promise that anything can be built from it. Whatever this '
        . 'resolved to is what a screen asking for the port will be handed, and it does '
        . "not implement it — so the failure lands one call later, on a device (G8).",
        implode("\n  ", $broken),
    ));
});
