<?php

declare(strict_types=1);

use Tests\Support\Module;

// F2 — a presenter is pure: data in, view model out.
//
// This is where 100% coverage and mutation testing actually land, because it is
// where the decisions are. A presenter that holds a port stops being a function
// of its arguments: the test has to arrange the port before it can ask the
// question, and the thing being measured becomes the fake rather than the
// decision. It also puts IO one constructor away from the render path, on a
// device where the answer may take thirty seconds to arrive.
//
// The check is "no interface in the constructor" rather than a list of known
// port names. Every port is an interface by definition, a new one added
// tomorrow is caught without anyone updating a list here, and the things a
// presenter legitimately takes — values, typed collections, view models — are
// final readonly classes and pass.

it('F2 — a presenter is handed data, not a way to go and get it', function (): void {
    $offenders = [];

    foreach (Module::all() as $module) {
        foreach ($module->classNames() as $name) {
            if (! str_contains($name, '\Presenters\\')) {
                continue;
            }

            $constructor = new ReflectionClass($name)->getConstructor();

            foreach ($constructor?->getParameters() ?? [] as $parameter) {
                $type = $parameter->getType();

                if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    continue;
                }

                if (interface_exists($type->getName())) {
                    $offenders[] = sprintf('%s takes $%s (%s)', $name, $parameter->getName(), $type->getName());
                }
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These presenters were injected with something to call:\n  %s\n\n"
        . 'A presenter takes the answer, not the means of getting it. Move the call to '
        . 'the component that already knows it is on a screen, and hand the presenter '
        . 'what came back (F2).',
        implode("\n  ", $offenders),
    ));
});
