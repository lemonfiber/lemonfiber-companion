<?php

declare(strict_types=1);

use Modules\Kernel\Api\Attempted;
use Tests\Support\Module;

// N1-R41 — the app does not retain an undelivered action, and cannot present one
// as pending.
//
// `ADR-0020` spends its length rejecting the obvious kindness: hold the action
// and send it when the stack comes back. An action queued on a phone is an
// action the operator believes has happened, applied at a moment nobody chose,
// against a stack whose state has moved on — and the operator is not there to
// see it land.
//
// `Attempted` has two arms and no third, so nothing *can* be presented as
// pending through it. That is the mechanism, and this rule guards the way round
// it: a queue somewhere else. A property holding a list of actions is how the
// kindness gets re-implemented, one screen at a time, by somebody who has not
// read the ADR.
//
// Deliberately narrow. It cannot tell a queue of actions from a queue of
// anything else, so it asks about the types this application would queue — the
// ones whose presence in a collection means an action is waiting for a network
// that is not there.

/** Types that must never be found waiting in a collection. */
const NEVER_QUEUED = [
    'Attempted',
    'IdempotencyKey',
];

it('N1-R41 — nothing holds a collection of actions waiting to be sent', function (): void {
    $queues = [];

    foreach (Module::all() as $module) {
        foreach ($module->classNames() as $name) {
            $class = new ReflectionClass($name);

            foreach ($class->getProperties() as $property) {
                $type = $property->getType();

                if (! $type instanceof ReflectionNamedType || $type->getName() !== 'array') {
                    continue;
                }

                $doc = $property->getDocComment();

                if ($doc === false) {
                    continue;
                }

                foreach (NEVER_QUEUED as $held) {
                    if (preg_match(sprintf('/@var\s+(list|array)<[^>]*\b%s\b/', $held), $doc) === 1) {
                        $queues[] = sprintf('%s::$%s holds %s', $name, $property->getName(), $held);
                    }
                }
            }
        }
    }

    sort($queues);

    expect($queues)->toBe([], sprintf(
        "These hold actions waiting to be sent:\n  %s\n\n"
        . 'ADR-0020 refuses exactly this. An action queued on a phone is an action the '
        . 'operator believes has happened, applied at a moment nobody chose, against a '
        . "stack whose state has moved on — and they are not there to see it land.\n"
        . 'An action the app could not deliver is refused, and the operator presses the '
        . 'button again when they choose to (N1-R41).',
        implode("\n  ", $queues),
    ));
});

it('N1-R41 — an attempt has no arm meaning "pending"', function (): void {
    // The absence that keeps the requirement. A third arm would be reasonable
    // to add and is the whole thing the ADR argues against, so its absence is
    // asserted rather than trusted.
    expect(get_class_methods(Attempted::class))->toBe(['delivered', 'refused', 'on', 'either']);
});
