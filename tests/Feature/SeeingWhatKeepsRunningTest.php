<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HandingOver;
use Modules\Kernel\Api\HostingAgreed;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\HowTheHandoverWent;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheFilesTouched;
use Modules\Kernel\Api\Unattended;
use Modules\Kernel\Api\WhatKeepsItRunning;
use Modules\Kernel\Api\WhatRunsUnattended;
use Modules\Kernel\Api\WhatTheHandoverDid;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatKeepsRunningHere;
use Modules\Operator\Internal\ViewModels\WhatTheHandoverShows;
use Native\Mobile\Edge\NativeRouter;
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

    expect($screen->answer()->commands)->toHaveCount(2)
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
        // Which one, read off the standing the row carries rather than a
        // boolean beside it: the standing is what the screen draws, so a count
        // agreeing with a flag nothing shows would agree about nothing.
        ->and($answer->commands[1]->standingSaid)->toBe(HowItIsHosted::Orphaned->saidOnTheScreen())
        ->and($answer->commands[0]->standingSaid)->toBe(HowItIsHosted::Hosted->saidOnTheScreen());
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
        ->and($answer->keptBySaid)->toBe('')
        ->and($answer->instead)->toBe('')
        ->and($answer->missing)->toBe(0);
});

it('N1-R10 — a device holding no session asks somebody to sign in', function (): void {
    // Nothing was met, because the app did not get as far as asking.
    $answer = theHostingScreen(
        AStackThatHosts::with(aMachineThatKeepsTwoThings()),
        signedIn: false,
    )->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->went->met)->toBe('')
        ->and($answer->commands)->toBe([])
        ->and($answer->keptBySaid)->toBe('')
        ->and($answer->instead)->toBe('')
        ->and($answer->missing)->toBe(0);
});

it('N1-R3 — an obstacle that is not a refused credential leaves the session standing', function (): void {
    // A phone with no signal told to sign in is given advice for a problem it
    // does not have, over the one it does.
    $answer = theHostingScreen(AStackThatHosts::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->isSignedIn)->toBeTrue();
});

