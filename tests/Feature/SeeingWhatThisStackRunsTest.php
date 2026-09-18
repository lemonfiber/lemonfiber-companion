<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
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
use Modules\Operator\Internal\AStacksScreen;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatAMachineRuns;

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
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'));
    }

    $screen = new WhatThisStackRuns($supervising, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('N2-R7 — shows every service, how it runs, and which form it is in', function (): void {
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
        ->and($answer->services[0]->form)->toBe('downloads')
        ->and($answer->services[0]->runsSaid)->toBe(HowAServiceRuns::Running->saidOnTheScreen());
});

it('N2-R7 — a row carries the name an operator recognises, beside the id a verb uses', function (): void {
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

it('N2-R7 — carries the forms whether or not anything in them is running', function (): void {
    // The form with everything stopped is the one an operator opened this
    // screen to start, and a listing assembled from the rows would not have it.
    $running = Daemons::of(
        HowTheStackIsRunning::Partial,
        Forms::these(Form::called('downloads'), Form::called('media')),
        WhatAMachineRuns::whatTheVerbsCost(),
        WhatAMachineRuns::aService(),
    );

    expect(theServicesScreen(AStackThatSupervises::with($running))->answer()->forms)
        ->toBe(['downloads', 'media']);
});

it('N1-R27 — looks again only while something is settling', function (): void {
    $settling = Daemons::of(
        HowTheStackIsRunning::Partial,
        Forms::these(Form::called('downloads')),
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
    expect($supervising->askings())->toBe(2)
        ->and($screen->cadence())->toBe(HowOften::WhileWorkRuns);
});

it('N1-R66 — a standing listing is not polled', function (): void {
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

it('N1-R44 — a device with no session for it asks nothing', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theServicesScreen($supervising, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->services)->toBe([])
        ->and($supervising->askings())->toBe(0);
});

it('N1-R10 — an obstacle is what stood in the way, with what to do about it', function (): void {
    $screen = theServicesScreen(AStackThatSupervises::met(Obstacle::DeviceHasNoNetwork));
    $answer = $screen->answer();

    expect($answer->went->met)->toBe(Obstacle::DeviceHasNoNetwork->said())
        ->and($answer->went->remedy)->toBe(Obstacle::DeviceHasNoNetwork->remedy())
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

it('N3-R13 — a credential the stack refused signs this device out and lets the session go', function (): void {
    // Both halves, because a fold cannot forget anything. Rendering the
    // signed-out state and leaving the session in the store means the next
    // frame resumes it, is refused again, and the operator reads a sign-in
    // prompt over a device that still believes it is signed in.
    $keychain = AKeychainInMemory::working();
    $screen = theServicesScreen(AStackThatSupervises::met(Obstacle::CredentialWasRefused), $keychain);

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

it('N3-R13 — a machine that cannot be reached keeps its session', function (): void {
    // The line's other side. A phone in flight mode has not lost its pairing,
    // and forgetting the session there would make somebody sign in again to
    // start a service they were entitled to start all along.
    $keychain = AKeychainInMemory::working();
    $screen = theServicesScreen(AStackThatSupervises::met(Obstacle::DeviceHasNoNetwork), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($screen->answer()->went->met)->toBe(Obstacle::DeviceHasNoNetwork->said())
        ->and($keychain->isHolding(theStackWhoseServicesAreRead()->id()))->toBeTrue();
});

it('N1-R3 — asking again after an obstacle asks the stack again', function (): void {
    $supervising = AStackThatSupervises::met(Obstacle::DeviceHasNoNetwork);
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

it('N2-R7 — the screen is registered under the route that reaches it', function (): void {
    $resolved = NativeRouter::resolve(
        AStacksScreen::Services->forTheStack(theStackWhoseServicesAreRead()->id()->stored()),
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
