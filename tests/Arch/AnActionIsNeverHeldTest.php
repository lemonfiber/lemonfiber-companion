<?php

declare(strict_types=1);

use Modules\Kernel\Api\Attempted;
use Tests\Support\Module;

// N1-R41 and N3-R12 — the app does not retain an undelivered action, and cannot
// present one.
//
// Two requirements, one guarantee, and they are cited together here on purpose.
// N1-R41 says the operator's app must not retain an undelivered action, replay
// one on reconnecting, or present one as pending. N3-R12 says that while the
// stack is unreachable, a household member asking for something new must be
// declined rather than queued.
//
// They are the same sentence about two surfaces. Whoever reads N3-R12 next
// should find this rather than write a second queue-refusing check for the
// household module — which would be one fact in two places, and the copy that
// gets corrected is whichever the next person happens to open.
//
// What N3-R12 adds is who is looking: a member is told no by an app they did not
// configure and cannot diagnose, so the refusal has to be a sentence rather than
// a silence. `Attempted` carries a `Problem` on its refusing arm for exactly
// that, and has no third arm to fall through to.
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
// Deliberately narrow in what it names and deliberately wide in where it looks.
// It cannot tell a queue of actions from a queue of anything else, so it asks
// about the types this application would queue — the ones whose presence in a
// collection means an action is waiting for a network that is not there.
//
// **Where a class says it holds many is not one place, and the first two it was
// asked about were the two this codebase does not use.** A property typed
// `array` with a `@var` above it is the shape the rule was written against;
// every collection here is written the other way, as a promoted constructor
// parameter whose shape is on the constructor's `@param` — `Findings` is, and
// so is everything modelled on it. A queue built that way had no `@var`
// anywhere and no property the reading recognised, so the rule read the class
// and found nothing. `@implements IteratorAggregate<int, Attempted>` is the
// third, and it is the one a typed collection of them leads with.
//
// So all three are read, and the question asked of each is the same: does this
// class write down that it holds many of a type that must never be waiting.

/** Types that must never be found waiting in a collection. */
const NEVER_QUEUED = [
    'Attempted',
    'IdempotencyKey',
];

/**
 * Everywhere a class writes down what it holds.
 *
 * The class's own block for `@implements`, the constructor's for the promoted
 * parameters that are this codebase's collections, and each property's for one
 * declared the long way. A line at a time, because one tag is one claim and a
 * pattern spanning a whole block would read the end of one tag against the
 * start of the next.
 *
 * @param ReflectionClass<object> $class
 *
 * @return list<string>
 */
function everywhereAClassSaysWhatItHolds(ReflectionClass $class): array
{
    $blocks = [$class->getDocComment(), $class->getConstructor()?->getDocComment()];

    foreach ($class->getProperties() as $property) {
        $blocks[] = $property->getDocComment();
    }

    $lines = [];

    foreach ($blocks as $block) {
        if (is_string($block)) {
            $lines = [...$lines, ...explode("\n", $block)];
        }
    }

    return $lines;
}

/**
 * Whether one documented line says the class holds many of that type.
 *
 * The generic's name is what makes it many — `Attempted` alone is one answer to
 * one action, which is the shape every verb in this application returns and not
 * a queue of anything.
 */
function saysItHoldsManyOf(string $line, string $held): bool
{
    return preg_match(
        sprintf('/\b(?:list|array|iterable|Traversable|IteratorAggregate|Generator)\s*<.*\b%s\b/', $held),
        $line,
    ) === 1;
}

it('N1-R41 — nothing holds a collection of actions waiting to be sent', function (): void {
    $queues = [];

    foreach (Module::all() as $module) {
        foreach ($module->classNames() as $name) {
            foreach (everywhereAClassSaysWhatItHolds(new ReflectionClass($name)) as $line) {
                foreach (NEVER_QUEUED as $held) {
                    if (saysItHoldsManyOf($line, $held)) {
                        $queues[] = sprintf('%s holds many %s — %s', $name, $held, trim($line, " *\t"));
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

it('N1-R41 — the reading finds a holding wherever the class wrote it down', function (): void {
    // The judgement, handed each of the three spellings. A fixture can only be
    // written one way at a time, and the two that were missing are the two this
    // codebase actually uses — so which one a fixture happens to pick is
    // exactly what must not decide whether the rule holds.
    expect(saysItHoldsManyOf('     * @var list<Attempted> $waiting', 'Attempted'))->toBeTrue();
    expect(saysItHoldsManyOf('     * @param array<int, Attempted> $waiting', 'Attempted'))->toBeTrue();
    expect(saysItHoldsManyOf(' * @implements IteratorAggregate<int, Attempted>', 'Attempted'))->toBeTrue();
    expect(saysItHoldsManyOf('     * @var array<string, list<IdempotencyKey>>', 'IdempotencyKey'))->toBeTrue();

    // And one of them is not many. Every verb in this application answers with
    // a single `Attempted`, so a reading that counted that would fire on the
    // whole surface and be turned off within the week.
    expect(saysItHoldsManyOf('     * @param Attempted $answered', 'Attempted'))->toBeFalse();
    expect(saysItHoldsManyOf('     * @var list<Finding> $found', 'Attempted'))->toBeFalse();
});

it('N1-R41 — an attempt has no arm meaning "pending"', function (): void {
    // The absence that keeps the requirement. A third arm would be reasonable
    // to add and is the whole thing the ADR argues against, so its absence is
    // asserted rather than trusted.
    expect(get_class_methods(Attempted::class))->toBe(['delivered', 'refused', 'on', 'either']);
});