it('N3-R13 — a credential the stack refused signs this device out and lets the session go', function (): void {
    // Both halves: if the session stayed, the next frame would resume it, be
    // refused again, and draw a sign-in prompt over a device that still
    // believes it is signed in.
    $keychain = AKeychainInMemory::working();
    $screen = theHostingScreen(AStackThatHosts::met(Obstacle::CredentialWasRefused), $keychain);

    expect($keychain->isHolding(theStackWhoseHostingIsRead()->id()))->toBeTrue();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('')
        ->and($keychain->isHolding(theStackWhoseHostingIsRead()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, about the machine the route names', function (): void {
    // One read per frame. A home network with a machine that may be asleep is
    // the wrong thing to talk to four times a second — and the answers of a
    // screen that asked on every accessor would all agree, so the count is
    // what is read.
    $stack = AStackThatHosts::with(aMachineThatKeepsTwoThings());
    $screen = theHostingScreen($stack);

    $screen->answer();
    $screen->answer();

    expect($stack->askings())->toBe(1)
        ->and($stack->wasGivenASession())->toBeTrue()
        ->and($stack->askedAbout()?->id()->stored())->toBe(theStackWhoseHostingIsRead()->id()->stored());
});

it('N1-R3 — asking again asks the machine again', function (): void {
    $stack = AStackThatHosts::met(Obstacle::DeviceHasNoNetwork);
    $screen = theHostingScreen($stack);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($stack->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theHostingScreen(AStackThatHosts::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theHostingScreen(AStackThatHosts::with(aMachineThatKeepsTwoThings()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theHostingScreen(AStackThatHosts::with(aMachineThatKeepsTwoThings()));

    expect($screen->render()->name())->toBe('operator::what-keeps-running-here');
});

/** A machine with a manager keeping the guard and the boot start. */
function aMachineWithTheGuardAndTheBootStart(): WhatRunsUnattended
{
    return WhatRunsUnattended::keptBy(
        WhatKeepsItRunning::Systemd,
        Unattended::called('watch', 'lemonfiber watch', 'stops the stack if the data location disappears', HowItIsHosted::NotHosted),
        Unattended::called('boot', 'lemonfiber up --at-boot', 'brings the stack back after this machine restarts', HowItIsHosted::Hosted),
    );
}

/** An install that started the guard, as the stack would report it. */
function anInstallThatStartedTheGuard(bool $rehearsed = false): HowTheHandoverWent
{
    return HowTheHandoverWent::did(WhatTheHandoverDid::installing(
        name: 'watch',
        rehearsed: $rehearsed,
        started: ! $rehearsed,
        standing: HowItIsHosted::Hosted,
        output: '/home/me/.local/state/lemonfiber/hosted/watch.log',
        touched: TheFilesTouched::these('/home/me/.config/systemd/user/lemonfiber-watch.service'),
    ));
}

/** The screen with the question for one act answered yes, and what it sent. */
function theHostingScreenHavingAgreed(AStackThatHosts $stack, HandingOver $doing = HandingOver::Install): WhatKeepsRunningHere
{
    $screen = theHostingScreen($stack);

    $doing === HandingOver::Install ? $screen->wouldInstall('watch') : $screen->wouldRemove('watch');
    $screen->agree();

    return $screen;
}

it('offers keeping each command running, and taking it back, where the machine has a manager', function (): void {
    expect(theHostingScreen(AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart()))->answer()->handsOver)->toBeTrue();
});

it('offers neither on a machine this product cannot configure', function (): void {
    // The stack has said it cannot perform the act there. Offering it would be
    // offering what it has already refused.
    $running = WhatRunsUnattended::unsupported(
        'Add it to your own login items.',
        Unattended::called('watch', 'lemonfiber watch', 'stops the stack if the data location disappears', HowItIsHosted::Unsupported),
    );
    $stack = AStackThatHosts::with($running, anInstallThatStartedTheGuard());
    $screen = theHostingScreen($stack);

    $screen->wouldInstall('watch');
    $screen->agree();

    expect($screen->answer()->handsOver)->toBeFalse()
        ->and($screen->asking)->toBeNull()
        ->and($stack->told())->toBe([]);
});

it('offers neither where the machine could not be asked, or nobody is signed in', function (): void {
    expect(theHostingScreen(AStackThatHosts::met(Obstacle::StackDidNotAnswer))->answer()->handsOver)->toBeFalse()
        ->and(theHostingScreen(AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart()), signedIn: false)->answer()->handsOver)->toBeFalse();
});

it('asks before keeping a command running, and sends nothing until the yes', function (): void {
    $stack = AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart(), anInstallThatStartedTheGuard());
    $screen = theHostingScreen($stack);

    $screen->wouldInstall('watch');

    expect($screen->asking?->doing())->toBe(HandingOver::Install)
        ->and($screen->asking?->named())->toBe('watch')
        ->and($stack->told())->toBe([]);
});

it('asks before taking a command back, and sends nothing until the yes', function (): void {
    $stack = AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart());
    $screen = theHostingScreen($stack);

    $screen->wouldRemove('boot');

    expect($screen->asking?->doing())->toBe(HandingOver::Remove)
        ->and($screen->asking?->named())->toBe('boot')
        ->and($stack->told())->toBe([]);
});

it('does not ask about a command the listing does not carry', function (): void {
    // The name arrives from a template; what is asked about is built from what
    // was read.
    $screen = theHostingScreen(AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart()));

    $screen->wouldInstall('expiring');
    $screen->wouldRemove('');

    expect($screen->asking)->toBeNull();
});

it('putting the question away sends nothing', function (): void {
    $stack = AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart(), anInstallThatStartedTheGuard());
    $screen = theHostingScreen($stack);

    $screen->wouldInstall('watch');
    $screen->neverMind();
    $screen->agree();

    expect($screen->asking)->toBeNull()
        ->and($screen->handedOver)->toBeNull()
        ->and($stack->told())->toBe([]);
});

