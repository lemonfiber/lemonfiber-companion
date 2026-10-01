<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Api;

use function expect;
use function it;

use Modules\Health\Api\WhatTheWalkSaidSoFar;
use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatTheWalkSaid;

use function sprintf;

/** A moment in a walk, counted in seconds from one a test starts at. */
function secondsIntoTheWalk(int $seconds): Instant
{
    return Instant::atEpochSeconds(1_790_000_000 + $seconds);
}

/** A step a walk says, told apart from another by its stage. */
function aStepAt(WalkthroughStep $step): WhatTheWalkSaid
{
    return WhatTheWalkSaid::said(ALineItSaid::withoutDetail($step, 'Doing what this step does'));
}

/** One line carried out of an arm of the step held. */
final readonly class WhatTheWalkScreenHolds
{
    public function __construct(public string $said) {}
}

/** The step held, by which arm it took, with what it carried. */
function theStepHeld(WhatTheWalkSaidSoFar $heard): string
{
    return $heard->step(
        none: static fn(): WhatTheWalkScreenHolds => new WhatTheWalkScreenHolds('none'),
        current: static fn(ALineItSaid $line): WhatTheWalkScreenHolds => new WhatTheWalkScreenHolds(sprintf('current %s', $line->step()->value)),
        asOf: static fn(ALineItSaid $line, Instant $at): WhatTheWalkScreenHolds => new WhatTheWalkScreenHolds(sprintf('%s as of %d', $line->step()->value, $at->epochSeconds() - 1_790_000_000)),
    )->said;
}

it('holds no step before it has listened, and may listen at once', function (): void {
    $heard = WhatTheWalkSaidSoFar::nothingYet();

    expect(theStepHeld($heard))->toBe('none')
        ->and($heard->isListening())->toBeFalse()
        ->and($heard->hasBroken())->toBeFalse()
        ->and($heard->mayListen(secondsIntoTheWalk(0)))->toBeTrue()
        ->and($heard->hasGoneQuiet(secondsIntoTheWalk(3_600)))->toBeFalse();
});

it('counts silence from the moment the subscription opened, however often it is asked', function (): void {
    $heard = WhatTheWalkSaidSoFar::nothingYet()
        ->after(WhatTheWalkSaid::nothing(), secondsIntoTheWalk(0))
        ->after(WhatTheWalkSaid::nothing(), secondsIntoTheWalk(20));

    expect($heard->isListening())->toBeTrue()
        ->and(theStepHeld($heard))->toBe('none')
        ->and($heard->hasGoneQuiet(secondsIntoTheWalk(30)))->toBeFalse()
        ->and($heard->hasGoneQuiet(secondsIntoTheWalk(31)))->toBeTrue();
});

it('starts silence again at a sign of life, which carries no step', function (): void {
    $heard = WhatTheWalkSaidSoFar::nothingYet()
        ->after(WhatTheWalkSaid::nothing(), secondsIntoTheWalk(0))
        ->after(WhatTheWalkSaid::aSignOfLife(), secondsIntoTheWalk(20));

    expect($heard->hasGoneQuiet(secondsIntoTheWalk(50)))->toBeFalse()
        ->and($heard->hasGoneQuiet(secondsIntoTheWalk(51)))->toBeTrue()
        ->and(theStepHeld($heard))->toBe('none');
});

it('holds the step that arrived as current, and starts silence again from it', function (): void {
    $heard = WhatTheWalkSaidSoFar::nothingYet()->after(aStepAt(WalkthroughStep::Searching), secondsIntoTheWalk(5));

    expect(theStepHeld($heard))->toBe('current searching')
        ->and($heard->isListening())->toBeTrue()
        ->and($heard->hasGoneQuiet(secondsIntoTheWalk(35)))->toBeFalse()
        ->and($heard->hasGoneQuiet(secondsIntoTheWalk(36)))->toBeTrue();
});

