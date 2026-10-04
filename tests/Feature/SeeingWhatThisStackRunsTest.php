<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
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
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Modules\Operator\Internal\ViewModels\WhatOneServiceSays;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatANeedSays;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatThePhoneKeeps;

/** The machine whose services this screen is about. */
function theStackWhoseServicesAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('9', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('e', Fingerprint::CHARACTERS)),
    );
}

/**
 * The screen, with a stack it knows and a keychain holding whatever a test says.
 *
 * Named for this file, since the root suites share one namespace (`G10`).
 */
function theServicesScreen(
    AStackThatSupervises $supervising,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatThisStackRuns {
    $stack = theStackWhoseServicesAreRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatThisStackRuns($supervising, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening(), WhatThePhoneKeeps::noListingYet());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('shows every service, and how it runs', function (): void {
    $screen = theServicesScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));
    $answer = $screen->answer();

    expect($answer->services)->toHaveCount(2)
        // A stack that answered is not a session that ended. `isSignedIn` is
        // the template's first branch, so a fold reporting otherwise would put
        // The sign-in prompt in front of a working session and the rows
        // would never be reached at all.
        ->and($answer->went->isSignedIn)->toBeTrue()
        ->and($answer->went->met)->toBe('')
        // And no remedy, which is the pair `met` is read with: a template
        // branching on one and printing the other would put *what to do about
        // it* under a machine where nothing went wrong.
        ->and($answer->went->remedy)->toBe('')
        ->and($answer->services[0]->id->named())->toBe('sonarr')
        ->and($answer->services[0]->runsSaid)->toBe(HowAServiceRuns::Running->saidOnTheScreen());
});

it('a row carries the name an operator recognises, beside the id a verb uses', function (): void {
    // Two facts and not one. On a stack where somebody renamed a service they
    // differ, and a row carrying only the id puts an identifier in front of
    // somebody looking for *Sonarr*.
    $screen = theServicesScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));
    $row = $screen->answer()->services[0];

    expect($row->name)->toBe('Sonarr')
        ->and($row->id->named())->toBe('sonarr')
        // How much it matters is what decides how loudly a stop is asked
        // about, so it travels with the row rather than being looked up.
        ->and($row->mattersSaid)->toBe(HowMuchItMatters::Important->saidOnTheScreen());
});

it('carries the forms whether or not anything in them is running', function (): void {
    // The form with everything stopped is the one an operator opened this
    // screen to start, and a listing assembled from the rows would not have it.
    $running = Daemons::of(
        HowTheStackIsRunning::Partial,
        WhatAMachineRuns::whatTheVerbsCost(),
        WhatAMachineRuns::aService(),
    );

    $screen = theServicesScreen(AStackThatSupervises::with($running)->declaring(WhatAMachineRuns::libraryAndFull()));
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    expect($screen->forms()?->names)->toBe(['library', 'full']);
});

it('draws a control for each form the stack declares', function (): void {
    // The stack declares `library` and `full`, and a control is drawn for
    // each, so the form an operator opens this screen to start is on it.
    $frame = WhatTheDeviceWouldDraw::onTheSecondFrame(theServicesScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull())));

    expect($frame->offers())->toContain('library')
        ->and($frame->offers())->toContain('full')
        ->and($frame->said())->not->toContain(__('health.no_forms_at_all'));
});

it('a stack that declares no forms says that, rather than that nothing is set up', function (): void {
    $none = Daemons::of(
        HowTheStackIsRunning::Active,
        WhatAMachineRuns::whatTheVerbsCost(),
        WhatAMachineRuns::aService(),
    );

    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame(theServicesScreen(AStackThatSupervises::with($none)))->said();

    expect($drawn)->toContain('This stack declares no forms');
});

it('reads the forms on the frame after what is running, and asks for that frame at once', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = theServicesScreen($supervising);

    $first = WhatTheDeviceWouldDraw::by($screen);
    $asked = [$supervising->askings(), $supervising->formsAskings()];
    $waited = $screen->waitsForTheNextFrame();
    $second = WhatTheDeviceWouldDraw::by($screen);

    expect($asked)->toBe([1, 0])
        ->and($first->offers())->not->toContain('library')
        ->and($waited)->toBeTrue()
        ->and($screen->waitsForTheNextFrame())->toBeFalse()
        ->and([$supervising->askings(), $supervising->formsAskings()])->toBe([1, 1])
        ->and($second->offers())->toContain('library');
});

it('keeps the forms while what is running is read again', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->declaring(WhatAMachineRuns::libraryAndFull());
    $screen = theServicesScreen($supervising);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    $screen->again();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect([$supervising->askings(), $supervising->formsAskings()])->toBe([2, 1])
        ->and($drawn->offers())->toContain('library');
});

it('forms that could not be read stop the screen on the next frame, and are asked again with it', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings())->whoseFormsMeet(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $screen = theServicesScreen($supervising);

    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $waited = $screen->waitsForTheNextFrame();
    $stopped = WhatTheDeviceWouldDraw::by($screen);
    $screen->again();
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    expect($waited)->toBeTrue()
        ->and($drawn->said())->not->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($stopped->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($supervising->formsAskings())->toBe(2);
});

