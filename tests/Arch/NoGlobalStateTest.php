<?php

declare(strict_types=1);

use Tests\Support\Module;

// A6 / I1 — no mutable global state, checked by reflection.
//
// Pest's architecture expectations have no rule for this, so rather than assume
// one and get a silently skipped check, this asks PHP directly. The reason it
// matters more here than in a web application: NativePHP runs a persistent
// process, not a request. A static cache that a web server would harmlessly
// rebuild on the next request survives between screens here, and becomes a
// stale answer on someone's phone long after the thing it cached changed.

it('declares no static property anywhere in a module', function (): void {
    $offenders = [];

    foreach (Module::all() as $module) {
        foreach ($module->classNames() as $name) {
            $reflection = new ReflectionClass($name);

            foreach ($reflection->getProperties(ReflectionProperty::IS_STATIC) as $property) {
                if ($property->getDeclaringClass()->getName() !== $name) {
                    continue;  // inherited from a framework base class, not ours
                }

                $offenders[] = sprintf('%s::$%s', $name, $property->getName());
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These hold state that survives a dispatch:\n  %s",
        implode("\n  ", $offenders),
    ));
});
