<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AFootprint;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\AServiceLeftOut;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\WhatIsAlreadyRunning;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatLeansOnIt;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatRehearses;
use Tests\Support\Fakes\AStackThatSaysWhatItWaitsOn;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatANeedSays;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatThePhoneKeeps;

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
    ?AStackThatRehearses $rehearsing = null,
): WhatToDoWithThis {
    $stack = theMachineTheseVerbsReach();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $rehearsing ??= AStackThatRehearses::with(WhatStartingItWouldComeTo::rehearsed(Services::none(), TheServicesLeftOut::of(), AFootprint::estimated(0, Services::none()), WhatIsAlreadyRunning::these(Services::none())));
    $screen = new WhatToDoWithThis($supervising, $rehearsing, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AStackThatSaysWhatItWaitsOn::saying(), AroundThePhone::listening(), WhatThePhoneKeeps::noListingYet());
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $named]);

    return $screen;
}

/**
 * The screen, on the frame after the one that read everything a form needs.
 *
 * A frame reads the stack once: the first what is running, the second the
 * forms, and the third what starting one would come to.
 */
function everythingRead(WhatToDoWithThis $screen): WhatToDoWithThis
{
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->render();

    return $screen;
}

it('the frame is about the one thing the route names', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()));
    $thing = $screen->thing();

    expect($thing->named)->toBe('sonarr')
        ->and($thing->isAForm)->toBeFalse()
        ->and($thing->isRun())->toBeTrue()
        ->and($thing->service?->name)->toBe('Sonarr')
        // The details that belong to the one thing rather than to the list.
        // A row carrying all of them is a list nobody can scan. What leans on
        // it is said by the name the operator knows, not the identifier.
        ->and($thing->service?->leaning)->toBe(['Jellyfin']);
});

it('a thing this machine is not running is an answer, not a blank frame', function (): void {
    // A route can name anything: a list tapped a moment before the stack
    // changed, or a screen restored after a service left its form.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()), 'a-thing-nobody-listed');

    expect($screen->thing()->isRun())->toBeFalse()
        ->and($screen->thing()->verbs)->toBe([])
        ->and($screen->thing()->named)->toBe('a-thing-nobody-listed');
});

it('a route naming nothing at all is an answer, not a blank frame', function (): void {
    // Blank rather than absent, which a navigation stack can produce and
    // `ServiceId::called()` would raise on. Nothing here is named nothing, so
    // it comes away as *no such thing* like any other name never read.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()), '   ');

    expect($screen->thing()->isRun())->toBeFalse()
        ->and($screen->thing()->named)->toBe('');
});

it('offers only the verbs this one state can take', function (): void {
    $running = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()));

    expect($running->thing()->verbs)->toBe([WhatToDoWithIt::Stop, WhatToDoWithIt::Restart]);

    $stopped = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Stopped, HowTheStackIsRunning::Partial)));

    expect($stopped->thing()->verbs)->toBe([WhatToDoWithIt::Start]);
});

it('a service this stack does not run is offered no verb at all', function (): void {
    // The verbs are about what this stack runs. A verb about something the host
    // runs would be refused by the machine, and offering it teaches an operator
    // that the buttons here are a guess.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::HostManaged, HowTheStackIsRunning::Active)));

    expect($screen->thing()->isRun())->toBeTrue()
        ->and($screen->thing()->service?->isOurs)->toBeFalse()
        ->and($screen->thing()->verbs)->toBe([]);
});

it('a whole form takes all three, because it has no state of its own', function (): void {
    $screen = everythingRead(theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()), 'library'));

    expect($screen->thing()->isAForm)->toBeTrue()
        ->and($screen->thing()->service)->toBeNull()
        ->and($screen->thing()->verbs)->toBe(WhatToDoWithIt::cases());
});

