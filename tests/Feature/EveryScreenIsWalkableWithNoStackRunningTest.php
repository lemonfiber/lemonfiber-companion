<?php

declare(strict_types=1);

use Modules\Dx\Api\AStandInStack;
use Modules\Dx\Providers\DxServiceProvider;
use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\AStacksScreen;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhereAScreenCanSendYou;

// Q-R72 — the whole application, drawn, with no stack running anywhere.
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
// working one is reachable is a build where `N1-R10`'s screens — the ones an
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
        if ($case->forTheStack($stack) === $goes) {
            return $case->name;
        }
    }

    throw new RuntimeException('The sign-in prompt points somewhere the router serves no screen.');
}

/** The stand-ins, put over the ports, the way the switch does it. */
function withNoStackRunning(): void
{
    config(['dx.stands_in' => true]);
    app()->register(new DxServiceProvider(app()), force: true);
}

/**
 * The sentence a screen draws when this device is signed out of a stack.
 *
 * Read off the catalogue rather than written here, so a rule about what a
 * screen says cannot disagree with what the screen says.
 */
function theSignedOutSentence(): string
{
    return __('connection.session_has_ended');
}

it('Q-R72 — a machine that answers draws every screen it is behind', function (): void {
    withNoStackRunning();

    $thin = [];

    foreach (whatEachScreenDrewOf(AStandInStack::Answering) as $name => $drawn) {
        // The signed-out sentence is the tell, and it is a better one than a
        // count: a screen drawing the obstacle or the sign-in prompt draws a
        // handful of nodes, and so does a reading that came back nearly empty.
        // What must not happen is the app deciding it cannot ask.
        if (in_array(theSignedOutSentence(), $drawn->said(), strict: true)) {
            $thin[] = sprintf('%s — drew the signed-out prompt against the machine that answers', $name);
        }
    }

    sort($thin);

    expect($thin)->toBe([], sprintf(
        "These could not be drawn against a machine that answers everything:\n  %s\n\n"
        . 'With stand-ins on the device is paired and signed in, so every one of these is a '
        . "reading. A signed-out prompt here means the app never got as far as asking.\n"
        . 'That is what `modules/dx` exists to make possible: the whole application, on a '
        . 'device, with no stack running anywhere (Q-R72, N1-R57).',
        implode("\n  ", $thin),
    ));
});

it('finds screens to draw', function (): void {
    // The floor `Q-R66` asks for. The rules above walk what the router serves,
    // and a walk over nothing passes three times over.
    withNoStackRunning();

    expect(whatEachScreenDrewOf(AStandInStack::Answering))->not->toBe([]);
});

it('N1-R10 — a machine that does not answer draws what stood in the way, and the way back', function (): void {
    withNoStackRunning();

    $quiet = [];

    $wayBackIn = theScreenASignedOutOperatorIsSentTo(AStandInStack::NotAnswering->asAStack()->id()->stored());

    foreach (whatEachScreenDrewOf(AStandInStack::NotAnswering) as $name => $drawn) {
        if ($name === $wayBackIn) {
            continue;
        }

        $met = in_array(__(Obstacle::StackDidNotAnswer->said()), $drawn->said(), strict: true);

        // `N1-R3`: an obstacle never takes the action away. A screen that
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
