<?php

declare(strict_types=1);

use Modules\Connection\Api\HowTheSignInWent;
use Modules\Dx\Api\AStandInStack;
use Modules\Dx\Providers\DxServiceProvider;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\StackId;
use Modules\Operator\Internal\Screens\SignIntoAStack;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhereAScreenCanSendYou;

// Asserts on the routes the operator's and the household's providers declare,
// so their mutants are judged here: see `scripts/mutation.php`.
pest()->group(
    'holds:app-modules/operator/src/Providers/OperatorServiceProvider.php',
    'holds:app-modules/household/src/Providers/HouseholdServiceProvider.php',
);

// The whole application, drawn, with no stack running anywhere.
//
// That is what `modules/dx` is for, and until this rule existed nothing said
// whether it was true. It was not. Every adapter that opens a connection was
// handed `new PinnedClients()` by the composition root — a concrete final class
// named in seven constructors — so the stand-in bound at the kernel's port was
// resolved correctly and reached by nothing at all. With stand-ins on, the
// application dialled the addresses of machines that do not exist.
//
// Nothing could see it. Every dx test asserted the binding, which was right;
// every screen test built its own fake port, which was also right; and the one
// question neither asks is whether a screen drawn by this application reaches
// the stand-in. The answer arrives at a render, which is a phone.
//
// So this draws every stack-scoped screen against each of the three machines
// and asks what each drew. The three are the point: a build where only the
// working one is reachable is a build where the obstacle screens — the ones an
// operator meets on a bad evening — are never looked at.

/**
 * Every stack-scoped screen, drawn against one machine.
 *
 * Built from the container and given the route's own parameters, which is how
 * the application builds one. A screen the router serves nothing under is left
 * out here and named by `F12`, whose question it is.
 *
 * @return array<string, WhatTheDeviceWouldDraw>
 */
function whatEachScreenDrewOf(AStandInStack $machine): array
{
    $served = WhereAScreenCanSendYou::read()->screensTheRouterServes();
    $stack = $machine->asAStack()->id()->stored();
    $drawn = [];

    foreach (AStacksScreen::cases() as $case) {
        $class = $served[sprintf('AStacksScreen::%s', $case->name)] ?? '';
        $screen = $class === '' ? null : app()->make($class);

        if (! $screen instanceof NativeComponent) {
            continue;
        }

        $screen->setParams($case->alsoNeedsAService()
            ? ['stack' => $stack, 'service' => 'gluetun']
            : ['stack' => $stack]);

        $drawn[$case->name] = WhatTheDeviceWouldDraw::by($screen);
    }

    return $drawn;
}

/**
 * The screen a signed-out operator is sent to, which is not one of these rules'
 * subjects.
 *
 * Asking it to draw *you are signed out of this stack* would be asking the
 * destination of the prompt to display the prompt. It is the way back in, and
 * what it draws is a field for a password.
 *
 * Found by following the accessor every screen hands to the component rather
 * than by naming a case here: the day somebody sends a signed-out operator
 * somewhere else, this follows them.
 */
function theScreenASignedOutOperatorIsSentTo(string $stack): string
{
    $goes = WhereAStackIs::rememberedAs($stack)->signIn();

    foreach (AStacksScreen::cases() as $case) {
        if ($case->forTheStack(StackId::rememberedAs($stack)) === $goes) {
            return $case->name;
        }
    }

    throw new RuntimeException('The sign-in prompt points somewhere the router serves no screen.');
}

/**
 * Whether this device still holds a session for that machine.
 *
 * Asked of the port rather than of the stand-in that seeded it, so what is read
 * is what a screen would read — and a freshly built adapter proves the store
 * behind them is shared, which is what makes a session dropped on one frame
 * still gone on the next.
 */
function thisDeviceStillHoldsASessionFor(StackId $stack): bool
{
    // Narrowed by the container rather than by a check afterwards: the binding
    // is a class the analyser can see, so a check would be a branch nothing can
    // reach — and a rule with an arm nobody reaches is one nobody can tell from
    // a broken one.
    $storage = app()->make(SecureStorage::class);

    // `Resumed::either()` answers with an object either way, so the question
    // *is there one* is asked by which of the two arms ran. Neither arm reads
    // the session: what it holds is a credential, and a rule that took one out
    // to look at it would be the one place in this suite that did.
    return $storage->resume($stack)->either(
        static fn(): object => new stdClass(),
        static fn(): object => new RuntimeException(),
    ) instanceof stdClass;
}

/** The stand-ins, put over the ports, the way the switch does it. */
function withNoStackRunning(): void
{
    config(['dx.stands_in' => true]);
    app()->register(new DxServiceProvider(app()), force: true);
}

/**
 * The sentences that mean the app never got an answer.
 *
 * Read off the catalogue rather than written here, so a rule about what a
 * screen says cannot disagree with what the screen says.
 *
 * @return list<string>
 */
