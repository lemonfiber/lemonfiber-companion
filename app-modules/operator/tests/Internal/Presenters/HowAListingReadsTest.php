<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\Presenters\HowAListingReads;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;

/**
 * A device with no session is not a stack running nothing.
 *
 * The two read alike on a screen and are not alike at all: one is *we could not
 * ask*, and the other is *we asked and the machine is running nothing*. A fold
 * that carried half a reading into the first would be the screen saying the
 * second, quietly, with fields left over from whatever it had before.
 *
 * So every field is asserted rather than the one the template branches on. A
 * value nothing reads is a value nothing can tell from any other, and the day a
 * screen starts drawing the verdict on the signed-out frame is the day this
 * says whether there was one.
 */
it('N1-R44 — a session that has ended carries no reading at all', function (): void {
    $signedOut = new HowAListingReads()->signedOut();

    expect($signedOut->went->isSignedIn)->toBeFalse()
        ->and($signedOut->went->cameBack())->toBeFalse()
        ->and($signedOut->services)->toBe([])
        ->and($signedOut->overall)->toBe('')
        ->and($signedOut->isSettling)->toBeFalse()
        ->and($signedOut->disturbs)->toBeNull();
});

it('carries the forms a stack declares by name, and none where they could not be read', function (): void {
    $reads = new HowAListingReads();
    $declared = $reads->forms(Forms::these(Form::called('library'), Form::called('full')));
    $met = $reads->formsMet(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $signedOut = $reads->formsSignedOut();

    expect([$declared->went->cameBack(), $declared->names])->toBe([true, ['library', 'full']])
        ->and([$met->went->cameBack(), $met->went->met, $met->names])->toBe([false, KindOfObstacle::StackDidNotAnswer->said(), []])
        ->and([$signedOut->went->isSignedIn, $signedOut->names])->toBe([false, []]);
});

it('a listing stopped by forms that could not be read says why, and nothing else', function (): void {
    $stopped = new HowAListingReads()->stoppedBy(HowTheReadingWent::somethingStopped(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect($stopped->went->met)->toBe(KindOfObstacle::StackDidNotAnswer->said())
        ->and([$stopped->services, $stopped->overall, $stopped->isSettling, $stopped->disturbs, $stopped->active, $stopped->leftOut])
        ->toBe([[], '', false, null, [], []]);
});
