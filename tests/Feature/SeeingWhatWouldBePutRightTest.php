<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Repairs;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsNotConfigured;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Undoing;
use Modules\Operator\Internal\Screens\WhatWouldBePutRight;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatWouldMend;
use Tests\Support\Fakes\StacksInMemory;

// N2-R4 — what a repair does, what else it affects and whether it can be undone,
// stated before anybody is asked to confirm.
//
// The types for this have been built and tested since the health screen landed
// and the port was held back, because every action on this surface arrives as a
// job and the job reading did not exist. It does now, and this is the screen.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine being asked what it would put right. */
function theStackBeingOfferedRepairs(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** Two repairs: one reversible with a consequence, one permanent with none. */
function aListingWorthReading(): Offer
{
    return Offer::of('agreement-a-test-can-name', Repairs::of(
        Repair::offered(
            'storage.one-filesystem',
            'Move the library onto the larger disk',
            Effects::of('Downloads pause while it moves'),
            Undoing::Possible,
        ),
        Repair::offered(
            'credentials.expired',
            'Forget the expired credential',
            Effects::nothingElse(),
            Undoing::Permanent,
        ),
    ));
}

/**
 * The screen, with a stack it knows and a keychain holding whatever a test says.
 *
 * Named for this file, since the root suites share one namespace (`G10`).
 */
function theRepairsScreen(
    AStackThatWouldMend $mending,
    ?string $named = null,
    bool $signedIn = true,
): WhatWouldBePutRight {
    $stack = theStackBeingOfferedRepairs();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'));
    }

    $screen = new WhatWouldBePutRight($mending, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $named ?? $stack->id()->stored()]);

    return $screen;
}

it('N2-R4 — states what each repair does, affects and can undo, before any yes', function (): void {
    // All three clauses on every row. A screen carrying the sentence and
    // dropping whether it can be taken back would satisfy any assertion about
    // what appeared and still leave somebody agreeing to something permanent
    // believing they could put it back.
    $rows = theRepairsScreen(AStackThatWouldMend::offering(aListingWorthReading()))->repairs();

    expect($rows[0]->does)->toBe('Move the library onto the larger disk')
        ->and($rows[0]->effects)->toBe(['Downloads pause while it moves'])
        ->and($rows[0]->undoing)->toBe(Undoing::Possible->saidOnTheScreen())
        ->and($rows[1]->does)->toBe('Forget the expired credential')
        ->and($rows[1]->effects)->toBe([])
        ->and($rows[1]->undoing)->toBe(Undoing::Permanent->saidOnTheScreen());
});

it('N2-R4 — a permanent repair and a reversible one do not read alike', function (): void {
    // The assertion the requirement is actually about. Two rows that differed
    // only in a field nothing rendered would pass everything above.
    $rows = theRepairsScreen(AStackThatWouldMend::offering(aListingWorthReading()))->repairs();

    expect($rows[0]->undoing)->not->toBe($rows[1]->undoing);
});

it('N2-R7 — asking costs a round trip, then a read of the handle', function (): void {
    // The unconfirmed form is still a job. One of each per frame, and never two
    // askings — a second would be a second piece of work on somebody's machine
    // for a question already asked.
    $mending = AStackThatWouldMend::offering(aListingWorthReading());
    $screen = theRepairsScreen($mending);

    $screen->repairs();
    $screen->howMany();
    $screen->isWorkingItOut();

    expect($mending->askings())->toBe(1)
        ->and($mending->readings())->toBe(1)
        ->and($mending->askedAfter()?->shown())->toBe(AStackThatWouldMend::THE_JOB);
});

it('N2-R7 — work still going is a state of the screen, not a spinner', function (): void {
    $screen = theRepairsScreen(AStackThatWouldMend::stillWorkingItOut());

    expect($screen->isWorkingItOut())->toBeTrue()
        ->and($screen->hasEnded())->toBeFalse()
        ->and($screen->howMany())->toBe(0)
        ->and($screen->met())->toBe('');
});

it('N1-R17 — asking again while the work runs reads the same job, and starts none', function (): void {
    // The distinction that makes this port safe to hold a handle for: the work
    // is the stack's, and repeating the read changes nothing. Starting a second
    // job for one question would be two lots of work on a machine.
    $mending = AStackThatWouldMend::stillWorkingItOut();
    $screen = theRepairsScreen($mending);

    $screen->isWorkingItOut();
    $screen->again();
    $screen->isWorkingItOut();

    expect($mending->askings())->toBe(1)
        ->and($mending->readings())->toBe(2);
});