it('says nothing about a name no service goes by until the forms are read, on the next frame', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = theThingScreen($supervising, 'library');

    $first = WhatTheDeviceWouldDraw::by($screen);
    $waited = $screen->waitsForTheNextFrame();
    $second = WhatTheDeviceWouldDraw::by($screen);

    expect($waited)->toBeTrue()
        ->and($first->said())->not->toContain('library')
        ->and($first->said())->not->toContain(__('health.nothing_of_that_name', ['name' => 'library']))
        ->and($second->said())->toContain('library')
        ->and([$supervising->askings(), $supervising->formsAskings()])->toBe([1, 1]);
});

it('forms that could not be read stop the screen on the next frame, and say nothing of the name before', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->whoseFormsMeet(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), 'library');

    $second = WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $third = WhatTheDeviceWouldDraw::by($screen);

    expect($second->said())->not->toContain(__('health.nothing_of_that_name', ['name' => 'library']))
        ->and($second->said())->not->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($third->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));
});

it('never asks for the forms where a service goes by the name', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());

    WhatTheDeviceWouldDraw::onTheSecondFrame(theThingScreen($supervising));

    expect($supervising->formsAskings())->toBe(0);
});

it('a whole form is agreed to as a form', function (): void {
    // The other granularity, and it must not arrive at the port
    // as a service: a form's name sent under `services` would stop nothing and
    // report that it had.
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = everythingRead(theThingScreen($supervising, 'library'));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->agree();

    $told = $supervising->whatItWasToldToDo();

    expect($told)->toHaveCount(1)
        ->and($told[0]->isAboutAForm())->toBeTrue()
        ->and($told[0]->named())->toBe('library');
});

it('a service wins over a form that shares its name', function (): void {
    // The narrower reading is the safer one: agreeing about one service and
    // being sent a whole form is the mistake that costs a household something.
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::oneThing('library', HowAServiceRuns::Running, HowTheStackIsRunning::Active));
    $screen = everythingRead(theThingScreen($supervising, 'library'));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->agree();

    expect($screen->thing()->isAForm)->toBeFalse()
        ->and($supervising->whatItWasToldToDo()[0]->isAboutAForm())->toBeFalse();
});

it('a stop is asked about before anything is sent', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = theThingScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);

    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->asking())->toBeInstanceOf(AgreedTo::class)
        ->and($screen->asking()?->doing())->toBe(WhatToDoWithIt::Stop)
        ->and($screen->asking()?->named())->toBe('sonarr');
});

it('the yes sends what was stated and not what a tap carries', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
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

it('saying never mind sends nothing at all', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = theThingScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->neverMind();
    $screen->agree();

    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->asking())->toBeNull();
});

it('a start is not asked about, because it disturbs nothing', function (): void {
    // A screen that asked about a start would teach an operator to confirm
    // without reading, which is what makes the stop confirmation worth anything.
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Stopped, HowTheStackIsRunning::Partial));
    $screen = theThingScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);

    expect($screen->asking())->toBeNull()
        ->and($supervising->whatItWasToldToDo())->toHaveCount(1)
        ->and($supervising->whatItWasToldToDo()[0]->doing())->toBe(WhatToDoWithIt::Start);
});

it('states what will not work while it is off', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);

    expect($screen->thing()->service?->leaning)->toBe(['Jellyfin'])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain('Jellyfin')
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain('jellyfin');
});

it('says a restart will not help where it is already looping', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::CrashLooping, HowTheStackIsRunning::Degraded)));

    $screen->wouldYouLike(WhatToDoWithIt::Restart->value);

    expect($screen->aRestartWouldNotHelp())->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.would_not_help'));
});

it('carries no restart warning on a stop of a service that is looping', function (): void {
    // The warning is about another restart joining a queue of starts, which
    // a stop is not.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::CrashLooping, HowTheStackIsRunning::Degraded)));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);

    expect($screen->thing()->service?->wouldNotHelp)->toBeTrue()
        ->and($screen->aRestartWouldNotHelp())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('health.would_not_help'));
});

it('carries no restart warning on a restart of a service that is not looping', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()));

    $screen->wouldYouLike(WhatToDoWithIt::Restart->value);

    expect($screen->asking())->not->toBeNull()
        ->and($screen->aRestartWouldNotHelp())->toBeFalse();
});