function whatNoAnswerLooksLike(): array
{
    return [
        theSignedOutSentence(),
        theExpiredSentence(),
        whateverTheCatalogueSays(Obstacle::StackDidNotAnswer->said()),
    ];
}

/**
 * The sentence a screen draws when the work it asked about is gone.
 *
 * `/api/jobs/<name>` is one path with two shapes behind it, and a stand-in that
 * answered with a name for the work rather than its outcome left the repairs
 * screen stuck on this against a machine answering everything.
 */
function theExpiredSentence(): string
{
    return whateverTheCatalogueSays('health.nothing_came_back');
}

/**
 * One line of the catalogue, narrowed.
 *
 * The translator answers a key with a sentence or with the array a key holding
 * several lines carries, and only the first is a thing a screen says. Narrowed
 * once here rather than cast at each call: a cast would turn the array into the
 * word *Array* and quietly compare a screen against it.
 */
function whateverTheCatalogueSays(string $key): string
{
    $said = __($key);

    return is_string($said)
        ? $said
        : throw new RuntimeException(sprintf('The catalogue answered %s with something that is not a sentence.', $key));
}

/**
 * The sentence a screen draws when this device is signed out of a stack.
 *
 * Read off the catalogue rather than written here, so a rule about what a
 * screen says cannot disagree with what the screen says.
 */
function theSignedOutSentence(): string
{
    return whateverTheCatalogueSays('connection.session_has_ended');
}

it('Q-R72 — a machine that answers draws every screen it is behind', function (): void {
    withNoStackRunning();

    $thin = [];

    foreach (whatEachScreenDrewOf(AStandInStack::Answering) as $name => $drawn) {
        // The three sentences that mean *no answer* are the tell, and they are
        // a better one than a count: a screen drawing an obstacle draws a
        // handful of nodes, and so does a reading that came back nearly empty.
        // What must not happen is the app saying it could not ask, or that what
        // it asked about is gone, against a machine answering everything.
        $silence = array_values(array_intersect(whatNoAnswerLooksLike(), $drawn->said()));

        if ($silence !== []) {
            $thin[] = sprintf('%s — drew "%s" against the machine that answers', $name, implode('", "', $silence));
        }
    }

    sort($thin);

    expect($thin)->toBe([], sprintf(
        "These could not be drawn against a machine that answers everything:\n  %s\n\n"
        . 'With stand-ins on the device is paired and signed in, so every one of these is a '
        . "reading that came back.\n"
        . 'That is what `modules/dx` exists to make possible: the whole application, on a '
        . 'device, with no stack running anywhere (Q-R72, N1-R57).',
        implode("\n  ", $thin),
    ));
});

it('finds screens to draw', function (): void {
    // The floor asked for. The rules above walk what the router serves,
    // and a walk over nothing passes three times over.
    withNoStackRunning();

    expect(whatEachScreenDrewOf(AStandInStack::Answering))->not->toBe([]);
});

/**
 * Screens that refuse before they can ask, and why each does.
 *
 * Not an exemption from the rule below. That one is about what a screen says
 * when it *met* a machine that would not answer, and a screen that never got
 * as far as asking has met nothing — it still draws an obstacle and still
 * leaves a way off, which every screen here is held to without exception.
 *
 * The list is here rather than absent because the alternative was worse in both
 * directions: widening the rule to accept any obstacle would let a screen that
 * genuinely met a dead machine report the wrong thing about it, and leaving the
 * screen out of the walk would stop it being drawn at all.
 *
 * **A stand-in run is the operator**, which `ASessionThisRunKeeps` says in as
 * many words: somebody looking at the whole of this application rather than a
 * member looking at their half. A shelf is read from the media server *as* an
 * account, and the operator is not one, so the reading is refused before a
 * request is made. Under a member's session this screen meets the machine like
 * any other.
 */
const NEVER_REACHES_THE_MACHINE = ['Shelf'];

it('N1-R10 — a machine that does not answer draws what stood in the way, and the way back', function (): void {
    withNoStackRunning();

    $quiet = [];

    $wayBackIn = theScreenASignedOutOperatorIsSentTo(AStandInStack::NotAnswering->asAStack()->id()->stored());

    foreach (whatEachScreenDrewOf(AStandInStack::NotAnswering) as $name => $drawn) {
        if ($name === $wayBackIn || in_array($name, NEVER_REACHES_THE_MACHINE, strict: true)) {
            continue;
        }

        $met = in_array(whateverTheCatalogueSays(Obstacle::StackDidNotAnswer->said()), $drawn->said(), strict: true);

        // An obstacle never takes the action away. A screen that
        // reported the failure and offered nothing leaves an operator whose
        // stack woke up two seconds later with no way to find out.
        if (! $met || $drawn->offers() === []) {
            $quiet[] = sprintf('%s — met=%s, offers=%d', $name, $met ? 'yes' : 'no', count($drawn->offers()));
        }
    }

    sort($quiet);

    expect($quiet)->toBe([], sprintf(
        "These met a machine that did not answer and said the wrong thing about it:\n  %s\n\n"
        . 'Both halves are the requirement: what stood in the way is a fact about the world '
        . "and the action left on the screen is the way back (N1-R10, N1-R3).\n"
        . 'A screen drawing neither is one an operator meets on the evening their stack is '
        . 'off, which is the evening this application is for.',
        implode("\n  ", $quiet),
    ));
});

