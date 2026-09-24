<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatTheDeviceWouldDraw;

// One thing this machine runs, and the verbs about it.
//
// The frame an operator reaches by tapping a row on the listing. It exists
// because the listing did not have room to be both: four services with their
// verbs drawn per row put fifteen controls on one screen, four of them called
// *Start it*, and which one a control acted on was carried by where it sat.
//
// A service and a form both arrive here, which is the two granularities
// meeting at one screen — the decision is the same and only the name the stack
// is told differs.

/** The machine the thing on this screen belongs to. */
function theMachineTheseVerbsReach(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('7', Nonce::SHORTEST))),
        StackName::of('The cupboard'),
        Address::of('https://192.168.1.44:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/**
 * The screen, about one named thing on a machine this device knows.
 *
 * Named for this file, since the root suites share one namespace (`G10`).
 */
function theThingScreen(
    AStackThatSupervises $supervising,
    string $named = 'sonarr',
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatToDoWithThis {
    $stack = theMachineTheseVerbsReach();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatToDoWithThis($supervising, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $named]);

    return $screen;
}

it('N2-R7 — the frame is about the one thing the route names', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));
    $thing = $screen->thing();

    expect($thing->named)->toBe('sonarr')
        ->and($thing->isAForm)->toBeFalse()
        ->and($thing->isRun())->toBeTrue()
        ->and($thing->service?->name)->toBe('Sonarr')
        // The details that belong to the one thing rather than to the list.
        // A row carrying all of them is a list nobody can scan.
        ->and($thing->service?->profile)->toBe('tv')
        ->and($thing->service?->leaning)->toBe(['jellyfin']);
});

it('N2-R7 — a thing this machine is not running is an answer, not a blank frame', function (): void {
    // A route can name anything: a list tapped a moment before the stack
    // changed, or a screen restored after a service left its form.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()), 'a-thing-nobody-listed');

    expect($screen->thing()->isRun())->toBeFalse()
        ->and($screen->thing()->verbs)->toBe([])
        ->and($screen->thing()->named)->toBe('a-thing-nobody-listed');
});

it('N2-R7 — a route naming nothing at all is the same answer', function (): void {
    // Blank rather than absent, which a navigation stack can produce and
    // `ServiceId::called()` would raise on. Nothing here is named nothing, so
    // it comes away as *no such thing* like any other name never read.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()), '   ');

    expect($screen->thing()->isRun())->toBeFalse()
        ->and($screen->thing()->named)->toBe('');
});

it('N2-R7 — only the verbs this one state can take', function (): void {
    $running = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));

    expect($running->thing()->verbs)->toBe([WhatToDoWithIt::Stop, WhatToDoWithIt::Restart]);

    $stopped = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Stopped, HowTheStackIsRunning::Partial)));

    expect($stopped->thing()->verbs)->toBe([WhatToDoWithIt::Start]);
});

it('N2-R7 — a service this stack does not run is offered no verb at all', function (): void {
    // The verbs are about what this stack runs. A verb about something the host
    // runs would be refused by the machine, and offering it teaches an operator
    // that the buttons here are a guess.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::HostManaged, HowTheStackIsRunning::Active)));

    expect($screen->thing()->isRun())->toBeTrue()
        ->and($screen->thing()->service?->isOurs)->toBeFalse()
        ->and($screen->thing()->verbs)->toBe([]);
});

it('N2-R7 — a whole form takes all three, because it has no state of its own', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()), 'library');

    expect($screen->thing()->isAForm)->toBeTrue()
        ->and($screen->thing()->service)->toBeNull()
        ->and($screen->thing()->verbs)->toBe(WhatToDoWithIt::cases());
});

it('N2-R7 — a whole form is agreed to as a form', function (): void {
    // The other granularity, and it must not arrive at the port
    // as a service: a form's name sent under `services` would stop nothing and
    // report that it had.
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theThingScreen($supervising, 'library');

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->agree();

    $told = $supervising->whatItWasToldToDo();

    expect($told)->toHaveCount(1)
        ->and($told[0]->isAboutAForm())->toBeTrue()
        ->and($told[0]->named())->toBe('library');
});

it('N18-R7 — a service\'s profile is not a form, and no verb is offered about it', function (): void {
    // `tv` is the profile the row names. The stack declares no form of that
    // name, so asking for it as one would be refused as a form it has never
    // heard of — and where the spellings happen to meet, it would reach a
    // different set of services from the one the row was about.
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theThingScreen($supervising, 'tv');

    expect($screen->thing()->isRun())->toBeFalse()
        ->and($screen->thing()->isAForm)->toBeFalse()
        ->and($screen->thing()->verbs)->toBe([]);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->agree();

    expect($supervising->whatItWasToldToDo())->toBe([]);
});