it('carries no restart warning while nothing is being asked', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::CrashLooping, HowTheStackIsRunning::Degraded)));

    expect($screen->aRestartWouldNotHelp())->toBeFalse();
});

it('the confirmation says how long the verb takes it away for', function (): void {
    // The number is the stack's: a length worked out here would be a guess at
    // something the stack knows, which is what is refused.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()));
    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);

    expect($screen->whatItTakesAway()?->said)->toBe('health.for_at_most')
        ->and($screen->whatItTakesAway()?->seconds)->toBe(10);
});

it('a stop and a restart are not held to the same clock', function (): void {
    // Two verbs, two numbers, read off the same listing. A screen that stated
    // one length for every verb would be stating a number that nothing honours
    // for every verb but one.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $stopping = $screen->whatItTakesAway()?->seconds;

    $screen->neverMind();
    $screen->wouldYouLike(WhatToDoWithIt::Restart->value);
    $restarting = $screen->whatItTakesAway()?->seconds;

    expect($stopping)->toBe(10)
        ->and($restarting)->toBe(180);
});

it('asks before fetching a form\'s images, saying it may take long and use the line, with no figure', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = everythingRead(theThingScreen($supervising, 'library'));

    $screen->wouldYouLike(WhatToDoWithIt::Pull->value);

    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->asking()?->doing())->toBe(WhatToDoWithIt::Pull)
        ->and($screen->whatItTakesAway()?->said)->toBe('health.fetch_may_take_long')
        ->and($screen->whatItTakesAway()?->seconds)->toBeNull();

    $screen->agree();
    $told = $supervising->whatItWasToldToDo();

    expect($told)->toHaveCount(1)
        ->and($told[0]->doing())->toBe(WhatToDoWithIt::Pull)
        ->and($told[0]->isAboutAForm())->toBeTrue()
        ->and($told[0]->named())->toBe('library');
});

it('offers no fetch for one service, and acts on none asked for there', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = theThingScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Pull->value);

    expect($screen->thing()->verbs)->not->toContain(WhatToDoWithIt::Pull)
        ->and($screen->asking())->toBeNull()
        ->and($supervising->whatItWasToldToDo())->toBe([]);
});

it('nothing is stated where nothing is being asked', function (): void {
    // Absent because there is no question, not because the stack said nothing.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()));

    expect($screen->asking())->toBeNull()
        ->and($screen->whatItTakesAway())->toBeNull();
});

it('states no length once the reading it came from is gone', function (): void {
    // The bound is the listing's, so a reading that met an obstacle carries
    // none — and a sentence built from a number this app invented is exactly
    // what substitution refuses.
    $screen = theThingScreen(AStackThatSupervises::thenMeeting(
        WhatAMachineRuns::twoThings(),
        Obstacle::of(KindOfObstacle::DeviceHasNoNetwork),
    ));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $screen->again();

    expect($screen->asking())->toBeInstanceOf(AgreedTo::class)
        ->and($screen->whatItTakesAway())->toBeNull();
});

it('a verb this app does not have is not acted on', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = theThingScreen($supervising);

    $screen->wouldYouLike('delete');

    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->asking())->toBeNull();
});

it('a route naming something this screen never read is not acted on', function (): void {
    // The half that makes the confirmation mean anything. The agreement is
    // built from the listing rather than from the route, so a URI naming a
    // service that is not on this machine reaches nothing.
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
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

it('looks again only while something is settling', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Starting, HowTheStackIsRunning::Partial));
    $screen = theThingScreen($supervising);

    expect($screen->answer()->isSettling)->toBeTrue();

    $screen->whileItSettles();
    $screen->answer();

    expect($supervising->askings())->toBe(2);
});

it('a standing thing is not polled', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = theThingScreen($supervising);

    $screen->answer();
    $screen->whileItSettles();
    $screen->whileItSettles();

    expect($supervising->askings())->toBe(1);
});

it('a device with no session for it asks nothing', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = theThingScreen($supervising, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->thing()->isRun())->toBeFalse()
        ->and($supervising->askings())->toBe(0);
});

