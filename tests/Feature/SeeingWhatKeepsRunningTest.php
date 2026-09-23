<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Unattended;
use Modules\Kernel\Api\WhatKeepsItRunning;
use Modules\Kernel\Api\WhatRunsUnattended;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatKeepsRunningHere;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatHosts;
use Tests\Support\Fakes\StacksInMemory;

// What this machine keeps running when nobody is signed in.
//
// The question about the hours nobody was looking. Everything else this app
// shows about a stack is true while somebody has it open; this is the one an
// operator asks the morning after a reboot.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application — `view()` and `__()` are not there in a
// module suite.

/** The machine whose unattended commands this screen is about. */
function theStackWhoseHostingIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A launch agent keeping two things, one of which is installed against nothing. */
function aMachineThatKeepsTwoThings(): WhatRunsUnattended
{
    return WhatRunsUnattended::keptBy(
        WhatKeepsItRunning::Launchd,
        Unattended::called('Watching the library', 'lemonfiber watch --all', 'New files are noticed', HowItIsHosted::Hosted),
        Unattended::orphaned('Seeding what you share', 'lemonfiber seed', 'Torrents keep seeding', '/usr/local/bin/lemonfiber'),
    );
}

/**
 * The screen, with a stack it knows and a keychain holding whatever a test says.
 *
 * Named for this file, since the root suites share one namespace (`G10`).
 */
function theHostingScreen(
    AStackThatHosts $hosting,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatKeepsRunningHere {
    $stack = theStackWhoseHostingIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatKeepsRunningHere($hosting, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('N16-R5 — shows every command, what it guarantees, and where it stands', function (): void {
    $screen = theHostingScreen(AStackThatHosts::with(aMachineThatKeepsTwoThings()));

    expect($screen->howMany())->toBe(2)
        // A machine that answered is not a session that ended. `isSignedIn` is
        // the template's first branch, so a fold reporting otherwise would put
        // the sign-in prompt in front of an operator whose session is working.
        ->and($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->keptBySaid)->toBe(WhatKeepsItRunning::Launchd->saidOnTheScreen());

    $rows = $screen->answer()->commands;

    expect($rows[0]->name)->toBe('Watching the library')
        ->and($rows[0]->command)->toBe('lemonfiber watch --all')
        ->and($rows[0]->guarantees)->toBe('New files are noticed')
        ->and($rows[0]->standingSaid)->toBe(HowItIsHosted::Hosted->saidOnTheScreen())
        ->and($rows[1]->standingSaid)->toBe(HowItIsHosted::Orphaned->saidOnTheScreen());
});

it('N16-R5 — a machine that keeps things running says nothing about doing it by hand', function (): void {
    // The empty string is what the template branches on. A sentence here would
    // tell an operator to go and set up by hand what the machine already does.
    expect(theHostingScreen(AStackThatHosts::with(aMachineThatKeepsTwoThings()))->answer()->instead)
        ->toBe('');
});

it('N16-R5 — a machine this product cannot configure says what to do instead', function (): void {
    // *Not available here* rather than *off*, and the sentence is what makes
    // the difference readable. Drawn instead of a control: there is nothing on
    // this platform to switch.
    $running = WhatRunsUnattended::unsupported(
        'Add it to your own login items.',
        Unattended::called('Watching the library', 'lemonfiber watch', 'New files are noticed', HowItIsHosted::Unsupported),
    );

    $answer = theHostingScreen(AStackThatHosts::with($running))->answer();

    expect($answer->instead)->toBe('Add it to your own login items.')
        ->and($answer->keptBySaid)->toBe(WhatKeepsItRunning::Unsupported->saidOnTheScreen())
        // And it is not an obstacle. A platform that configures nothing is a
        // fact about the machine, not a failure to reach it — an operator sent
        // to check their network about a laptop that was never going to run a
        // launch agent has been sent to fix the wrong thing.
        ->and($answer->went->cameBack())->toBeTrue();
});

it('N16-R6 — counts what did not come back, and an orphan is in it', function (): void {
    // Two rows, one of them orphaned: installed against a program that is gone,
    // so it cannot run. A count of one would be the reboot looking better than
    // it went.
    $answer = theHostingScreen(AStackThatHosts::with(aMachineThatKeepsTwoThings()))->answer();

    expect($answer->missing)->toBe(1)
        ->and($answer->commands[1]->didNotComeBack)->toBeTrue()
        ->and($answer->commands[0]->didNotComeBack)->toBeFalse();
});

it('N16-R6 — an orphan names the program that is gone, and nothing else does', function (): void {
    // A blank printed where a path belongs reads as *nothing is missing* on the
    // one row where something is, which is what the two arms exist to prevent.
    $rows = theHostingScreen(AStackThatHosts::with(aMachineThatKeepsTwoThings()))->answer()->commands;

    expect($rows[1]->missing)->toBe('/usr/local/bin/lemonfiber')
        ->and($rows[0]->missing)->toBe('');
});

it('a machine that keeps nothing running is an answer rather than a gap', function (): void {
    // It still says what keeps things running. Told apart from a machine that
    // could not be asked by having come back at all.
    $answer = theHostingScreen(
        AStackThatHosts::with(WhatRunsUnattended::keptBy(WhatKeepsItRunning::Systemd)),
    )->answer();

    expect($answer->commands)->toBe([])
        ->and($answer->missing)->toBe(0)
        ->and($answer->went->cameBack())->toBeTrue()
        ->and($answer->keptBySaid)->toBe(WhatKeepsItRunning::Systemd->saidOnTheScreen());
});

it('N1-R10 — a stack that could not be asked is not a machine hosting nothing', function (): void {
    // The collapse this surface exists to refuse. Both draw an empty list, and
    // only one of them means everything is fine.
    $answer = theHostingScreen(AStackThatHosts::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->commands)->toBe([])
        ->and($answer->keptBySaid)->toBe('');
});

it('N1-R10 — a device holding no session asks somebody to sign in', function (): void {
    // Nothing was met, because the app did not get as far as asking.
    $answer = theHostingScreen(
        AStackThatHosts::with(aMachineThatKeepsTwoThings()),
        signedIn: false,
    )->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->went->met)->toBe('')
        ->and($answer->commands)->toBe([]);
});

it('N1-R25 — asks once per frame, and asking again is what asks again', function (): void {
    // One read per frame. A home network with a machine that may be asleep is
    // the wrong thing to talk to four times a second.
    $stack = AStackThatHosts::with(aMachineThatKeepsTwoThings());
    $screen = theHostingScreen($stack);

    $screen->answer();
    $screen->answer();

    expect($stack->askings())->toBe(1)
        ->and($stack->wasGivenASession())->toBeTrue()
        ->and($stack->askedAbout()?->id()->stored())->toBe(theStackWhoseHostingIsRead()->id()->stored());

    $screen->again();
    $screen->answer();

    expect($stack->askings())->toBe(2);
});