it('looks again only while something is settling', function (): void {
    $settling = Daemons::of(
        HowTheStackIsRunning::Partial,
        WhatAMachineRuns::whatTheVerbsCost(),
        WhatAMachineRuns::aService('sonarr', HowAServiceRuns::Starting),
    );
    $supervising = AStackThatSupervises::with($settling);
    $screen = theServicesScreen($supervising);

    expect($screen->answer()->isSettling)->toBeTrue();

    $screen->whileItSettles();
    $screen->answer();

    // Twice: the first reading, and the one the cadence asked for. Counted off
    // the port, because that is the only thing that can say a second reading
    // happened: `answer()` hands back a fold either way, so asking whether it is
    // null says nothing about whether the cadence did anything.
    expect($supervising->askings())->toBe(2);
});

it('a standing listing is not polled', function (): void {
    // Every state but `starting` is a standing answer, so a stack that is not
    // settling answers the same thing however often it is read — and the
    // cadence costs a machine on a home network nothing.
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theServicesScreen($supervising);

    $screen->answer();
    $screen->whileItSettles();
    $screen->whileItSettles();

    expect($supervising->askings())->toBe(1);
});

it('a device with no session for it asks nothing', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theServicesScreen($supervising, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->services)->toBe([])
        ->and($supervising->askings())->toBe(0);
});

it('an obstacle is what stood in the way, with what to do about it', function (): void {
    $screen = theServicesScreen(AStackThatSupervises::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)));
    $answer = $screen->answer();

    expect($answer->went->met)->toEqual(KindOfObstacle::DeviceHasNoNetwork->said())
        ->and($answer->went->remedy)->toEqual(KindOfObstacle::DeviceHasNoNetwork->remedy())
        // Signed in, and the listing is empty because nothing was read — not
        // because the machine is running nothing.
        ->and($answer->went->isSignedIn)->toBeTrue()
        ->and($answer->services)->toBe([])
        // Which is why there is no overall either. A stack that could not be
        // reached has not been found to be healthy, and a fold that carried a
        // verdict here would put one on a screen assembled from nothing.
        ->and($answer->overall)->toBe('')
        // And nothing is settling, so the cadence stops. A screen that polled
        // through an obstacle would keep a phone talking to a machine that is
        // not answering.
        ->and($answer->isSettling)->toBeFalse();
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    // Both halves, because a fold cannot forget anything. Rendering the
    // signed-out state and leaving the session in the store means the next
    // frame resumes it, is refused again, and the operator reads a sign-in
    // prompt over a device that still believes it is signed in.
    $keychain = AKeychainInMemory::working();
    $screen = theServicesScreen(AStackThatSupervises::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $keychain);

    expect($keychain->isHolding(theStackWhoseServicesAreRead()->id()))->toBeTrue();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        // Nothing about a machine, because this is not about the machine — and
        // nothing already loaded, which matters more here than on a listing
        // nobody acts from: what is already loaded is six buttons that change
        // somebody's machine.
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->services)->toBe([])
        ->and($screen->answer()->overall)->toBe('')
        // The remedy and the cadence as well. A signed-out screen offering
        // *what to do about it* would be answering about a machine nobody
        // asked, and one that reported itself settling would poll a stack this
        // device has no session for, for ever.
        ->and($screen->answer()->went->remedy)->toBe('')
        ->and($screen->answer()->isSettling)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseServicesAreRead()->id()))->toBeFalse();
});

it('a machine that cannot be reached keeps its session', function (): void {
    // The line's other side. A phone in flight mode has not lost its pairing,
    // and forgetting the session there would make somebody sign in again to
    // start a service they were entitled to start all along.
    $keychain = AKeychainInMemory::working();
    $screen = theServicesScreen(AStackThatSupervises::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($screen->answer()->went->met)->toEqual(KindOfObstacle::DeviceHasNoNetwork->said())
        ->and($keychain->isHolding(theStackWhoseServicesAreRead()->id()))->toBeTrue();
});

it('asking again after an obstacle asks the stack again', function (): void {
    $supervising = AStackThatSupervises::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork));
    $screen = theServicesScreen($supervising);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($supervising->askings())->toBe(2);
});

it('a stack running nothing is an answer and not a gap', function (): void {
    $screen = theServicesScreen(AStackThatSupervises::withNothingRunning());

    expect($screen->answer()->services)->toBe([])
        ->and($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($screen->answer()->overall)->toBe(HowTheStackIsRunning::Inactive->saidOnTheScreen());
});

it('refuses a route parameter that is not text', function (): void {
    // A parameter arrives as `mixed`, because the navigation stack's own
    // parameter array is untyped. Anything that is not a string names no stack,
    // which is the same situation as a route with nothing in that segment —
    // asserted rather than assumed, because the narrowing is a branch and a
    // branch nothing drives is a branch that can quietly become the other one.
    // The five other screens with this shape each make this assertion; this was
    // the sixth, and it did not.
    $screen = theServicesScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the screen is registered under the route that reaches it', function (): void {
    $resolved = NativeRouter::resolve(
        AStacksScreen::Services->forTheStack(theStackWhoseServicesAreRead()->id()),
    );

    expect($resolved['class'] ?? null)->toBe(WhatThisStackRuns::class);
});

it('the way back to the machine and on to the logs are routes as well', function (): void {
    $screen = theServicesScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->logsOf(ServiceId::called('sonarr'))))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theServicesScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings()));

    expect($screen->render()->name())->toBe('operator::what-this-stack-runs');
});

