<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\Presenters\HowAChangeReads;
use Modules\Operator\Internal\ViewModels\WhatAChangeTurnedOutToBe;

/**
 * The three ways this screen ends with nothing, and the fields none of them
 * may carry.
 *
 * `HowAListingReadsTest` makes this argument for the listing and it is sharper
 * here, because a change has something to claim that a listing has not. The
 * fields on this view model are not decoration: `holdsWhatWasAsked` is the
 * screen saying *the machine now holds what you asked for*, and
 * `wroteSomething` is it saying *something on that machine changed*. Either
 * one true on a reading that never happened is the app telling an operator
 * their machine was written to when nothing reached it.
 *
 * So every field is asserted, for every one of the three ways in, rather than
 * the one the template branches on. A value nothing reads is a value nothing
 * can tell from any other — and these three builders differ only in `went`,
 * so a test that checked which arm ran would pass against a builder that
 * filled the rest in wrongly.
 */

/**
 * Every field that must be empty, whichever way the screen got here.
 */
function saysNothingAtAll(WhatAChangeTurnedOutToBe $turned): void
{
    expect($turned->key)->toBe('')
        ->and($turned->fromSaid)->toBe('')
        ->and($turned->toSaid)->toBe('')
        ->and($turned->holdsNothingYet)->toBeFalse()
        ->and($turned->costSaid)->toBe('')
        ->and($turned->stanceSaid)->toBe('')
        ->and($turned->mustBeAgreedFirst)->toBeFalse()
        // The two that would be a lie rather than a blank.
        ->and($turned->holdsWhatWasAsked)->toBeFalse()
        ->and($turned->wroteSomething)->toBeFalse()
        ->and($turned->refusalSaid)->toBe('');
}

it('N1-R44 — a session that has ended carries no proposal at all', function (): void {
    $signedOut = new HowAChangeReads()->signedOut();

    expect($signedOut->went->isSignedIn)->toBeFalse()
        ->and($signedOut->went->cameBack())->toBeFalse();

    saysNothingAtAll($signedOut);
});

it('N1-R44 — a stack that could not be reached carries no proposal at all', function (): void {
    // The obstacle is the one thing this arm does carry, and it is carried on
    // `went` rather than smeared across the fields a proposal would fill.
    $met = new HowAChangeReads()->met(Obstacle::StackDidNotAnswer);

    expect($met->went->cameBack())->toBeFalse()
        ->and($met->went->met)->not->toBe('');

    saysNothingAtAll($met);
});

it('N1-R44 — a tap with no setting open is a reading that came back saying nothing', function (): void {
    // Not an obstacle: nothing went wrong with the stack, and this is the arm
    // that must not be confusable with the one above. It came back, and it has
    // nothing to report — which is a different sentence from could not ask.
    $nothingOpen = new HowAChangeReads()->nothingOpen();

    expect($nothingOpen->went->cameBack())->toBeTrue()
        ->and($nothingOpen->went->isSignedIn)->toBeTrue()
        ->and($nothingOpen->went->met)->toBe('');

    saysNothingAtAll($nothingOpen);
});
