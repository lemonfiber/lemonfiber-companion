<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\ViewModels;

use function expect;
use function it;

use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;

/**
 * The one place a screen asks whether it has anything of its own to draw.
 *
 * Three situations and one question. `cameBack()` is the question, and it used
 * to be written out as its own inverse on seven templates — both halves silent
 * is a blank screen and both halves drawing is the obstacle over the content,
 * and nothing would have said which had happened.
 *
 * So the question is asked here rather than restated, and this is what says the
 * answer is right. Each of the three is asserted whole rather than by the one
 * field a caller happens to read: two situations that agree on `cameBack()` and
 * disagree on `met` are two different screens.
 */
it('N1-R44 — a session that has ended met nothing, because nothing was asked', function (): void {
    $went = HowTheReadingWent::theSessionEnded();

    expect($went->isSignedIn)->toBeFalse()
        ->and($went->met)->toBe('')
        ->and($went->remedy)->toBe('')
        ->and($went->cameBack())->toBeFalse();
});

it('a reading that came back has nothing to report and is the screen\'s own to draw', function (): void {
    $went = HowTheReadingWent::itCameBack();

    expect($went->isSignedIn)->toBeTrue()
        ->and($went->met)->toBe('')
        ->and($went->remedy)->toBe('')
        ->and($went->cameBack())->toBeTrue();
});

it('N1-R10 — something met carries what happened and what to do about it', function (): void {
    // Both, because one is a fact about the world and the other is advice, and
    // a screen showing only the first leaves an operator with nothing to try.
    $went = HowTheReadingWent::somethingStopped(Obstacle::StackDidNotAnswer);

    expect($went->isSignedIn)->toBeTrue()
        ->and($went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($went->remedy)->toBe(Obstacle::StackDidNotAnswer->remedy())
        ->and($went->cameBack())->toBeFalse();
});

it('N3-R13 — a refused credential is a session that ended rather than something met', function (): void {
    // The decision this type exists to hold. Eight presenters used to ask
    // `Obstacle::meansWeAreSignedOut()` separately, which is a rule kept by
    // everybody remembering — and a screen that read it the other way would go
    // on rendering what it had already loaded under a session the stack had
    // stopped recognising.
    $went = HowTheReadingWent::somethingStopped(Obstacle::CredentialWasRefused);

    expect($went->isSignedIn)->toBeFalse()
        ->and($went->met)->toBe('')
        ->and($went->remedy)->toBe('')
        ->and($went->cameBack())->toBeFalse();
});
