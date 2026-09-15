<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\LeftBehind;
use Modules\Kernel\Api\Mended;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Repairs;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsNotConfigured;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Undoing;
use Modules\Kernel\Api\WhatBecameOfIt;
use Modules\Kernel\Api\WhatWasMended;
use Modules\Operator\Internal\AStacksScreen;
use Modules\Operator\Internal\Screens\WhatWouldBePutRight;
use Native\Mobile\Attributes\Poll;
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
            Check::of('storage.one-filesystem'),
            'Move the library onto the larger disk',
            Effects::of('Downloads pause while it moves'),
            Undoing::Possible,
        ),
        Repair::offered(
            Check::of('credentials.expired'),
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
    ?AKeychainInMemory $keychain = null,
): WhatWouldBePutRight {
    $stack = theStackBeingOfferedRepairs();
    $keychain ??= AKeychainInMemory::working();

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
    $rows = theRepairsScreen(AStackThatWouldMend::offering(aListingWorthReading()))->offer()->repairs;

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
    $rows = theRepairsScreen(AStackThatWouldMend::offering(aListingWorthReading()))->offer()->repairs;

    expect($rows[0]->undoing)->not->toBe($rows[1]->undoing);
});

it('N2-R7 — asking costs a round trip, then a read of the handle', function (): void {
    // The unconfirmed form is still a job. One of each per frame, and never two
    // askings — a second would be a second piece of work on somebody's machine
    // for a question already asked.
    $mending = AStackThatWouldMend::offering(aListingWorthReading());
    $screen = theRepairsScreen($mending);

    $screen->offer();
    count($screen->offer()->repairs);
    $screen->offer();

    expect($mending->askings())->toBe(1)
        ->and($mending->readings())->toBe(1)
        ->and($mending->askedAfter()?->shown())->toBe(AStackThatWouldMend::THE_JOB);
});

it('N2-R7 — work still going is a state of the screen, not a spinner', function (): void {
    $screen = theRepairsScreen(AStackThatWouldMend::stillWorkingItOut());

    expect($screen->offer()->isWorking)->toBeTrue()
        ->and($screen->offer()->hasEnded)->toBeFalse()
        ->and(count($screen->offer()->repairs))->toBe(0)
        ->and($screen->offer()->met)->toBe('');
});

it('N1-R17 — asking again while the work runs reads the same job, and starts none', function (): void {
    // The distinction that makes this port safe to hold a handle for: the work
    // is the stack's, and repeating the read changes nothing. Starting a second
    // job for one question would be two lots of work on a machine.
    $mending = AStackThatWouldMend::stillWorkingItOut();
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $screen->again();
    $screen->offer();

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

    count($screen->offer()->repairs);
    $screen->again();
    count($screen->offer()->repairs);

    expect($mending->askings())->toBe(2)
        ->and($mending->readings())->toBe(2);
});

it('a job the stack forgot is its own state, and asking again starts a new one', function (): void {
    // Not a fault and not an answer. There is nothing left to read, so this is
    // the one case where asking again has to be a fresh asking — a screen that
    // re-read the dead handle would say *expired* forever.
    $mending = AStackThatWouldMend::thatForgotTheJob();
    $screen = theRepairsScreen($mending);

    expect($screen->offer()->hasEnded)->toBeTrue()
        ->and($screen->offer()->isWorking)->toBeFalse()
        ->and($screen->offer()->met)->toBe('');

    $screen->again();
    $screen->offer();

    expect($mending->askings())->toBe(2);
});

it('a stack with nothing to put right is not a job that ended', function (): void {
    // The healthy case. Both show no repairs, and they are different sentences:
    // one says there is nothing to fix, the other says ask me again.
    $nothing = theRepairsScreen(AStackThatWouldMend::offering(Offer::of('named', Repairs::none())));
    $ended = theRepairsScreen(AStackThatWouldMend::thatForgotTheJob());

    expect(count($nothing->offer()->repairs))->toBe(0)
        ->and($nothing->offer()->hasEnded)->toBeFalse()
        ->and(count($ended->offer()->repairs))->toBe(0)
        ->and($ended->offer()->hasEnded)->toBeTrue();
});

