<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Api;

use function expect;
use function it;

use Modules\Health\Api\WhatWasHeardSoFar;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatWasHeard;

use function sprintf;

/** A moment, counted in seconds from one a test starts at. */
function secondsIn(int $seconds): Instant
{
    return Instant::atEpochSeconds(1_790_000_000 + $seconds);
}

/** A summary, told apart from another by its word. */
function aSummaryThatSays(HowItStands $standing): TheHealthSummary
{
    return TheHealthSummary::of($standing, 0, '');
}

/** One line carried out of an arm. */
final readonly class WhatTheScreenHolds
{
    public function __construct(public string $said) {}
}

/** The summary held, by which arm it took, with what it carried. */
function theSummaryHeld(WhatWasHeardSoFar $heard): string
{
    return $heard->summary(
        none: static fn(): WhatTheScreenHolds => new WhatTheScreenHolds('none'),
        current: static fn(TheHealthSummary $summary): WhatTheScreenHolds => new WhatTheScreenHolds(sprintf('current %s', $summary->standing()->value)),
        asOf: static fn(TheHealthSummary $summary, Instant $at): WhatTheScreenHolds => new WhatTheScreenHolds(sprintf('%s as of %d', $summary->standing()->value, $at->epochSeconds() - 1_790_000_000)),
    )->said;
}

/** What stopped the subscription, by which arm it took. */
function whatStoppedIt(WhatWasHeardSoFar $heard): string
{
    return $heard->stoppedBy(
        nothing: static fn(): WhatTheScreenHolds => new WhatTheScreenHolds('nothing'),
        met: static fn(Obstacle $why): WhatTheScreenHolds => new WhatTheScreenHolds($why->value),
    )->said;
}

it('holds nothing before it has listened, and may listen at once', function (): void {
    $heard = WhatWasHeardSoFar::nothingYet();

    expect(theSummaryHeld($heard))->toBe('none')
        ->and(whatStoppedIt($heard))->toBe('nothing')
        ->and($heard->isListening())->toBeFalse()
        ->and($heard->mayListen(secondsIn(0)))->toBeTrue()
        ->and($heard->hasGoneQuiet(secondsIn(3_600)))->toBeFalse();
});

it('counts silence from the moment a subscription opened, until twice the heartbeat has passed', function (): void {
    $heard = WhatWasHeardSoFar::nothingYet()->after(WhatWasHeard::nothing(), secondsIn(0));

    expect($heard->isListening())->toBeTrue()
        ->and(theSummaryHeld($heard))->toBe('none')
        ->and($heard->hasGoneQuiet(secondsIn(30)))->toBeFalse()
        ->and($heard->hasGoneQuiet(secondsIn(31)))->toBeTrue();
});

it('keeps counting silence while nothing arrives, however often it is asked', function (): void {
    $heard = WhatWasHeardSoFar::nothingYet()
        ->after(WhatWasHeard::nothing(), secondsIn(0))
        ->after(WhatWasHeard::nothing(), secondsIn(20));

    expect($heard->hasGoneQuiet(secondsIn(31)))->toBeTrue();
});

it('starts silence again at a sign of life, which carries no summary', function (): void {
    $heard = WhatWasHeardSoFar::nothingYet()
        ->after(WhatWasHeard::nothing(), secondsIn(0))
        ->after(WhatWasHeard::aSignOfLife(), secondsIn(20));

    expect($heard->hasGoneQuiet(secondsIn(50)))->toBeFalse()
        ->and($heard->hasGoneQuiet(secondsIn(51)))->toBeTrue()
        ->and($heard->isListening())->toBeTrue()
        ->and(theSummaryHeld($heard))->toBe('none');
});

it('holds a summary that arrived as current, and starts silence again from it', function (): void {
    $heard = WhatWasHeardSoFar::nothingYet()->after(WhatWasHeard::said(aSummaryThatSays(HowItStands::Advisory)), secondsIn(5));

    expect(theSummaryHeld($heard))->toBe('current advisory')
        ->and(whatStoppedIt($heard))->toBe('nothing')
        ->and($heard->isListening())->toBeTrue()
        ->and($heard->mayListen(secondsIn(5)))->toBeTrue()
        ->and($heard->hasGoneQuiet(secondsIn(35)))->toBeFalse()
        ->and($heard->hasGoneQuiet(secondsIn(36)))->toBeTrue();
});