it('the yes sends what was asked about, once, and reads the machine again', function (): void {
    $stack = AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart(), anInstallThatStartedTheGuard());
    $screen = theHostingScreen($stack);

    $screen->answer();
    $screen->wouldInstall('watch');
    $screen->agree();
    $screen->agree();
    $screen->answer();

    expect(array_map(static fn(HostingAgreed $one): string => sprintf('%s %s', $one->doing()->value, $one->named()), $stack->told()))
        ->toBe(['install watch'])
        ->and($screen->asking)->toBeNull()
        ->and($stack->wasGivenASession())->toBeTrue()
        ->and($stack->askedAbout()?->id()->stored())->toBe(theStackWhoseHostingIsRead()->id()->stored())
        // The listing in front of the operator was about the machine before the act.
        ->and($stack->askings())->toBe(2);
});

it('an install is reported by where the command stands, whether it started, where its words go, and each file', function (): void {
    $shown = theHostingScreenHavingAgreed(
        AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart(), anInstallThatStartedTheGuard()),
    )->handedOver;

    expect($shown?->headingSaid)->toBe('stacks.handed_over.heading.install')
        ->and($shown?->name)->toBe('watch')
        ->and($shown?->rehearsed)->toBeFalse()
        ->and($shown?->standingSaid)->toBe(HowItIsHosted::Hosted->saidOnTheScreen())
        ->and($shown?->startedSaid)->toBe('stacks.handed_over.started')
        ->and($shown?->writesToSaid)->toBe('stacks.handed_over.writes_to')
        ->and($shown?->writesTo)->toBe('/home/me/.local/state/lemonfiber/hosted/watch.log')
        ->and($shown?->touched)->toBe(['/home/me/.config/systemd/user/lemonfiber-watch.service'])
        ->and($shown?->touchedSaid)->toBe('stacks.handed_over.touched.install')
        ->and($shown?->touchedNothingSaid)->toBe('stacks.handed_over.touched_nothing.install')
        ->and($shown?->refused)->toBe('')
        ->and($shown?->metSaid)->toBe('');
});

it('an install the stack did not start, and did not say where it writes, says both', function (): void {
    $went = HowTheHandoverWent::did(WhatTheHandoverDid::installingWithNowhereSaid(
        name: 'watch',
        rehearsed: false,
        started: false,
        standing: HowItIsHosted::InstalledUnverified,
        touched: TheFilesTouched::these(),
    ));
    $shown = theHostingScreenHavingAgreed(AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart(), $went))->handedOver;

    expect($shown?->startedSaid)->toBe('stacks.handed_over.not_started')
        ->and($shown?->standingSaid)->toBe(HowItIsHosted::InstalledUnverified->saidOnTheScreen())
        ->and($shown?->writesToSaid)->toBe('stacks.handed_over.writes_unsaid')
        ->and($shown?->writesTo)->toBe('')
        ->and($shown?->touched)->toBe([]);
});

it('a removal names what it took back, and says nothing about starting or writing', function (): void {
    $went = HowTheHandoverWent::did(WhatTheHandoverDid::removing(
        name: 'watch',
        rehearsed: false,
        standing: HowItIsHosted::NotHosted,
        touched: TheFilesTouched::these('/home/me/a.service'),
    ));
    $shown = theHostingScreenHavingAgreed(AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart(), $went), HandingOver::Remove)->handedOver;

    expect($shown?->headingSaid)->toBe('stacks.handed_over.heading.remove')
        ->and($shown?->startedSaid)->toBe('')
        ->and($shown?->writesToSaid)->toBe('')
        ->and($shown?->standingSaid)->toBe(HowItIsHosted::NotHosted->saidOnTheScreen())
        ->and($shown?->touched)->toBe(['/home/me/a.service'])
        ->and($shown?->touchedSaid)->toBe('stacks.handed_over.touched.remove')
        ->and($shown?->touchedNothingSaid)->toBe('stacks.handed_over.touched_nothing.remove');
});