it('N18-R7 — a row names its profile as a profile', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())))->said();

    expect($drawn)->toContain(__('health.in_profile', ['profile' => 'tv']))
        ->and($drawn)->toContain('In the tv profile');
});

it('N2-R7 — a service wins over a form that shares its name', function (): void {
    // The narrower reading is the safer one: agreeing about one service and
    // being sent a whole form is the mistake that costs a household something.
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::oneThing('library', HowAServiceRuns::Running, HowTheStackIsRunning::Active));
    $screen = theThingScreen($supervising, 'library');

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->agree();

    expect($screen->thing()->isAForm)->toBeFalse()
        ->and($supervising->whatItWasToldToDo()[0]->isAboutAForm())->toBeFalse();
});

it('N2-R8 — a stop is asked about before anything is sent', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theThingScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);

    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->asking())->toBeInstanceOf(AgreedTo::class)
        ->and($screen->asking()?->doing())->toBe(WhatToDoWithIt::Stop)
        ->and($screen->asking()?->named())->toBe('sonarr');
});

it('N2-R8 — the yes sends what was stated and not what a tap carries', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theThingScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->agree();

    $told = $supervising->whatItWasToldToDo();

    expect($told)->toHaveCount(1)
        ->and($told[0]->doing())->toBe(WhatToDoWithIt::Stop)
        ->and($told[0]->named())->toBe('sonarr')
        ->and($told[0]->isAboutAForm())->toBeFalse()
        // And the question is put away, so a second yes cannot send it twice.
        ->and($screen->asking())->toBeNull();
});

it('N2-R8 — saying never mind sends nothing at all', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theThingScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->neverMind();
    $screen->agree();

    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->asking())->toBeNull();
});

it('N2-R8 — a start is not asked about, because it disturbs nothing', function (): void {
    // A screen that asked about a start would teach an operator to confirm
    // without reading, which is what makes the stop confirmation worth anything.
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Stopped, HowTheStackIsRunning::Partial));
    $screen = theThingScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);

    expect($screen->asking())->toBeNull()
        ->and($supervising->whatItWasToldToDo())->toHaveCount(1)
        ->and($supervising->whatItWasToldToDo()[0]->doing())->toBe(WhatToDoWithIt::Start);
});

it('N2-R8 — states what will not work while it is off', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);

    expect($screen->thing()->service?->leaning)->toBe(['jellyfin']);
});

it('N2-R8 — says a restart will not help where it is already looping', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::CrashLooping, HowTheStackIsRunning::Degraded)));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);

    expect($screen->thing()->service?->wouldNotHelp)->toBeTrue();
});

it('N2-R8 — the confirmation says how long the verb takes it away for', function (): void {
    // The number is the stack's: a length worked out here would be a guess at
    // something the stack knows, which is what is refused.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));
    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);

    expect($screen->whatItTakesAway()?->said)->toBe('health.for_at_most')
        ->and($screen->whatItTakesAway()?->seconds)->toBe(10);
});

it('N2-R8 — a stop and a restart are not held to the same clock', function (): void {
    // Two verbs, two numbers, read off the same listing. A screen that stated
    // one length for every verb would be stating a number that nothing honours
    // for every verb but one.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $stopping = $screen->whatItTakesAway()?->seconds;

    $screen->neverMind();
    $screen->wouldYouLike(WhatToDoWithIt::Restart->value);
    $restarting = $screen->whatItTakesAway()?->seconds;

    expect($stopping)->toBe(10)
        ->and($restarting)->toBe(180);
});

it('N2-R8 — nothing is stated where nothing is being asked', function (): void {
    // Absent because there is no question, not because the stack said nothing.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));

    expect($screen->asking())->toBeNull()
        ->and($screen->whatItTakesAway())->toBeNull();
});

it('N2-R8 — states no length once the reading it came from is gone', function (): void {
    // The bound is the listing's, so a reading that met an obstacle carries
    // none — and a sentence built from a number this app invented is exactly
    // what substitution refuses.
    $screen = theThingScreen(AStackThatSupervises::thenMeeting(
        WhatAMachineRuns::twoThings(),
        Obstacle::DeviceHasNoNetwork,
    ));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->again();

    expect($screen->asking())->toBeInstanceOf(AgreedTo::class)
        ->and($screen->whatItTakesAway())->toBeNull();
});

it('a verb this app does not have is not acted on', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theThingScreen($supervising);

    $screen->wouldYouLike('delete');

    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->asking())->toBeNull();
});

it('a route naming something this screen never read is not acted on', function (): void {
    // The half that makes the confirmation mean anything. The agreement is
    // built from the listing rather than from the route, so a URI naming a
    // service that is not on this machine reaches nothing.
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theThingScreen($supervising, 'a-service-nobody-listed');

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);

    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->asking())->toBeNull();
});