it('keeps the step current while the stream stays open, speaking or not', function (): void {
    $heard = WhatTheWalkSaidSoFar::nothingYet()
        ->after(aStepAt(WalkthroughStep::Downloading), secondsIntoTheWalk(0))
        ->after(WhatTheWalkSaid::nothing(), secondsIntoTheWalk(5))
        ->after(WhatTheWalkSaid::aSignOfLife(), secondsIntoTheWalk(10));

    expect(theStepHeld($heard))->toBe('current downloading');
});

it('holds the newest step, even one at an earlier stage than the step before it', function (): void {
    $heard = WhatTheWalkSaidSoFar::nothingYet()
        ->after(aStepAt(WalkthroughStep::Grabbing), secondsIntoTheWalk(0))
        ->after(aStepAt(WalkthroughStep::Searching), secondsIntoTheWalk(5));

    expect(theStepHeld($heard))->toBe('current searching');
});

it('holds a step from before a close as of when it arrived, and waits out the break before opening again', function (): void {
    $heard = WhatTheWalkSaidSoFar::nothingYet()
        ->after(aStepAt(WalkthroughStep::Importing), secondsIntoTheWalk(0))
        ->after(WhatTheWalkSaid::closed(), secondsIntoTheWalk(20));

    expect(theStepHeld($heard))->toBe('importing as of 0')
        ->and($heard->isListening())->toBeFalse()
        ->and($heard->hasBroken())->toBeTrue()
        ->and($heard->hasGoneQuiet(secondsIntoTheWalk(3_600)))->toBeFalse()
        ->and($heard->mayListen(secondsIntoTheWalk(29)))->toBeFalse()
        ->and($heard->mayListen(secondsIntoTheWalk(30)))->toBeTrue();
});

it('holds a step as not current once the stream could not be read, and waits out the break', function (): void {
    $heard = WhatTheWalkSaidSoFar::nothingYet()
        ->after(aStepAt(WalkthroughStep::Scanning), secondsIntoTheWalk(0))
        ->after(WhatTheWalkSaid::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), secondsIntoTheWalk(40));

    expect(theStepHeld($heard))->toBe('scanning as of 0')
        ->and($heard->isListening())->toBeFalse()
        ->and($heard->hasBroken())->toBeTrue()
        ->and($heard->mayListen(secondsIntoTheWalk(49)))->toBeFalse()
        ->and($heard->mayListen(secondsIntoTheWalk(50)))->toBeTrue();
});

it('keeps the old step not current when the stream opens again, until a new one arrives', function (): void {
    $opened = WhatTheWalkSaidSoFar::nothingYet()
        ->after(aStepAt(WalkthroughStep::Choosing), secondsIntoTheWalk(0))
        ->after(WhatTheWalkSaid::closed(), secondsIntoTheWalk(10))
        ->after(WhatTheWalkSaid::nothing(), secondsIntoTheWalk(20));

    expect(theStepHeld($opened))->toBe('choosing as of 0')
        ->and($opened->isListening())->toBeTrue()
        ->and($opened->hasBroken())->toBeFalse()
        ->and($opened->mayListen(secondsIntoTheWalk(20)))->toBeTrue()
        ->and(theStepHeld($opened->after(aStepAt(WalkthroughStep::Available), secondsIntoTheWalk(25))))->toBe('current available');
});

it('holds nothing as current once nobody can see it, and opens again the moment somebody can, which is no break', function (): void {
    // Let go of as nobody could see it: the stream answers the letting go as
    // closed, and going away is still not a break.
    $heard = WhatTheWalkSaidSoFar::nothingYet()
        ->after(aStepAt(WalkthroughStep::Downloading), secondsIntoTheWalk(0))
        ->after(WhatTheWalkSaid::closed(), secondsIntoTheWalk(5))
        ->wentAway();

    expect(theStepHeld($heard))->toBe('downloading as of 0')
        ->and($heard->isListening())->toBeFalse()
        ->and($heard->hasBroken())->toBeFalse()
        ->and($heard->mayListen(secondsIntoTheWalk(5)))->toBeTrue()
        ->and($heard->hasGoneQuiet(secondsIntoTheWalk(3_600)))->toBeFalse();
});
