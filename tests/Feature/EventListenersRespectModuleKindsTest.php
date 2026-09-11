<?php

declare(strict_types=1);

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\Dispatcher as Events;
use Tests\Support\Module;

// E5 — a listener is bound by the module kinds like anything else.
//
// Laravel resolves a listener from a string, so the class that reacts to
// another module's event never names it in a `use` statement and never appears
// in the dependency graph a Pest arch expectation reads. A capability module
// listening to a surface module's event is a boundary break that every other
// rule in this suite is structurally unable to see.
//
// So the check asks the dispatcher what it actually holds, after the whole
// application has booted and all twelve module providers have registered
// whatever they register. That also makes it a composition-root test, which is
// why it lives in the Feature suite.

it('E5 — no listener reacts to an event its module may not name', function (): void {
    $dispatcher = app(Dispatcher::class);

    expect($dispatcher)->toBeInstanceOf(Events::class);

    $offenders = [];

    foreach ($dispatcher->getRawListeners() as $event => $listeners) {
        foreach (listenerClasses($listeners) as $listener) {
            $breach = boundaryBreach($listener, $event);

            if ($breach !== '') {
                $offenders[] = $breach;
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These listeners reach across a boundary the dispatcher hides:\n  %s\n\n"
        . 'A listener is resolved from a string, so it never names the event class and '
        . 'never shows up in the import graph an architecture rule reads. The module '
        . 'kinds still apply: a capability cannot react to a surface, and neither can '
        . 'reach an adapter. Move the reaction to the module that is allowed to know '
        . 'about both — which is usually the composition root (E5, E1).',
        implode("\n  ", $offenders),
    ));
});

/**
 * Listener class names, from whatever shape the dispatcher stored them in.
 *
 * A closure has no class and cannot be attributed to a module, so it is not
 * reported. The composition root is where a closure listener belongs anyway,
 * and a closure written inside a module still has to import what it uses, which
 * puts it back within reach of the ordinary boundary rules.
 *
 * @return list<string>
 */
function listenerClasses(mixed $listeners): array
{
    if (! is_array($listeners)) {
        return [];
    }

    $found = [];

    foreach ($listeners as $listener) {
        $name = listenerClassName($listener);

        if ($name !== '') {
            $found[] = $name;
        }
    }

    return $found;
}

/** One listener's class, or an empty string where it has none. */
function listenerClassName(mixed $listener): string
{
    if (is_string($listener)) {
        return explode('@', $listener)[0];
    }

    if (! is_array($listener)) {
        return '';
    }

    $subject = $listener[0] ?? null;

    if (is_string($subject)) {
        return $subject;
    }

    return is_object($subject) ? $subject::class : '';
}

/** What is wrong with this pairing, or an empty string if nothing is. */
function boundaryBreach(string $listener, string $event): string
{
    foreach (Module::all() as $module) {
        if (! str_starts_with($listener, sprintf('%s\\', $module->namespace))) {
            continue;
        }

        foreach ($module->forbiddenModuleNamespaces() as $forbidden) {
            if (str_starts_with($event, sprintf('%s\\', $forbidden))) {
                return sprintf('%s listens for %s', $listener, $event);
            }
        }
    }

    return '';
}