it('reads the machine again once a verb has been sent', function (): void {
    // The reading in front of the operator is about the machine as it was
    // before they said anything.
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Stopped, HowTheStackIsRunning::Partial));
    $screen = theThingScreen($supervising);

    $screen->answer();
    $screen->wouldYouLike(WhatToDoWithIt::Start->value);
    $screen->answer();

    // Read, told, read again — the verb is one of the three.
    expect($supervising->askings())->toBe(3);
});

it('N1-R27 — looks again only while something is settling', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Starting, HowTheStackIsRunning::Partial));
    $screen = theThingScreen($supervising);

    expect($screen->answer()->isSettling)->toBeTrue();

    $screen->whileItSettles();
    $screen->answer();

    expect($supervising->askings())->toBe(2)
        ->and($screen->cadence())->toBe(HowOften::WhileWorkRuns);
});

it('N1-R66 — a standing thing is not polled', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theThingScreen($supervising);

    $screen->answer();
    $screen->whileItSettles();
    $screen->whileItSettles();

    expect($supervising->askings())->toBe(1);
});

it('N1-R44 — a device with no session for it asks nothing', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theThingScreen($supervising, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->thing()->isRun())->toBeFalse()
        ->and($supervising->askings())->toBe(0);
});

it('N1-R10 — an obstacle is what stood in the way, with what to do about it', function (): void {
    $screen = theThingScreen(AStackThatSupervises::met(Obstacle::DeviceHasNoNetwork));
    $answer = $screen->answer();

    expect($answer->went->met)->toBe(Obstacle::DeviceHasNoNetwork->said())
        ->and($answer->went->remedy)->toBe(Obstacle::DeviceHasNoNetwork->remedy())
        ->and($answer->went->isSignedIn)->toBeTrue();
});

it('N1-R3 — asking again after an obstacle asks the stack again', function (): void {
    $supervising = AStackThatSupervises::met(Obstacle::DeviceHasNoNetwork);
    $screen = theThingScreen($supervising);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($supervising->askings())->toBe(2);
});

it('N3-R13 — a credential refused on the verb lets the session go too', function (): void {
    // The half a read cannot reach. A stack that refuses a credential the
    // moment somebody taps stop is the same signed-out device as one that
    // refuses it on a read, and this is the call that happens on the tap.
    $keychain = AKeychainInMemory::working();
    $supervising = AStackThatSupervises::withButRefusing(
        WhatAMachineRuns::twoThings(),
        Obstacle::CredentialWasRefused,
    );
    $screen = theThingScreen($supervising, keychain: $keychain);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->agree();

    expect($supervising->whatItWasToldToDo())->toHaveCount(1)
        ->and($keychain->isHolding(theMachineTheseVerbsReach()->id()))->toBeFalse();
});

it('N3-R13 — a machine that cannot be reached keeps its session', function (): void {
    // A phone in flight mode has not lost its pairing, and forgetting the
    // session there would make somebody sign in again to start a service they
    // were entitled to start all along.
    $keychain = AKeychainInMemory::working();
    $screen = theThingScreen(
        AStackThatSupervises::met(Obstacle::DeviceHasNoNetwork),
        keychain: $keychain,
    );

    expect($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($keychain->isHolding(theMachineTheseVerbsReach()->id()))->toBeTrue();
});

it('a session that ended between the reading and the yes sends nothing', function (): void {
    // The narrow path a removed identity opens: the listing was read while the session
    // worked, and the stack refused it in between. This must come away quietly
    // rather than raise on a tap — the frame after it is the sign-in screen.
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $keychain = AKeychainInMemory::working();
    $screen = theThingScreen($supervising, keychain: $keychain);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $keychain->forget(theMachineTheseVerbsReach()->id());
    $screen->agree();

    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->answer()->went->isSignedIn)->toBeFalse();
});

it('refuses a route parameter that is not text', function (): void {
    // A parameter arrives as `mixed`, because the navigation stack's own
    // parameter array is untyped. Anything that is not a string names no stack.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));
    $screen->setParams(['stack' => 42, 'service' => 'sonarr']);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('refuses a named thing that is not text', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));
    $screen->setParams(['stack' => theMachineTheseVerbsReach()->id()->stored(), 'service' => 42]);

    expect($screen->thing()->named)->toBe('')
        ->and($screen->thing()->isRun())->toBeFalse();
});

it('N2-R7 — the screen is registered under the route that reaches it', function (): void {
    $resolved = NativeRouter::resolve(AStacksScreen::Doing->forTheStacksService(
        theMachineTheseVerbsReach()->id(),
        ServiceId::called('sonarr'),
    ));

    expect($resolved['class'] ?? null)->toBe(WhatToDoWithThis::class);
});

it('the way back to the machine and on to the logs are routes as well', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));

    expect(NativeRouter::resolve($screen->goes()->services()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->logsOf(ServiceId::called('sonarr'))))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));

    expect($screen->render()->name())->toBe('operator::what-to-do-with-this');
});
