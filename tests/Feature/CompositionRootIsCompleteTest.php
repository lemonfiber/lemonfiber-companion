<?php

declare(strict_types=1);

use Tests\Support\Module;

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