it('N3-R13 — a machine that refuses the session draws the way back in', function (): void {
    withNoStackRunning();

    $refusing = AStandInStack::RefusingTheSession->asAStack()->id();

    // Asserted before the walk as well as after, and the order is the whole
    // proof: *no session* after a refusal means nothing unless there was one
    // to lose. A stand-in that seeded none would make the assertion below pass
    // without a refusal ever happening.
    expect(thisDeviceStillHoldsASessionFor($refusing))
        ->toBeTrue('the stand-in kept no session for the machine that refuses one, so nothing was refused');

    $wrong = [];

    $wayBackIn = theScreenASignedOutOperatorIsSentTo(AStandInStack::RefusingTheSession->asAStack()->id()->stored());

    foreach (whatEachScreenDrewOf(AStandInStack::RefusingTheSession) as $name => $drawn) {
        if ($name === $wayBackIn) {
            continue;
        }

        if (! in_array(theSignedOutSentence(), $drawn->said(), strict: true)) {
            $wrong[] = sprintf('%s — drew no signed-out prompt against the machine that refuses', $name);
        }
    }

    sort($wrong);

    // The other half of a removed identity, and the half a screen cannot show: a fold
    // that signs somebody out while the store keeps the session gives a screen
    // that flips between signed-out and signed-in as they navigate. The
    // stand-in keychain held one for this machine before the first frame, which
    // is what makes the question askable at all.
    expect(thisDeviceStillHoldsASessionFor($refusing))
        ->toBeFalse('the stack refused this session and the device is still holding it');

    expect($wrong)->toBe([], sprintf(
        "These met a refused session and went on showing something else:\n  %s\n\n"
        . '`N3-R13` says a refused credential is a signed-out app rather than a sentence '
        . 'about a machine, and that the app must not go on rendering what it had already '
        . "loaded.\n"
        . 'This is the sequence that needs a session to exist before it can happen at all — '
        . 'which is why the stand-in keychain holds one for the machine that refuses it.',
        implode("\n  ", $wrong),
    ));
});

/**
 * The sign-in screen, built the way the application builds one.
 *
 * Outside the closure because the container declares that it may fail to build
 * a screen and the analyser refuses a checked exception raised inside one —
 * left to raise rather than caught, because a screen the container cannot build
 * is this rule failing, and it fails loudest with the container's own message.
 */
function theSignInScreenFor(string $stack): SignIntoAStack
{
    $screen = app()->make(SignIntoAStack::class);
    $screen->setParams(['stack' => $stack]);

    return $screen;
}

it('N1-R7 — a password can be offered to a machine that is not running', function (): void {
    // The one act the walk above leaves out, because it is not a reading: every
    // other screen draws what a stack said, and this one offers the operator's
    // password and keeps what came back.
    //
    // It was also the last flow that reached the network. `Admissions` built
    // its own door inline — `Admission` is a transport of its own, with its own
    // pin and its own connector — so standing in at the client left the door
    // dialling the address of a machine that does not exist, with somebody's
    // password on it. `ADoorThatIsNotThere` is the other half, and this is what
    // says it is there.
    withNoStackRunning();

    $stack = AStandInStack::Answering->asAStack();
    $screen = theSignInScreenFor($stack->id()->stored());

    // Through the framework's own property sync, which is how a typed character
    // reaches a screen on a device. A test assigning the property directly
    // would be proving something about PHP.
    $screen->__syncProperty('typed', 'not-a-password');

    expect($screen->mayOffer())->toBeTrue('nothing was typed, so there is nothing to offer');

    $screen->offer();

    expect($screen->went())->toBe(
        HowTheSignInWent::SignedIn,
        'the stand-in door did not open, so the sign-in screen is the one frame of this '
        . 'application that cannot be walked without a stack running',
    );

    // The exchange trades the password once: what comes back is kept, so the next
    // frame does not ask again. Read through the port rather than off the
    // screen, because the screen's answer is what it just did and the store's
    // is what the app will find on the frame after this one.
    expect(thisDeviceStillHoldsASessionFor($stack->id()))->toBeTrue();

    // And the field is cleared whatever happened, which is a password off the
    // glass rather than one the next tap would offer again unchanged.
    expect($screen->typed())->toBe('');
});