it('says which forms are running, and every form each service runs for', function (): void {
    $screen = theServicesScreen(AStackThatSupervises::with(WhatAMachineRuns::partOfItOnPurpose()));
    $answer = $screen->answer();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    // Each row says how it runs and what it runs for on the one line under
    // its name.
    $lines = implode("\n", $drawn);

    expect($answer->active)->toBe(['library', 'hunt'])
        ->and($answer->services[0]->runsFor)->toBe(['library', 'hunt'])
        ->and($answer->services[1]->runsFor)->toBe(['hunt'])
        ->and($drawn)->toContain(__('health.forms_running', ['forms' => 'library, hunt']))
        ->and($lines)->toContain(__('health.runs_for', ['forms' => 'library, hunt']))
        ->and($lines)->toContain(__('health.runs_for', ['forms' => 'hunt']));
});

it('draws a service the forms left out as left out with why, and not as a service that is absent', function (): void {
    $screen = theServicesScreen(AStackThatSupervises::with(WhatAMachineRuns::partOfItOnPurpose()));
    $answer = $screen->answer();
    $named = [];

    foreach ($answer->services as $service) {
        $named[] = $service->id->named();
    }

    expect($named)->toBe(['jellyfin', 'sonarr'])
        ->and($answer->leftOut)->toHaveCount(1)
        ->and([$answer->leftOut[0]->name, $answer->leftOut[0]->askedBy])->toBe(['qBittorrent', ['hunt']])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain('qBittorrent')
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.left_out_by', [
            'forms' => 'hunt',
            'needs' => WhatANeedSays::of(WhatItWouldNeed::Torrent),
        ]));
});

it('says so where no form is running, nothing was left out, and a service no running form asked for', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theServicesScreen(AStackThatSupervises::with(WhatAMachineRuns::twoThings())))->said();

    expect($drawn)->toContain(__('health.no_form_running'))
        ->and($drawn)->toContain(__('health.nothing_left_out'))
        ->and(implode("\n", $drawn))->toContain(__('health.runs_for_no_form'));
});

/** A stack running Sonarr, with Caddy and Navidrome there to run and asked for by nothing. */
function aStackWithTwoThingsNobodyAskedFor(): Daemons
{
    return Daemons::of(
        HowTheStackIsRunning::Active,
        WhatAMachineRuns::whatTheVerbsCost(),
        WhatAMachineRuns::aService('sonarr')->broughtInBy(Forms::these(Form::called('library'))),
        WhatAMachineRuns::aService('caddy', HowAServiceRuns::Absent),
        WhatAMachineRuns::aService('navidrome', HowAServiceRuns::Absent),
    );
}

it('folds what no running form asked for away at the foot, counted, quiet and without a warning', function (): void {
    $screen = theServicesScreen(AStackThatSupervises::with(aStackWithTwoThingsNobodyAskedFor()));
    $answer = $screen->answer();
    $folded = WhatTheDeviceWouldDraw::by($screen)->said();

    $installed = array_map(static fn(WhatOneServiceSays $service): string => $service->name, $answer->installed());
    $notInstalled = array_map(static fn(WhatOneServiceSays $service): string => $service->name, $answer->notInstalled());

    expect($installed)->toBe(['Sonarr'])
        ->and($notInstalled)->toBe(['Caddy', 'Navidrome'])
        ->and($answer->notInstalled()[0]->tone)->toBe('quiet')
        ->and($folded)->toContain(__('health.not_installed', ['count' => 2]))
        ->and($folded)->not->toContain('Caddy')
        ->and(implode("\n", $folded))->not->toContain(__(HowAServiceRuns::Absent->saidOnTheScreen()));

    $screen->showWhatIsNotInstalled();
    $opened = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($opened)->toContain('Caddy')
        ->and($opened)->toContain('Navidrome')
        ->and($screen->goes()->doingWith($answer->notInstalled()[0]->id))
        ->toBe(AStacksScreen::Doing->forTheStacksService(theStackWhoseServicesAreRead()->id(), ServiceId::called('caddy')));

    $screen->showWhatIsNotInstalled();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain('Caddy');
});

it('keeps a service a running form asked for and has not got among the rest, with its warning', function (): void {
    $screen = theServicesScreen(AStackThatSupervises::with(Daemons::of(
        HowTheStackIsRunning::Degraded,
        WhatAMachineRuns::whatTheVerbsCost(),
        WhatAMachineRuns::aService('jellyfin', HowAServiceRuns::Absent)->broughtInBy(Forms::these(Form::called('library'))),
    )));

    expect($screen->answer()->notInstalled())->toBe([])
        ->and($screen->answer()->installed()[0]->tone)->toBe('attention')
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('health.not_installed', ['count' => 0]));
});