it('keeps a summary current while the stream stays open, speaking or not', function (): void {
    $heard = WhatWasHeardSoFar::nothingYet()
        ->after(WhatWasHeard::said(aSummaryThatSays(HowItStands::Healthy)), secondsIn(0))
        ->after(WhatWasHeard::nothing(), secondsIn(2))
        ->after(WhatWasHeard::aSignOfLife(), secondsIn(4));

    expect(theSummaryHeld($heard))->toBe('current healthy');
});

it('holds a summary from before a close as of when it arrived, and waits out the break before opening again', function (): void {
    $heard = WhatWasHeardSoFar::nothingYet()
        ->after(WhatWasHeard::said(aSummaryThatSays(HowItStands::Healthy)), secondsIn(0))
        ->after(WhatWasHeard::closed(), secondsIn(20));

    expect(theSummaryHeld($heard))->toBe('healthy as of 0')
        ->and(whatStoppedIt($heard))->toBe('nothing')
        ->and($heard->isListening())->toBeFalse()
        ->and($heard->hasGoneQuiet(secondsIn(3_600)))->toBeFalse()
        ->and($heard->mayListen(secondsIn(29)))->toBeFalse()
        ->and($heard->mayListen(secondsIn(30)))->toBeTrue();
});

it('says what stopped a subscription, holds what came before it as not current, and waits out the break', function (): void {
    $heard = WhatWasHeardSoFar::nothingYet()
        ->after(WhatWasHeard::said(aSummaryThatSays(HowItStands::Critical)), secondsIn(0))
        ->after(WhatWasHeard::met(Obstacle::StackDidNotAnswer), secondsIn(40));

    expect(whatStoppedIt($heard))->toBe('no_answer')
        ->and(theSummaryHeld($heard))->toBe('critical as of 0')
        ->and($heard->isListening())->toBeFalse()
        ->and($heard->mayListen(secondsIn(49)))->toBeFalse()
        ->and($heard->mayListen(secondsIn(50)))->toBeTrue();
});

it('forgets what stopped it once a subscription opens again, and the old summary stays not current', function (): void {
    $heard = WhatWasHeardSoFar::nothingYet()
        ->after(WhatWasHeard::said(aSummaryThatSays(HowItStands::Broken)), secondsIn(0))
        ->after(WhatWasHeard::met(Obstacle::StackDidNotAnswer), secondsIn(40))
        ->after(WhatWasHeard::nothing(), secondsIn(50));

    expect(whatStoppedIt($heard))->toBe('nothing')
        ->and(theSummaryHeld($heard))->toBe('broken as of 0')
        ->and($heard->isListening())->toBeTrue()
        ->and($heard->mayListen(secondsIn(50)))->toBeTrue();
});

it('forgets what stopped it when the stream closes on its own', function (): void {
    $heard = WhatWasHeardSoFar::nothingYet()
        ->after(WhatWasHeard::met(Obstacle::CredentialWasRefused), secondsIn(0))
        ->after(WhatWasHeard::closed(), secondsIn(10));

    expect(whatStoppedIt($heard))->toBe('nothing')
        ->and(theSummaryHeld($heard))->toBe('none');
});

it('holds a new summary as current, however stale the one before it was', function (): void {
    $heard = WhatWasHeardSoFar::nothingYet()
        ->after(WhatWasHeard::said(aSummaryThatSays(HowItStands::Broken)), secondsIn(0))
        ->after(WhatWasHeard::closed(), secondsIn(40))
        ->after(WhatWasHeard::said(aSummaryThatSays(HowItStands::Stopped)), secondsIn(50));

    expect(theSummaryHeld($heard))->toBe('current stopped')
        ->and($heard->mayListen(secondsIn(50)))->toBeTrue();
});

it('holds nothing as current once nobody can see it, and opens again the moment somebody can', function (): void {
    $heard = WhatWasHeardSoFar::nothingYet()
        ->after(WhatWasHeard::said(aSummaryThatSays(HowItStands::Healthy)), secondsIn(0))
        ->after(WhatWasHeard::closed(), secondsIn(5))
        ->wentAway();

    expect(theSummaryHeld($heard))->toBe('healthy as of 0')
        ->and($heard->isListening())->toBeFalse()
        ->and($heard->mayListen(secondsIn(5)))->toBeTrue()
        ->and($heard->hasGoneQuiet(secondsIn(3_600)))->toBeFalse();
});

it('keeps what stopped it while nobody can see it', function (): void {
    $heard = WhatWasHeardSoFar::nothingYet()
        ->after(WhatWasHeard::met(Obstacle::StackDidNotAnswer), secondsIn(0))
        ->wentAway();

    expect(whatStoppedIt($heard))->toBe('no_answer')
        ->and($heard->mayListen(secondsIn(0)))->toBeTrue();
});