it('an obstacle is what stood in the way, with what to do about it', function (): void {
    $screen = theThingScreen(AStackThatSupervises::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)));
    $answer = $screen->answer();

    expect($answer->went->met)->toEqual(KindOfObstacle::DeviceHasNoNetwork->said())
        ->and($answer->went->remedy)->toEqual(KindOfObstacle::DeviceHasNoNetwork->remedy())
        ->and($answer->went->isSignedIn)->toBeTrue();
});

it('asking again after an obstacle asks the stack again', function (): void {
    $supervising = AStackThatSupervises::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork));
    $screen = theThingScreen($supervising);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($supervising->askings())->toBe(2);
});

it('a credential refused on the verb lets the session go too', function (): void {
    // The half a read cannot reach. A stack that refuses a credential the
    // moment somebody taps start is the same signed-out device as one that
    // refuses it on a read, and this is the call that happens on the tap. A
    // start asks no question, so nothing is asked of the stack before it.
    $keychain = AKeychainInMemory::working();
    $supervising = AStackThatSupervises::withButRefusing(
        WhatAMachineRuns::twoThings(),
        Obstacle::of(KindOfObstacle::CredentialWasRefused),
    );
    $screen = theThingScreen($supervising, keychain: $keychain);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value);

    expect($supervising->whatItWasToldToDo())->toHaveCount(1)
        ->and($keychain->isHolding(theMachineTheseVerbsReach()->id()))->toBeFalse();
});

it('lets the session go where the stack refuses it on the rehearsal asked before a yes', function (): void {
    $keychain = AKeychainInMemory::working();
    $supervising = AStackThatSupervises::withButRefusing(
        WhatAMachineRuns::twoThings(),
        Obstacle::of(KindOfObstacle::CredentialWasRefused),
    );
    $screen = theThingScreen($supervising, keychain: $keychain);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);

    expect($supervising->whatItWasAskedToRehearse())->toHaveCount(1)
        ->and($supervising->whatItWasToldToDo())->toBe([])
        ->and($keychain->isHolding(theMachineTheseVerbsReach()->id()))->toBeFalse();
});

it('a machine that cannot be reached keeps its session', function (): void {
    // A phone in flight mode has not lost its pairing, and forgetting the
    // session there would make somebody sign in again to start a service they
    // were entitled to start all along.
    $keychain = AKeychainInMemory::working();
    $screen = theThingScreen(
        AStackThatSupervises::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)),
        keychain: $keychain,
    );

    expect($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($keychain->isHolding(theMachineTheseVerbsReach()->id()))->toBeTrue();
});

it('a session that ended between the reading and the yes sends nothing', function (): void {
    // The narrow path a removed identity opens: the listing was read while the session
    // worked, and the stack refused it in between. This must come away quietly
    // rather than raise on a tap — the frame after it says the session ended,
    // beside the listing that was read while it worked.
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $keychain = AKeychainInMemory::working();
    $screen = theThingScreen($supervising, keychain: $keychain);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value);
    $keychain->forget(theMachineTheseVerbsReach()->id());
    $screen->agree();

    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->answer()->askedNow->isSignedIn)->toBeFalse()
        ->and($screen->answer()->waitsForTheStack)->toBeTrue();
});

it('refuses a route parameter that is not text', function (): void {
    // A parameter arrives as `mixed`, because the navigation stack's own
    // parameter array is untyped. Anything that is not a string names no stack.
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()));
    $screen->setParams(['stack' => 42, 'service' => 'sonarr']);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('refuses a named thing that is not text', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()));
    $screen->setParams(['stack' => theMachineTheseVerbsReach()->id()->stored(), 'service' => 42]);

    expect($screen->thing()->named)->toBe('')
        ->and($screen->thing()->isRun())->toBeFalse();
});

it('the screen is registered under the route that reaches it', function (): void {
    $resolved = NativeRouter::resolve(AStacksScreen::Doing->forTheStacksService(
        theMachineTheseVerbsReach()->id(),
        ServiceId::called('sonarr'),
    ));

    expect($resolved['class'] ?? null)->toBe(WhatToDoWithThis::class);
});

