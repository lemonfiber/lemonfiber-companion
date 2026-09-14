<?php

declare(strict_types=1);

use Modules\Kernel\Api\Awaiting;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Sdk\Api\RosterIsUnreadable;
use Modules\Sdk\Internal\Costs;

/**
 * What a stack reports, with whatever this case is about changed.
 *
 * @param  array<mixed> $differently
 * @return array<mixed>
 */
function whatAStackReportsItsVerbsCost(array $differently = []): array
{
    return [
        'disturbs' => [
            'starting' => ['bound' => 'bounded', 'seconds' => 180],
            'stopping' => ['bound' => 'bounded', 'seconds' => 10],
            'restarting' => ['bound' => 'bounded', 'seconds' => 45],
            ...$differently,
        ],
    ];
}

/** One answer carried out of an `either()` arm, which hands back objects. */
final readonly class WhatItTurnedOutToCost
{
    public function __construct(public int $seconds, public string $said) {}
}

/** The length a verb is held to, as a number a test can compare. */
function howLong(Disturbances $disturbs, WhatToDoWithIt $doing): int
{
    return whatItCosts($disturbs, $doing)->seconds;
}

/** The same, read as what it was waiting for. */
function whatItCosts(Disturbances $disturbs, WhatToDoWithIt $doing): WhatItTurnedOutToCost
{
    return $disturbs->forThe($doing)->either(
        bounded: static fn(int $seconds): WhatItTurnedOutToCost => new WhatItTurnedOutToCost(
            seconds: $seconds,
            said: 'a clock',
        ),
        // Minus one for an unbounded wait, which no clock can produce, so a
        // case meaning to assert a length cannot pass by meeting one instead.
        openEnded: static fn(Awaiting $awaiting): WhatItTurnedOutToCost => new WhatItTurnedOutToCost(
            seconds: -1,
            said: $awaiting->value,
        ),
    );
}

it('reads a length for each verb this surface offers', function (): void {
    // Three numbers and not one. A reader that took the first and used it
    // everywhere would be right about a stop and wrong about the other two,
    // and an operator confirming a restart would be shown ten seconds.
    $disturbs = Costs::in(whatAStackReportsItsVerbsCost());

    expect(howLong($disturbs, WhatToDoWithIt::Start))->toBe(180)
        ->and(howLong($disturbs, WhatToDoWithIt::Stop))->toBe(10)
        ->and(howLong($disturbs, WhatToDoWithIt::Restart))->toBe(45);
});

it('reads one with nothing bounding it as what it is waiting for', function (): void {
    $disturbs = Costs::in(whatAStackReportsItsVerbsCost([
        'stopping' => ['bound' => 'open-ended', 'until' => 'downloads'],
    ]));

    expect(whatItCosts($disturbs, WhatToDoWithIt::Stop)->said)->toBe('downloads');
});

it('reads the shape off the tag rather than off which field is there', function (): void {
    // A payload carrying both would otherwise be read as whichever this side
    // looked for first, and a bound read off the wrong shape is a number
    // nothing honours.
    $disturbs = Costs::in(whatAStackReportsItsVerbsCost([
        'stopping' => ['bound' => 'open-ended', 'seconds' => 10, 'until' => 'downloads'],
    ]));

    expect(howLong($disturbs, WhatToDoWithIt::Stop))->toBe(-1);
});

it('refuses a reading with no lengths at all', function (): void {
    // Not a stack whose verbs are free. `N2-R14` has this side refuse rather
    // than fill one in, because the reassuring answer is the one an operator
    // would confirm on.
    expect(fn(): object => Costs::in(['condition' => 'active']))
        ->toThrow(RosterIsUnreadable::class, 'disturbs');
});

it('refuses a reading that is short of one verb', function (): void {
    // Built without the verb rather than built and then unset, so the shape
    // this case is about is the one the reader is handed.
    $short = ['disturbs' => [
        'starting' => ['bound' => 'bounded', 'seconds' => 180],
        'stopping' => ['bound' => 'bounded', 'seconds' => 10],
    ]];

    expect(fn(): object => Costs::in($short))
        ->toThrow(RosterIsUnreadable::class, 'restarting');
});

it('refuses a length that is not a number', function (): void {
    expect(fn(): object => Costs::in(whatAStackReportsItsVerbsCost([
        'stopping' => ['bound' => 'bounded', 'seconds' => 'ten'],
    ])))->toThrow(RosterIsUnreadable::class, 'stopping');
});

it('refuses a wait for something this app has no case for', function (): void {
    // A word this side does not know is not a wait it can describe, and
    // rendering the word itself would put lemonfiber's vocabulary on a screen
    // in place of a sentence somebody wrote.
    expect(fn(): object => Costs::in(whatAStackReportsItsVerbsCost([
        'stopping' => ['bound' => 'open-ended', 'until' => 'the-weather'],
    ])))->toThrow(RosterIsUnreadable::class, 'stopping');
});

it('refuses a listing whose lengths are not a shape at all', function (): void {
    // A word where the block belongs. `N2-R14` again: read as *nothing*, every
    // verb would silently become free.
    expect(fn(): object => Costs::in(['disturbs' => 'quick']))
        ->toThrow(RosterIsUnreadable::class, 'disturbs');
});

it('refuses a verb that arrived without a tag to read its shape off', function (): void {
    // The tag is what decides which of the two shapes this is, so a verb
    // without one is a bound this side cannot read — not a bound of zero, and
    // not whichever field happens to be present.
    expect(fn(): object => Costs::in(whatAStackReportsItsVerbsCost([
        'stopping' => ['seconds' => 10],
    ])))->toThrow(RosterIsUnreadable::class, 'bound');
});

it('refuses a verb whose bound is not a shape', function (): void {
    expect(fn(): object => Costs::in(whatAStackReportsItsVerbsCost([
        'stopping' => 'ten seconds',
    ])))->toThrow(RosterIsUnreadable::class, 'bound');
});

it('refuses a bounded verb that never said how long', function (): void {
    expect(fn(): object => Costs::in(whatAStackReportsItsVerbsCost([
        'stopping' => ['bound' => 'bounded'],
    ])))->toThrow(RosterIsUnreadable::class, 'stopping');
});

it('refuses an unbounded verb that never said what it waits for', function (): void {
    // *Unbounded, and nothing about what for* is the answer that leaves an
    // operator watching a spinner with nothing to decide on.
    expect(fn(): object => Costs::in(whatAStackReportsItsVerbsCost([
        'stopping' => ['bound' => 'open-ended'],
    ])))->toThrow(RosterIsUnreadable::class, 'stopping');
});

it('refuses a wait that is not a word', function (): void {
    expect(fn(): object => Costs::in(whatAStackReportsItsVerbsCost([
        'stopping' => ['bound' => 'open-ended', 'until' => 4],
    ])))->toThrow(RosterIsUnreadable::class, 'stopping');
});