it('a rehearsal is labelled as one, and its files are what would have happened', function (): void {
    $install = theHostingScreenHavingAgreed(
        AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart(), anInstallThatStartedTheGuard(rehearsed: true)),
    )->handedOver;
    $removal = theHostingScreenHavingAgreed(
        AStackThatHosts::with(
            aMachineWithTheGuardAndTheBootStart(),
            HowTheHandoverWent::did(WhatTheHandoverDid::removing(
                name: 'watch',
                rehearsed: true,
                standing: HowItIsHosted::Hosted,
                touched: TheFilesTouched::these('/home/me/a.service'),
            )),
        ),
        HandingOver::Remove,
    )->handedOver;

    expect($install?->rehearsed)->toBeTrue()
        ->and($install?->touchedSaid)->toBe('stacks.handed_over.would_touch.install')
        ->and($removal?->rehearsed)->toBeTrue()
        ->and($removal?->touchedSaid)->toBe('stacks.handed_over.would_touch.remove');
});

/**
 * Every field an act that did not happen should have left empty, where it did not.
 *
 * An act that never happened has no standing, no start, nowhere it writes, no
 * files and no rehearsal; a line drawn for any of them would report something
 * that did not occur.
 *
 * @return list<string>
 */
function whatAnActThatDidNotHappenStillSays(?WhatTheHandoverShows $shown): array
{
    $said = [
        'rehearsed' => $shown?->rehearsed === true ? 'yes' : '',
        'startedSaid' => $shown?->startedSaid,
        'standingSaid' => $shown?->standingSaid,
        'writesToSaid' => $shown?->writesToSaid,
        'writesTo' => $shown?->writesTo,
        'touched' => $shown?->touched === [] ? '' : 'files',
        'touchedSaid' => $shown?->touchedSaid,
        'touchedNothingSaid' => $shown?->touchedNothingSaid,
    ];
    $left = [];

    foreach ($said as $field => $value) {
        if ($value !== '') {
            $left[] = $field;
        }
    }

    return $left;
}

it('a refusal is shown in the stack\'s own words, as something that did not happen', function (): void {
    $went = HowTheHandoverWent::refused('The guard was not told what to guard');
    $shown = theHostingScreenHavingAgreed(AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart(), $went))->handedOver;

    expect($shown?->headingSaid)->toBe('stacks.handed_over.heading.did_not')
        ->and($shown?->name)->toBe('watch')
        ->and($shown?->refused)->toBe('The guard was not told what to guard')
        ->and($shown?->metSaid)->toBe('')
        ->and(whatAnActThatDidNotHappenStillSays($shown))->toBe([]);
});

it('an act that never got an answer says what was met, and a refused credential lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $stack = AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart(), HowTheHandoverWent::met(Obstacle::CredentialWasRefused));
    $screen = theHostingScreen($stack, $keychain);

    $screen->wouldInstall('watch');
    $screen->agree();

    expect($screen->handedOver?->headingSaid)->toBe('stacks.handed_over.heading.did_not')
        ->and($screen->handedOver?->metSaid)->toBe(Obstacle::CredentialWasRefused->said())
        ->and($screen->handedOver?->refused)->toBe('')
        ->and(whatAnActThatDidNotHappenStillSays($screen->handedOver))->toBe([])
        ->and($keychain->isHolding(theStackWhoseHostingIsRead()->id()))->toBeFalse();
});

it('an act on a device that no longer holds a session sends nothing and says it did not happen', function (): void {
    $keychain = AKeychainInMemory::working();
    $stack = AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart(), anInstallThatStartedTheGuard());
    $screen = theHostingScreen($stack, $keychain);

    $screen->wouldInstall('watch');
    $keychain->forget(theStackWhoseHostingIsRead()->id());
    $screen->agree();

    expect($stack->told())->toBe([])
        ->and($screen->handedOver?->headingSaid)->toBe('stacks.handed_over.heading.did_not')
        ->and($screen->handedOver?->refused)->toBe('')
        ->and($screen->handedOver?->metSaid)->toBe('')
        ->and(whatAnActThatDidNotHappenStillSays($screen->handedOver))->toBe([]);
});

it('asking about another act puts the last outcome away', function (): void {
    $screen = theHostingScreenHavingAgreed(
        AStackThatHosts::with(aMachineWithTheGuardAndTheBootStart(), anInstallThatStartedTheGuard()),
    );

    $screen->wouldRemove('boot');

    expect($screen->handedOver)->toBeNull();
});