it('N1-R10 — a stack that could not be asked says so, and says what to do', function (): void {
    $screen = theRepairsScreen(AStackThatWouldMend::met(Obstacle::StackDidNotAnswer));

    expect($screen->offer()->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($screen->offer()->remedy)->toBe(Obstacle::StackDidNotAnswer->remedy())
        ->and(count($screen->offer()->repairs))->toBe(0)
        ->and($screen->offer()->isWorking)->toBeFalse()
        ->and($screen->offer()->hasEnded)->toBeFalse();
});

it('a stack that took the question on and then went away is an obstacle too', function (): void {
    // The awkward middle: a phone leaves the house between asking and reading.
    // The screen has a state for it rather than holding a handle it can never
    // resolve — and it is the obstacle, because that is what the operator met.
    $mending = AStackThatWouldMend::thatWentAwayAfterwards(Obstacle::StackDidNotAnswer);
    $screen = theRepairsScreen($mending);

    expect($screen->offer()->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($mending->askings())->toBe(1)
        ->and($mending->readings())->toBe(1);
});

it('N1-R44 — a device with no session for that stack is not asked to reach it', function (): void {
    $mending = AStackThatWouldMend::offering(aListingWorthReading());
    $screen = theRepairsScreen($mending, signedIn: false);

    expect($screen->isSignedIn())->toBeFalse()
        ->and(count($screen->offer()->repairs))->toBe(0)
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

    expect(fn(): int => count($screen->offer()->repairs))->toThrow(StackIsNotConfigured::class);
});

it('N2-R4 — the screen is registered under the route that reaches it', function (): void {
    $resolved = NativeRouter::resolve(
        AStacksScreen::Repairs->forTheStack(theStackBeingOfferedRepairs()->id()->stored()),
    );

    expect($resolved)->not->toBeNull(
        'Nothing is registered for the repairs route, so the button on the stack screen leads nowhere.',
    );

    expect($resolved['class'] ?? null)->toBe(WhatWouldBePutRight::class);
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theRepairsScreen(AStackThatWouldMend::offering(aListingWorthReading()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->signIn()))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theRepairsScreen(AStackThatWouldMend::offering(aListingWorthReading()));

    expect($screen->render()->name())->toBe('operator::what-would-be-put-right');
});

it('refuses a route parameter that is not text', function (): void {
    // A parameter arrives as `mixed`, because the navigation stack's own
    // parameter array is untyped. Anything that is not a string names no stack,
    // which is the same situation as a route with nothing in that segment.
    //
    // Asserted rather than assumed, because the narrowing is a branch and a
    // branch nothing drives is one that can quietly become the other. The empty
    // string it falls back to is what raises the refusal, so a fallback that
    // stopped being empty would hand `StackId` a name and open whatever machine
    // happened to answer to it. The three screens either side of this one make
    // the same assertion about the same shape.
    $screen = theRepairsScreen(AStackThatWouldMend::offering(aListingWorthReading()));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('N1-R17 — asking again before the first frame has read anything asks once', function (): void {
    // *Ask again* is an action, and an action can arrive before any accessor
    // has run — a frame that has been built and not yet resolved is a real
    // state, not a hypothetical one. Nothing has been read, so there is no
    // handle to decide about, and the screen has to survive being asked that
    // question anyway.
    //
    // The count is the assertion rather than the absence of an error: a screen
    // that treated the empty state as work in progress would keep a handle it
    // never had, and the read after it would be the second asking rather than
    // the first.
    $mending = AStackThatWouldMend::offering(aListingWorthReading());
    $screen = theRepairsScreen($mending);

    $screen->again();
    $screen->offer();

    expect($mending->askings())->toBe(1)
        ->and($mending->readings())->toBe(1);
});

/** A run in which the first repair took and the second stopped part-way. */
function aRunThatHalfWorked(): WhatWasMended
{
    $moved = Repair::offered(
        Check::of('storage.one-filesystem'),
        'Move the library onto the larger disk',
        Effects::of('Downloads pause while it moves'),
        Undoing::Possible,
    );

    $forgot = Repair::offered(
        Check::of('credentials.expired'),
        'Forget the expired credential',
        Effects::nothingElse(),
        Undoing::Permanent,
    );

    return WhatWasMended::of(
        Mended::went($moved, WhatBecameOfIt::Fixed),
        Mended::stopped($forgot, LeftBehind::of('Half of the library on the old disk')),
    );
}

it('N2-R5 — agreeing is a second act, and the screen stops showing the offer', function (): void {
    // The offer and the outcome are different questions with different answers,
    // and the screen must not answer one with the other. A template reading a
    // single value would render a listing of what a machine *would* do as a
    // record of what it *did*.
    $mending = AStackThatWouldMend::carryingOut(aListingWorthReading(), aRunThatHalfWorked());
    $screen = theRepairsScreen($mending);

    expect($screen->wasAgreedTo())->toBeFalse();

    $screen->offer();
    $screen->agreeTo('storage.one-filesystem');

    expect($screen->wasAgreedTo())->toBeTrue()
        ->and($mending->agreements())->toBe(1);
});

it('N2-R6 — the yes quotes the listing the operator was shown', function (): void {
    $mending = AStackThatWouldMend::carryingOut(aListingWorthReading(), aRunThatHalfWorked());
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $screen->agreeTo('storage.one-filesystem');

    expect($mending->agreedTo()?->quoting())->toBe(aListingWorthReading()->named())
        ->and($mending->agreedTo()?->repair()->answers()->shown())->toBe('storage.one-filesystem');
});

it('N2-R5 — a repair named by a check that is not on offer agrees to nothing', function (): void {
    // Named by check rather than by position, so a listing that came back in
    // another order between the render and the tap cannot agree to a different
    // repair. A check that is not there at all is the same protection working.
    $mending = AStackThatWouldMend::carryingOut(aListingWorthReading(), aRunThatHalfWorked());
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $screen->agreeTo('something.else-entirely');

    expect($screen->wasAgreedTo())->toBeFalse()
        ->and($mending->agreements())->toBe(0);
});

it('N2-R5 — a name that is blank agrees to nothing rather than raising', function (): void {
    // A template can send anything, and `Check::of()` raises on a blank —
    // rightly, a check named as nothing is no check at all — so building the
    // value before knowing there is a row would put that raise on a tap.
    // Whitespace as well as empty, because the two reach `Check::of()` by
    // different routes and only one of them is a length check away.
    $mending = AStackThatWouldMend::carryingOut(aListingWorthReading(), aRunThatHalfWorked());
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $screen->agreeTo('');
    $screen->agreeTo('   ');

    expect($screen->wasAgreedTo())->toBeFalse()
        ->and($mending->agreements())->toBe(0);
});

it('N2-R5 — agreeing before a listing has been read agrees to nothing', function (): void {
    // There is nothing to quote, so there is nothing to agree to. Silent rather
    // than refusing: the template does not draw the button in that state, and a
    // sentence about a button nobody can see is noise.
    $mending = AStackThatWouldMend::carryingOut(aListingWorthReading(), aRunThatHalfWorked());
    $screen = theRepairsScreen($mending);

    $screen->agreeTo('storage.one-filesystem');

    expect($screen->wasAgreedTo())->toBeFalse()
        ->and($mending->agreements())->toBe(0);
});

it('N2-R5 — a finished run says what became of each repair, and what one left', function (): void {
    // Per repair, because a listing agreed to as a whole comes apart. Reported
    // as one word, the operator believes either that everything worked or that
    // nothing did — and there is half a library on the old disk either way.
    $mending = AStackThatWouldMend::carryingOut(aListingWorthReading(), aRunThatHalfWorked());
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $screen->agreeTo('storage.one-filesystem');
    $done = $screen->done();

    expect($done->changed)->toBe(1)
        ->and($done->outcomes[0]->became)->toBe(WhatBecameOfIt::Fixed->saidOnTheScreen())
        ->and($done->outcomes[0]->left)->toBe('')
        ->and($done->outcomes[0]->worthAnotherGo)->toBeFalse()
        ->and($done->outcomes[1]->became)->toBe(WhatBecameOfIt::Stopped->saidOnTheScreen())
        ->and($done->outcomes[1]->left)->toBe('Half of the library on the old disk')
        ->and($done->outcomes[1]->worthAnotherGo)->toBeTrue();
});

it('N2-R4 — an outcome still carries what the repair said it would do', function (): void {
    // Not only the sentence. *Can this be undone* is exactly the question an
    // operator has once a repair has worked, and what else it affected is what
    // explains the half hour they just had.
    $mending = AStackThatWouldMend::carryingOut(aListingWorthReading(), aRunThatHalfWorked());
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $screen->agreeTo('storage.one-filesystem');
    $first = $screen->done()->outcomes[0];

    expect($first->repair->does)->toBe('Move the library onto the larger disk')
        ->and($first->repair->effects)->toBe(['Downloads pause while it moves'])
        ->and($first->repair->undoing)->toBe(Undoing::Possible->saidOnTheScreen());
});

it('N1-R41 — asking again after agreeing reads, and never agrees twice', function (): void {
    // An agreement sent twice is a repair carried out twice, which for a fix
    // that moves a library is not the same as doing it once.
    $mending = AStackThatWouldMend::carryingOut(aListingWorthReading(), aRunThatHalfWorked());
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $screen->agreeTo('storage.one-filesystem');
    $screen->done();

    // Measured across the second asking rather than counted from zero: the
    // offer's own read is in the total too, and a test that hard-codes the sum
    // is one that has to be edited every time the frame reads anything else.
    $before = $mending->readings();

    $screen->again();
    $screen->done();

    expect($mending->readings() - $before)->toBe(1)
        ->and($mending->agreements())->toBe(1);
});

it('a job that ended after an agreement is not a run that failed', function (): void {
    // The operator does not know what happened to their machine, and *it
    // failed* is the one answer that is certainly wrong — it may well have
    // worked. So the screen says nobody knows and sends them to look.
    $mending = AStackThatWouldMend::thatForgotTheJob();
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $screen->agreeTo('storage.one-filesystem');

    expect($screen->wasAgreedTo())->toBeFalse();
});

it('nothing was agreed to, so there is nothing that was done', function (): void {
    // Not *still working it out*: a screen answering that for a run nobody
    // started would be inventing one. The same answer as a job the stack has
    // forgotten, because both mean there is no run to describe — and the
    // template branches on `wasAgreedTo()` rather than on this.
    $screen = theRepairsScreen(AStackThatWouldMend::offering(aListingWorthReading()));

    expect($screen->done()->hasEnded)->toBeTrue()
        ->and($screen->done()->isWorking)->toBeFalse()
        ->and($screen->done()->outcomes)->toBe([]);
});

it('N1-R10 — a stack that goes away between agreeing and reading says what was met', function (): void {
    // Time passes between the two, which is exactly where a phone leaves the
    // house or a session ends underneath somebody. Reaching it needs a stack
    // that offers a listing and *then* cannot be reached — one that met the
    // obstacle on both halves could never get as far as agreeing.
    $screen = theRepairsScreen(
        AStackThatWouldMend::goneAfterAgreeing(aListingWorthReading(), Obstacle::StackDidNotAnswer),
    );

    $screen->offer();
    $screen->agreeTo('storage.one-filesystem');

    expect($screen->done()->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($screen->done()->remedy)->toBe(Obstacle::StackDidNotAnswer->remedy())
        ->and($screen->done()->outcomes)->toBe([]);
});

it('N2-R5 — a run still being carried out is its own state', function (): void {
    // The ordinary middle: the listing read, the operator agreed, the machine
    // working. Told apart from *nothing was agreed to*, which is the case
    // above, and from a run that finished having done nothing.
    $screen = theRepairsScreen(AStackThatWouldMend::carryingOutStill(aListingWorthReading()));

    $screen->offer();
    $screen->agreeTo('storage.one-filesystem');

    expect($screen->done()->isWorking)->toBeTrue()
        ->and($screen->done()->hasEnded)->toBeFalse()
        ->and($screen->done()->met)->toBe('');
});

it('N1-R17 — what was done is held, so reading it twice asks once', function (): void {
    // The same rule as the offer, one question along: a frame reads several
    // fields off the outcome and each read must not be a trip to the machine.
    $mending = AStackThatWouldMend::carryingOut(aListingWorthReading(), aRunThatHalfWorked());
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $screen->agreeTo('storage.one-filesystem');

    $before = $mending->readings();

    $screen->done();
    $screen->done();
    $screen->done();

    expect($mending->readings() - $before)->toBe(1);
});

it('N2-R5 — a second repair in the same listing can still be agreed to', function (): void {
    // A listing usually holds more than one, and agreeing to the first is not
    // agreeing to the rest. Without a way back, the operator who fixed the disk
    // is left looking at that one outcome with the credential still waiting
    // beside it — a screen enterable once per listing, which is not what a
    // listing is.
    $mending = AStackThatWouldMend::carryingOut(aListingWorthReading(), aRunThatHalfWorked());
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $screen->agreeTo('storage.one-filesystem');
    $screen->done();

    $screen->lookAgain();

    expect($screen->wasAgreedTo())->toBeFalse()
        ->and(count($screen->offer()->repairs))->toBe(2);

    $screen->agreeTo('credentials.expired');

    expect($screen->wasAgreedTo())->toBeTrue()
        ->and($mending->agreements())->toBe(2)
        ->and($mending->agreedTo()?->repair()->answers()->shown())->toBe('credentials.expired');
});

it('looking again asks the stack afresh rather than reusing the listing', function (): void {
    // The machine has just changed, so the repairs it would offer now are not
    // necessarily the ones it offered before. Keeping the old listing would
    // have somebody agree to a fix for something already put right.
    $mending = AStackThatWouldMend::carryingOut(aListingWorthReading(), aRunThatHalfWorked());
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $screen->agreeTo('storage.one-filesystem');
    $screen->done();

    $before = $mending->askings();
    $screen->lookAgain();
    $screen->offer();

    expect($mending->askings() - $before)->toBe(1);
});

it('N1-R27 — while the stack is working it out, the screen looks again by itself', function (): void {
    // The half of `N1-R27` that is not a button. An operator who told a machine
    // to do something should not have to keep tapping to find out whether it
    // did, and leaving and returning is what the rule refuses by name.
    $mending = AStackThatWouldMend::stillWorkingItOut();
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $before = $mending->readings();
    $screen->whileItRuns();
    $screen->offer();

    // A reading rather than an asking: the handle is kept while the work runs,
    // so looking again reads the job the stack already took on rather than
    // asking it what it would put right a second time.
    expect($mending->readings() - $before)->toBe(1)
        ->and($screen->isWorking())->toBeTrue();
});

it('N1-R17 — the cadence costs nothing while there is nothing to wait for', function (): void {
    // What keeps this from being the polling `N1-R17` refuses. A screen showing
    // an offer has nothing that changes on its own, so the poll does not reach
    // the machine at all.
    $mending = AStackThatWouldMend::offering(aListingWorthReading());
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $before = $mending->askings() + $mending->readings();
    $screen->whileItRuns();
    $screen->offer();

    expect($mending->askings() + $mending->readings() - $before)->toBe(0)
        ->and($screen->isWorking())->toBeFalse();
});

it('N1-R27 — the cadence the screen states is the one the attribute keeps', function (): void {
    // The two halves come off one constant, so there is no arrangement in which
    // the sentence says five seconds and the poll fires at two. Asserted
    // against the attribute itself rather than against a number written here.
    $screen = theRepairsScreen(AStackThatWouldMend::stillWorkingItOut());

    // Through the class rather than `new ReflectionMethod(...)`, whose
    // constructor throws a checked exception a Pest body may not — the reason
    // `EveryWireValueIsACaseTest` reads its enums off class constants.
    $polls = [];

    foreach (new ReflectionClass($screen)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->getName() === 'whileItRuns') {
            $polls = $method->getAttributes(Poll::class);
        }
    }

    expect($polls)->toHaveCount(1)
        ->and($polls[0]->newInstance()->ms)->toBe(HowOften::WhileWorkRuns->milliseconds())
        ->and($screen->cadence()->seconds() * 1_000)->toBe($polls[0]->newInstance()->ms)
        ->and($screen->cadence())->toBe(HowOften::WhileWorkRuns);
});

it('N3-R13 — a refused credential on the outcome read signs this device out', function (): void {
    // The case the offer read cannot cover. A screen that read an offer
    // successfully and then met a refusal while asking what became of the work
    // would, for one frame, render what it loaded a moment ago under a session
    // the stack has stopped recognising — which is the half of `N3-R13` that is
    // about rendering rather than about storage.
    $keychain = AKeychainInMemory::working();
    $mending = AStackThatWouldMend::goneAfterAgreeing(
        aListingWorthReading(),
        Obstacle::CredentialWasRefused,
    );
    $screen = theRepairsScreen($mending, keychain: $keychain);

    $screen->offer();
    $screen->agreeTo('storage.one-filesystem');

    expect($screen->isSignedIn())->toBeFalse()
        // And the storage half on this road too. The offer read has its own
        // case below; this is the one where the refusal arrives after a session
        // has already been used successfully, and the effect is not a property
        // of which read met it.
        ->and($keychain->isHolding(theStackBeingOfferedRepairs()->id()))->toBeFalse();
});

it('N3-R13 — an outcome that came back is not a session that ended', function (): void {
    // The other side of the line `isSignedIn()` draws. Once something has been
    // agreed to, the answer comes from the outcome read rather than the offer
    // read — so a screen that folded the two together with *and* would report a
    // signed-out device the moment anybody agreed to anything, which is every
    // successful repair this screen exists to carry out.
    $mending = AStackThatWouldMend::carryingOut(aListingWorthReading(), aRunThatHalfWorked());
    $screen = theRepairsScreen($mending);

    $screen->offer();
    $screen->agreeTo('storage.one-filesystem');

    expect($screen->wasAgreedTo())->toBeTrue()
        ->and($screen->isSignedIn())->toBeTrue();
});

it('N3-R13 — and the session is let go of, handle and all', function (): void {
    // This screen is the one that holds a job between frames, and a handle is
    // only redeemable with the session it was taken out under — keeping it
    // would have the next frame ask about work on behalf of somebody the stack
    // has stopped recognising.
    $keychain = AKeychainInMemory::working();
    $mending = AStackThatWouldMend::met(Obstacle::CredentialWasRefused);
    $screen = theRepairsScreen($mending, keychain: $keychain);

    expect($keychain->isHolding(theStackBeingOfferedRepairs()->id()))->toBeTrue();

    $screen->offer();

    expect($keychain->isHolding(theStackBeingOfferedRepairs()->id()))->toBeFalse()
        ->and($screen->isSignedIn())->toBeFalse();
});
