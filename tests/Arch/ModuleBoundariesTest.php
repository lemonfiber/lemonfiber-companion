<?php

declare(strict_types=1);

use Tests\Support\Kind;
use Tests\Support\Module;

// The dependency rules in ARCHITECTURE.md, generated from what each module
// declares itself to be.
//
// Written as a loop rather than as one arch() call per module pair on purpose.
// A hand-written list has to be extended every time a module is added, and the
// failure mode of forgetting is silence: the new module simply has no rules and
// nothing says so. Deriving them from the manifests means a module is governed
// the moment it exists.

$modules = Module::populated();

// Not a guard against an empty repository — a guard against this file quietly
// testing nothing. If every module is empty, that is a fact worth stating
// rather than a green tick.
it('has modules to check', function () use ($modules): void {
    expect($modules)->not->toBeEmpty(
        'No module holds a class yet, so every boundary rule below is vacuous.',
    );
})->skip(
    $modules === [],
    'No module holds a class yet. These rules apply as soon as one does.',
);

foreach ($modules as $module) {
    $vendors = $module->kind->forbiddenVendors();

    if ($vendors !== []) {
        arch(sprintf('A7/E4 — %s stays inside what a %s module may name', $module->name, $module->kind->value))
            ->expect($module->namespace)
            ->not->toUse($vendors);
    }

    $others = $module->forbiddenModuleNamespaces();

    if ($others !== []) {
        arch(sprintf("E1 — %s respects the other modules' boundaries", $module->name))
            ->expect($module->namespace)
            ->not->toUse($others);
    }

    // A module's own internals are its own business; nobody else's.
    arch(sprintf('E2 — %s publishes an Api and keeps the rest to itself', $module->name))
        ->expect(sprintf('%s\\Internal', $module->namespace))
        ->not->toBeUsedIn(
            array_map(
                static fn(Module $other): string => $other->namespace,
                array_values(array_filter(
                    Module::all(),
                    static fn(Module $other): bool => $other->name !== $module->name,
                )),
            ),
        );

    if (! $module->kind->renders()) {
        // Only a surface holds state the renderer re-reads. Everything else is
        // a value or a decision, and both are safer readonly.
        //
        // Asked by reflection rather than with `->toBeReadonly()`, which refuses
        // an interface. `kernel` is the module of ports, so that expectation
        // fails every port it publishes for declaring no state at all — a gate
        // that blocks its own cure. Interfaces and enums are skipped by name
        // below: an interface holds no state and an enum cannot be changed.
        it(sprintf('%s holds no mutable state', $module->name), function () use ($module): void {
            $mutable = [];

            foreach ($module->classNames() as $name) {
                $class = new ReflectionClass($name);

                if ($class->isInterface() || $class->isEnum()) {
                    continue;
                }

                if (! $class->isReadOnly()) {
                    $mutable[] = $name;
                }
            }

            expect($mutable)->toBe([], sprintf(
                "These can be changed after they are built:\n  %s\n\n"
                . 'Only a surface holds mutable state, because only a surface is re-read by '
                . 'the renderer. Everywhere else a value that can change after construction '
                . 'is a value whose invariants were checked once and can be false by the time '
                . 'anyone reads it. Mark the class readonly.',
                implode("\n  ", $mutable),
            ));
        });
    }
}

// ---------------------------------------------------------------------------
// The exceptions, asserted by name so they cannot be widened by accident.
// ---------------------------------------------------------------------------

// N1-R16. The composer manifests already make this true — modules/sdk is the
// only one requiring lemonfiber/sdk-php — but that only fails when the
// dependency analyser runs. This fails in the test suite, which runs first.
arch('E3 — the SDK is named in exactly one module')
    ->expect('Lemonfiber\Sdk')
    ->toOnlyBeUsedIn('Modules\Sdk');

arch('nothing but the sdk adapter speaks HTTP')
    ->expect(['GuzzleHttp', 'Saloon', 'Symfony\Component\HttpClient'])
    ->toOnlyBeUsedIn('Modules\Sdk');

arch('nothing opens a socket by hand')
    ->expect(['curl_init', 'curl_exec', 'fsockopen', 'stream_socket_client'])
    ->not->toBeUsed();

// The composition root is the one place a port is allowed to meet an adapter.
arch('only the composition root names an adapter')
    ->expect(array_map(
        static fn(Module $m): string => $m->namespace,
        array_values(array_filter(
            Module::all(),
            static fn(Module $m): bool => $m->kind === Kind::Adapter,
        )),
    ))
    ->not->toBeUsedIn(array_map(
        static fn(Module $m): string => $m->namespace,
        array_values(array_filter(
            Module::all(),
            static fn(Module $m): bool => $m->kind !== Kind::Adapter,
        )),
    ));