it('asking again after a finished listing asks the stack afresh', function (): void {
    // The whole of what makes the button mean something. A finished job answers
    // the same listing however often it is read, so an operator who has just
    // changed something on their machine and taps *ask again* would be shown
    // what is already on the screen — and would reasonably conclude the fix did
    // not take. A second asking is the only way to learn otherwise.
    $mending = AStackThatWouldMend::offering(aListingWorthReading());
    $screen = theRepairsScreen($mending);

    $screen->howMany();
    $screen->again();
    $screen->howMany();

    expect($mending->askings())->toBe(2)
        ->and($mending->readings())->toBe(2);
});

it('a job the stack forgot is its own state, and asking again starts a new one', function (): void {
    // Not a fault and not an answer. There is nothing left to read, so this is
    // the one case where asking again has to be a fresh asking — a screen that
    // re-read the dead handle would say *expired* forever.
    $mending = AStackThatWouldMend::thatForgotTheJob();
    $screen = theRepairsScreen($mending);

    expect($screen->hasEnded())->toBeTrue()
        ->and($screen->isWorkingItOut())->toBeFalse()
        ->and($screen->met())->toBe('');

    $screen->again();
    $screen->hasEnded();

    expect($mending->askings())->toBe(2);
});

it('a stack with nothing to put right is not a job that ended', function (): void {
    // The healthy case. Both show no repairs, and they are different sentences:
    // one says there is nothing to fix, the other says ask me again.
    $nothing = theRepairsScreen(AStackThatWouldMend::offering(Offer::of('named', Repairs::none())));
    $ended = theRepairsScreen(AStackThatWouldMend::thatForgotTheJob());

    expect($nothing->howMany())->toBe(0)
        ->and($nothing->hasEnded())->toBeFalse()
        ->and($ended->howMany())->toBe(0)
        ->and($ended->hasEnded())->toBeTrue();
});

it('N1-R10 — a stack that could not be asked says so, and says what to do', function (): void {
    $screen = theRepairsScreen(AStackThatWouldMend::met(Obstacle::StackDidNotAnswer));

    expect($screen->met())->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($screen->remedy())->toBe(Obstacle::StackDidNotAnswer->remedy())
        ->and($screen->howMany())->toBe(0)
        ->and($screen->isWorkingItOut())->toBeFalse()
        ->and($screen->hasEnded())->toBeFalse();
});

it('a stack that took the question on and then went away is an obstacle too', function (): void {
    // The awkward middle: a phone leaves the house between asking and reading.
    // The screen has a state for it rather than holding a handle it can never
    // resolve — and it is the obstacle, because that is what the operator met.
    $mending = AStackThatWouldMend::thatWentAwayAfterwards(Obstacle::StackDidNotAnswer);
    $screen = theRepairsScreen($mending);

    expect($screen->met())->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($mending->askings())->toBe(1)
        ->and($mending->readings())->toBe(1);
});

it('N1-R44 — a device with no session for that stack is not asked to reach it', function (): void {
    $mending = AStackThatWouldMend::offering(aListingWorthReading());
    $screen = theRepairsScreen($mending, signedIn: false);

    expect($screen->isSignedIn())->toBeFalse()
        ->and($screen->howMany())->toBe(0)
        // Never asked. A screen that reached out and then noticed it had no
        // session would have started work on a machine with nothing behind it.
        ->and($mending->askings())->toBe(0)
        ->and($mending->readings())->toBe(0);
});

it('N1-R11 — a route naming a stack this device has forgotten is refused', function (): void {
    $screen = theRepairsScreen(
        AStackThatWouldMend::offering(aListingWorthReading()),
        named: str_repeat('z', Nonce::SHORTEST),
    );

    expect(fn(): int => $screen->howMany())->toThrow(StackIsNotConfigured::class);
});

it('N2-R4 — the screen is registered under the route that reaches it', function (): void {
    $resolved = NativeRouter::resolve(sprintf(
        '/stacks/%s/repairs',
        theStackBeingOfferedRepairs()->id()->stored(),
    ));

    expect($resolved)->not->toBeNull(
        'Nothing is registered for the repairs route, so the button on the stack screen leads nowhere.',
    );

    expect($resolved['class'] ?? null)->toBe(WhatWouldBePutRight::class);
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theRepairsScreen(AStackThatWouldMend::offering(aListingWorthReading()));

    expect(NativeRouter::resolve($screen->healthIsAt()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->signInAt()))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theRepairsScreen(AStackThatWouldMend::offering(aListingWorthReading()));

    expect($screen->render()->name())->toBe('operator::what-would-be-put-right');
});