it('the way back to the machine and on to the logs are routes as well', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()));

    expect(NativeRouter::resolve($screen->goes()->to(AStacksScreen::Services)))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->logsOf(ServiceId::called('sonarr'))))->not->toBeNull();
});

it('draws the fetch\'s warning where a length would stand, and no length', function (): void {
    $screen = everythingRead(theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()), 'library'));
    $screen->wouldYouLike(WhatToDoWithIt::Pull->value);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('health.fetch_may_take_long'))
        ->and($drawn)->toContain(__('health.do.pull'))
        ->and($drawn)->toContain(__('health.about_to_form'));
});

it('renders its own view', function (): void {
    $screen = theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()));

    expect($screen->render()->name())->toBe('operator::what-to-do-with-this');
});

/** Starting `library` on a machine with no torrent credentials, and with nothing of it running unless told otherwise. */
function aRehearsalOfStartingTheLibrary(?WhatIsAlreadyRunning $running = null): WhatStartingItWouldComeTo
{
    return WhatStartingItWouldComeTo::rehearsed(
        Services::these(ServiceId::called('jellyfin'), ServiceId::called('sonarr')),
        TheServicesLeftOut::of(AServiceLeftOut::needing(ServiceId::called('qbittorrent'), 'qBittorrent', WhatItWouldNeed::Torrent, Forms::these(Form::called('library')))),
        AFootprint::estimated(700, Services::these(ServiceId::called('sonarr'))),
        $running ?? WhatIsAlreadyRunning::these(Services::none()),
    );
}

it('a form says what starting it would bring up and leave out, as a rehearsal, before its verbs', function (): void {
    $rehearsing = AStackThatRehearses::with(aRehearsalOfStartingTheLibrary());
    $screen = everythingRead(theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()), 'library', rehearsing: $rehearsing));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $rehearsed = array_search(__('health.rehearsal.heading'), $drawn, strict: true);
    $firstVerb = array_search(__(WhatToDoWithIt::cases()[0]->saidOnTheScreen()), $drawn, strict: true);

    expect($drawn)->toContain(__('health.rehearsal.nothing_started'))
        ->toContain(__('health.rehearsal.would_start', ['name' => 'jellyfin']))
        ->toContain(__('health.rehearsal.would_start', ['name' => 'sonarr']))
        ->toContain(__('health.rehearsal.left_out', ['name' => 'qBittorrent', 'needs' => WhatANeedSays::of(WhatItWouldNeed::Torrent)]))
        ->toContain(__('health.rehearsal.estimate', ['mib' => 700]))
        ->toContain(__('health.rehearsal.unestimated', ['services' => 'sonarr']))
        ->and(is_int($rehearsed) && is_int($firstVerb) && $rehearsed < $firstVerb)->toBeTrue()
        ->and($rehearsing->asked())->toHaveCount(1)
        ->and($rehearsing->asked()[0]->named())->toBe('library');
});

it('a form says which of what it would start is already running, and not that it would start', function (): void {
    $rehearsing = AStackThatRehearses::with(aRehearsalOfStartingTheLibrary(WhatIsAlreadyRunning::these(Services::these(ServiceId::called('jellyfin')))));
    $drawn = WhatTheDeviceWouldDraw::by(everythingRead(theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()), 'library', rehearsing: $rehearsing)))->said();

    expect(in_array(__('health.rehearsal.already_running', ['name' => 'jellyfin']), $drawn, strict: true))->toBeTrue()
        ->and(in_array(__('health.rehearsal.would_start', ['name' => 'sonarr']), $drawn, strict: true))->toBeTrue()
        ->and(in_array(__('health.rehearsal.would_start', ['name' => 'jellyfin']), $drawn, strict: true))->toBeFalse()
        ->and(in_array(__('health.rehearsal.running_unread'), $drawn, strict: true))->toBeFalse();
});

it('a form whose running could not be read says so once, above a list that still says what would start', function (): void {
    $rehearsing = AStackThatRehearses::with(aRehearsalOfStartingTheLibrary(WhatIsAlreadyRunning::couldNotBeRead()));
    $drawn = WhatTheDeviceWouldDraw::by(everythingRead(theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()), 'library', rehearsing: $rehearsing)))->said();
    $unread = array_keys($drawn, __('health.rehearsal.running_unread'), strict: true);

    $firstToStart = array_search(__('health.rehearsal.would_start', ['name' => 'jellyfin']), $drawn, strict: true);

    expect($unread)->toHaveCount(1)
        ->and(is_int($firstToStart) && $unread[0] < $firstToStart)->toBeTrue()
        ->and(in_array(__('health.rehearsal.would_start', ['name' => 'sonarr']), $drawn, strict: true))->toBeTrue();
});

it('a rehearsal that brings nothing up, leaves nothing out and takes nothing says so', function (): void {
    $screen = everythingRead(theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()), 'library'));

    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)
        ->toContain(__('health.rehearsal.would_start_nothing'))
        ->toContain(__('health.rehearsal.nothing_left_out'))
        ->toContain(__('health.rehearsal.estimate', ['mib' => 0]));
    expect($drawn)->not->toContain(__('health.rehearsal.unestimated', ['services' => '']));
});

it('a service is not rehearsed, because a rehearsal is of a form', function (): void {
    $rehearsing = AStackThatRehearses::with(aRehearsalOfStartingTheLibrary());
    WhatTheDeviceWouldDraw::by(theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()), rehearsing: $rehearsing));

    expect($rehearsing->asked())->toBe([]);
});

it('a rehearsal is asked for once, however often the template reads it', function (): void {
    $rehearsing = AStackThatRehearses::with(aRehearsalOfStartingTheLibrary());
    $screen = everythingRead(theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()), 'library', rehearsing: $rehearsing));
    $screen->rehearsal();
    $screen->rehearsal();

    expect($rehearsing->asked())->toHaveCount(1);
});

it('a rehearsal that could not be had says what stood in the way, and lets go of a refused session', function (): void {
    $keychain = AKeychainInMemory::working();
    $unanswered = everythingRead(theThingScreen(
        AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()),
        'library',
        rehearsing: AStackThatRehearses::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
    ));
    everythingRead(theThingScreen(
        AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()),
        'library',
        $keychain,
        rehearsing: AStackThatRehearses::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)),
    ))->rehearsal();

    expect($unanswered->rehearsal()?->went->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($unanswered->rehearsal()?->wouldStart)->toBe([])
        ->and($unanswered->rehearsal()?->leftOut)->toBe([])
        ->and($unanswered->rehearsal()?->estimatedMib)->toBeNull()
        ->and($unanswered->rehearsal()?->unestimated)->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($unanswered)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($keychain->isHolding(theMachineTheseVerbsReach()->id()))->toBeFalse();
});

it('a rehearsal on a device holding no session for the stack says so', function (): void {
    $screen = everythingRead(theThingScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull()), 'library', signedIn: false));

    expect($screen->rehearsal()?->went->isSignedIn)->toBeFalse()
        ->and($screen->rehearsal()?->wouldStart)->toBe([])
        ->and($screen->rehearsal()?->leftOut)->toBe([])
        ->and($screen->rehearsal()?->estimatedMib)->toBeNull()
        ->and($screen->rehearsal()?->unestimated)->toBe([]);
});

it('says a service that ended stopped with an error, and leaves its exit code for its logs', function (): void {
    $ended = Daemons::of(
        HowTheStackIsRunning::Degraded,
        WhatAMachineRuns::whatTheVerbsCost(),
        Daemon::thatExited('Sonarr', ServiceId::called('sonarr'), HowAServiceRuns::Stopped, HowMuchItMatters::Important, WhatLeansOnIt::nothing(), 137),
    );
    $screen = theThingScreen(AStackThatSupervises::with($ended));

    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->thing()->service?->exited)->toBe('137')
        ->and($screen->thing()->service?->carriedToTheLogs())->toBe(['exited' => '137', 'called' => 'Sonarr'])
        ->and($drawn)->toContain(__('health.it_stopped_with_an_error'))
        ->and(implode("\n", $drawn))->not->toContain('137');
});
