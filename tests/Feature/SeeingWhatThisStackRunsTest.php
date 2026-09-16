<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Disturbances;
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
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Kernel\Api\WhatLeansOnIt;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Operator\Internal\AStacksScreen;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\StacksInMemory;

/** What a stack reports its verbs cost, as this suite's stacks report them. */
function whatTheRunningVerbsCost(): Disturbances
{
    return Disturbances::of(
        starting: WhatItTakesAway::atMost(180),
        stopping: WhatItTakesAway::atMost(10),
        restarting: WhatItTakesAway::atMost(180),
    );
}

// N2-R7 and N2-R8 — the three verbs, and the sentence in front of two of them.
//
// The screen an operator opens on an evening when every check passes and the
// film still will not play: what is on, and what do I want on. Here rather than
// in the operator module's own tests because a screen renders, and rendering
// needs the application — `view()` and `__()` are not there in a module suite.

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

/** A service, with whatever this case is about changed. */
function aServiceRunning(
    string $id = 'sonarr',
    HowAServiceRuns $runs = HowAServiceRuns::Running,
    ?WhatLeansOnIt $leaning = null,
): Daemon {
    return Daemon::called(
        ucfirst($id),
        ServiceId::called($id),
        Form::called('downloads'),
        $runs,
        HowMuchItMatters::Important,
        $leaning ?? WhatLeansOnIt::nothing(),
    );
}

/** Two services in one form, one of them leaned on by the other. */
function aStackRunningTwoThings(): Daemons
{
    return Daemons::of(
        HowTheStackIsRunning::Active,
        Forms::these(Form::called('downloads'), Form::called('media')),
        whatTheRunningVerbsCost(),
        aServiceRunning('sonarr', leaning: WhatLeansOnIt::these(ServiceId::called('jellyfin'))),
        aServiceRunning('jellyfin'),
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
    $screen = theServicesScreen(AStackThatSupervises::with(aStackRunningTwoThings()));
    $answer = $screen->answer();

    expect($answer->services)->toHaveCount(2)
        // A stack that answered is not a session that ended. `isSignedIn` is
        // the template's first branch, so a fold reporting otherwise would put
        // `N1-R44`'s sign-in prompt in front of a working session and the rows
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
    $screen = theServicesScreen(AStackThatSupervises::with(aStackRunningTwoThings()));
    $row = $screen->answer()->services[0];

    expect($row->name)->toBe('Sonarr')
        ->and($row->id->named())->toBe('sonarr')
        // How much it matters is what decides how loudly a stop is asked
        // about, so it travels with the row rather than being looked up.
        ->and($row->mattersSaid)->toBe(HowMuchItMatters::Important->saidOnTheScreen());
});

it('N2-R7 — a service this stack does not run is not offered a verb', function (): void {
    // `N2-R7` is about what this stack runs. `B2-R15` has native-mode Jellyfin
    // report as host-managed and not be started or stopped by lemonfiber, so a
    // verb offered about one would be refused by the machine — and offering it
    // teaches an operator that the buttons here are a guess.
    $running = Daemons::of(
        HowTheStackIsRunning::Partial,
        Forms::these(Form::called('media')),
        whatTheRunningVerbsCost(),
        aServiceRunning('jellyfin', HowAServiceRuns::HostManaged),
        aServiceRunning('sonarr'),
    );
    $rows = theServicesScreen(AStackThatSupervises::with($running))->answer()->services;

    expect($rows[0]->isOurs)->toBeFalse()
        ->and($rows[1]->isOurs)->toBeTrue();
});

it('N2-R7 — a row says what it ended with, and says nothing where it did not', function (): void {
    // An empty field and a zero are different facts: a service that is running
    // has no exit code at all, and `0` is the code for one that ended well.
    $running = Daemons::of(
        HowTheStackIsRunning::Degraded,
        Forms::these(Form::called('downloads')),
        whatTheRunningVerbsCost(),
        Daemon::thatExited(
            'Sonarr',
            ServiceId::called('sonarr'),
            Form::called('downloads'),
            HowAServiceRuns::Failed,
            HowMuchItMatters::Important,
            WhatLeansOnIt::nothing(),
            137,
        ),
        aServiceRunning('jellyfin'),
    );
    $rows = theServicesScreen(AStackThatSupervises::with($running))->answer()->services;

    expect($rows[0]->exited)->toBe('137')
        ->and($rows[1]->exited)->toBe('');
});

it('N2-R7 — carries the forms whether or not anything in them is running', function (): void {
    // The form with everything stopped is the one an operator opened this
    // screen to start, and a listing assembled from the rows would not have it.
    $running = Daemons::of(
        HowTheStackIsRunning::Partial,
        Forms::these(Form::called('downloads'), Form::called('media')),
        whatTheRunningVerbsCost(),
        aServiceRunning(),
    );

    expect(theServicesScreen(AStackThatSupervises::with($running))->answer()->forms)
        ->toBe(['downloads', 'media']);
});

it('N2-R8 — a stop is asked about before anything is sent', function (): void {
    $supervising = AStackThatSupervises::with(aStackRunningTwoThings());
    $screen = theServicesScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value, 'sonarr');

    // The thing worth proving, and only the port can say it: the tap raised the
    // question and sent nothing.
    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->asking())->toBeInstanceOf(AgreedTo::class)
        ->and($screen->asking()?->doing())->toBe(WhatToDoWithIt::Stop)
        ->and($screen->asking()?->named())->toBe('sonarr');
});

it('N2-R8 — the yes sends what was stated and not what a tap carries', function (): void {
    $supervising = AStackThatSupervises::with(aStackRunningTwoThings());
    $screen = theServicesScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value, 'sonarr');
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
    $supervising = AStackThatSupervises::with(aStackRunningTwoThings());
    $screen = theServicesScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value, 'sonarr');
    $screen->neverMind();
    $screen->agree();

    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->asking())->toBeNull();
});

it('N2-R8 — a start is not asked about, because it disturbs nothing', function (): void {
    // A screen that asked about a start would teach an operator to confirm
    // without reading, which is what makes the stop confirmation worth anything.
    $supervising = AStackThatSupervises::with(aStackRunningTwoThings());
    $screen = theServicesScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value, 'sonarr');

    expect($screen->asking())->toBeNull()
        ->and($supervising->whatItWasToldToDo())->toHaveCount(1)
        ->and($supervising->whatItWasToldToDo()[0]->doing())->toBe(WhatToDoWithIt::Start);
});

it('N2-R8 — states what will not work while it is off', function (): void {
    $screen = theServicesScreen(AStackThatSupervises::with(aStackRunningTwoThings()));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value, 'sonarr');

    expect($screen->aboutTheService()?->leaning)->toBe(['jellyfin']);
});

it('N2-R8 — says a restart will not help where it is already looping', function (): void {
    $running = Daemons::of(
        HowTheStackIsRunning::Degraded,
        Forms::these(Form::called('downloads')),
        whatTheRunningVerbsCost(),
        aServiceRunning('sonarr', HowAServiceRuns::CrashLooping),
    );
    $screen = theServicesScreen(AStackThatSupervises::with($running));

    $screen->wouldYouLike(WhatToDoWithIt::Restart->value, 'sonarr');

    expect($screen->aboutTheService()?->wouldNotHelp)->toBeTrue();
});

it('N2-R7 — a whole form is agreed to as a form', function (): void {
    // The other granularity `N2-R7` names, and it must not arrive at the port
    // as a service: a form's name sent under `services` would stop nothing and
    // report that it had.
    $supervising = AStackThatSupervises::with(aStackRunningTwoThings());
    $screen = theServicesScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value, 'media');
    $screen->agree();

    $told = $supervising->whatItWasToldToDo();

    expect($told)->toHaveCount(1)
        ->and($told[0]->isAboutAForm())->toBeTrue()
        ->and($told[0]->named())->toBe('media');
});

it('a name this screen never read is not acted on', function (): void {
    // The half that makes the confirmation mean anything. The agreement is
    // built from the listing rather than from the tap, so a template sending a
    // service that is not on this screen reaches nothing.
    $supervising = AStackThatSupervises::with(aStackRunningTwoThings());
    $screen = theServicesScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Start->value, 'a-service-nobody-listed');

    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->asking())->toBeNull();
});

it('a verb about a name that is blank is not acted on', function (): void {
    $supervising = AStackThatSupervises::with(aStackRunningTwoThings());
    $screen = theServicesScreen($supervising);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value, '   ');

    expect($supervising->whatItWasToldToDo())->toBe([]);
});

it('a verb this app does not have is not acted on either', function (): void {
    $supervising = AStackThatSupervises::with(aStackRunningTwoThings());
    $screen = theServicesScreen($supervising);

    $screen->wouldYouLike('delete', 'sonarr');

    expect($supervising->whatItWasToldToDo())->toBe([]);
});

it('reads the machine again once a verb has been sent', function (): void {
    // The listing in front of the operator is about the machine as it was
    // before they said anything.
    $supervising = AStackThatSupervises::with(aStackRunningTwoThings());
    $screen = theServicesScreen($supervising);

    $screen->answer();
    $screen->wouldYouLike(WhatToDoWithIt::Start->value, 'sonarr');
    $screen->answer();

    // Read, told, read again — the verb is one of the three.
    expect($supervising->askings())->toBe(3);
});

it('N1-R27 — looks again only while something is settling', function (): void {
    $settling = Daemons::of(
        HowTheStackIsRunning::Partial,
        Forms::these(Form::called('downloads')),
        whatTheRunningVerbsCost(),
        aServiceRunning('sonarr', HowAServiceRuns::Starting),
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

it('N1-R17 — a standing listing is not polled', function (): void {
    // Every state but `starting` is a standing answer, so a stack that is not
    // settling answers the same thing however often it is read — and the
    // cadence costs a machine on a home network nothing.
    $supervising = AStackThatSupervises::with(aStackRunningTwoThings());
    $screen = theServicesScreen($supervising);

    $screen->answer();
    $screen->whileItSettles();
    $screen->whileItSettles();

    expect($supervising->askings())->toBe(1);
});

it('N1-R44 — a device with no session for it asks nothing', function (): void {
    $supervising = AStackThatSupervises::with(aStackRunningTwoThings());
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

it('N3-R13 — a credential refused on the verb lets the session go too', function (): void {
    // The half a read cannot reach. A stack that refuses a credential the
    // moment somebody taps stop is the same signed-out device as one that
    // refuses it on a read, and this is the call that happens on the tap.
    //
    // One stack, answering the reading and refusing the verb, because those are
    // different calls and a session can end between them. A second screen over a
    // shared keychain does not reach this: a stack that refuses the read signs
    // the device out on the road the case above already covers, and the fold
    // behind the verb is never entered at all.
    $keychain = AKeychainInMemory::working();
    $supervising = AStackThatSupervises::withButRefusing(
        aStackRunningTwoThings(),
        Obstacle::CredentialWasRefused,
    );
    $screen = theServicesScreen($supervising, $keychain);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value, 'sonarr');
    $screen->agree();

    // Sent, and then refused — the distinction that makes this the verb's half
    // and not the reading's.
    expect($supervising->whatItWasToldToDo())->toHaveCount(1)
        ->and($keychain->isHolding(theStackWhoseServicesAreRead()->id()))->toBeFalse();
});

it('N2-R8 — a question about a form names no single row', function (): void {
    // What `aboutTheService()` is for is the other services a stop disturbs,
    // and only a row carries them. A form has none to name — its services are
    // every row that names it, which the form's own sentence already says — so
    // handing one row back here would put one service's dependants underneath a
    // question about all of them.
    $screen = theServicesScreen(AStackThatSupervises::with(aStackRunningTwoThings()));

    // Nothing pending is the same answer, and for the plainer reason: there is
    // no question for a row to be about.
    expect($screen->aboutTheService())->toBeNull();

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value, 'downloads');

    expect($screen->asking()?->isAboutAForm())->toBeTrue()
        ->and($screen->aboutTheService())->toBeNull();
});

it('N2-R8 — a question about a form stays about the form when a service takes its name', function (): void {
    // Where that guard earns its place: across frames, not within one.
    // `agreementFor()` gives a service priority over a form of the same name,
    // so at the moment the question is asked no row can match it and the guard
    // could be deleted without this suite noticing.
    //
    // What moves is the machine. The operator holds the question open, the poll
    // comes round, and the stack has meanwhile started a service called
    // `downloads`. Without the guard the sentence in front of them would then
    // name one service's dependants underneath a question about the whole form,
    // which is `N2-R8`'s failure exactly: agreeing to one thing while being
    // shown another.
    $screen = theServicesScreen(AStackThatSupervises::thenRunning(
        aStackRunningTwoThings(),
        Daemons::of(
            HowTheStackIsRunning::Active,
            Forms::these(Form::called('downloads')),
            whatTheRunningVerbsCost(),
            aServiceRunning('downloads', leaning: WhatLeansOnIt::these(ServiceId::called('jellyfin'))),
        ),
    ));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value, 'downloads');
    $screen->again();

    expect($screen->asking()?->isAboutAForm())->toBeTrue()
        ->and($screen->aboutTheService())->toBeNull();
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

it('a session that ended between the reading and the yes sends nothing', function (): void {
    // The narrow path `N3-R13` opens: the listing was read while the session
    // worked, and the stack refused it in between. The agreement is still held
    // and there is nothing to send it with, so this must come away quietly
    // rather than raise on a tap — the frame after it is `N1-R44`'s screen,
    // which is where the operator is told.
    $supervising = AStackThatSupervises::with(aStackRunningTwoThings());
    $keychain = AKeychainInMemory::working();
    $screen = theServicesScreen($supervising, $keychain);

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value, 'sonarr');
    $keychain->forget(theStackWhoseServicesAreRead()->id());
    $screen->agree();

    expect($supervising->whatItWasToldToDo())->toBe([])
        ->and($screen->answer()->went->isSignedIn)->toBeFalse();
});

it('refuses a route parameter that is not text', function (): void {
    // A parameter arrives as `mixed`, because the navigation stack's own
    // parameter array is untyped. Anything that is not a string names no stack,
    // which is the same situation as a route with nothing in that segment —
    // asserted rather than assumed, because the narrowing is a branch and a
    // branch nothing drives is a branch that can quietly become the other one.
    // The five other screens with this shape each make this assertion; this was
    // the sixth, and it did not.
    $screen = theServicesScreen(AStackThatSupervises::with(aStackRunningTwoThings()));
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
    $screen = theServicesScreen(AStackThatSupervises::with(aStackRunningTwoThings()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->logsOf(ServiceId::called('sonarr'))))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theServicesScreen(AStackThatSupervises::with(aStackRunningTwoThings()));

    expect($screen->render()->name())->toBe('operator::what-this-stack-runs');
});

it('N2-R8 — the confirmation says how long the verb takes it away for', function (): void {
    // The sentence this screen could not say until lemonfiber reported it, and
    // the number is the stack's: a length worked out here would be a guess at
    // something the stack knows, which is what `N2-R14` refuses.
    $screen = theServicesScreen(AStackThatSupervises::with(aStackRunningTwoThings()));
    $screen->wouldYouLike(WhatToDoWithIt::Stop->value, 'sonarr');

    expect($screen->whatItTakesAway()?->said)->toBe('health.for_at_most')
        ->and($screen->whatItTakesAway()?->seconds)->toBe(10);
});

it('N2-R8 — a stop and a restart are not held to the same clock', function (): void {
    // Two verbs, two numbers, read off the same listing. A screen that stated
    // one length for every verb would be stating a number that nothing honours
    // for every verb but one.
    //
    // A start is not among them, because a start is not asked about at all —
    // which is why the pair compared here is the pair that is.
    $screen = theServicesScreen(AStackThatSupervises::with(aStackRunningTwoThings()));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value, 'sonarr');
    $stopping = $screen->whatItTakesAway()?->seconds;

    $screen->neverMind();
    $screen->wouldYouLike(WhatToDoWithIt::Restart->value, 'sonarr');
    $restarting = $screen->whatItTakesAway()?->seconds;

    expect($stopping)->toBe(10)
        ->and($restarting)->toBe(180);
});

it('N2-R8 — a start states no length, because it is never asked about', function (): void {
    // Not a gap in the reading: the stack reports a length for starting and
    // this screen holds it. There is simply no question to put it on, because
    // a start takes nothing away and confirming one would teach an operator to
    // tap past the confirmations that matter.
    $screen = theServicesScreen(AStackThatSupervises::with(aStackRunningTwoThings()));
    $screen->wouldYouLike(WhatToDoWithIt::Start->value, 'sonarr');

    expect($screen->asking())->toBeNull()
        ->and($screen->whatItTakesAway())->toBeNull();
});

it('N2-R8 — nothing is stated where nothing is being asked', function (): void {
    // Absent because there is no question, not because the stack said nothing.
    // A screen that answered here would be answering about a verb nobody named.
    $screen = theServicesScreen(AStackThatSupervises::with(aStackRunningTwoThings()));

    expect($screen->whatItTakesAway())->toBeNull();
});

it('N2-R8 — states no length once the reading it came from is gone', function (): void {
    // The window a screen holding a question across frames actually meets: the
    // listing was read, somebody tapped, and by the next frame the stack is not
    // answering. The question survives and the lengths do not, so a screen
    // deriving one from both has to say nothing rather than reach into a
    // reading that is no longer there.
    //
    // `N2-R14` is why it says nothing rather than falling back: a length this
    // side invented would be a guess at something only the stack knows, offered
    // at the exact moment the stack has stopped saying anything.
    $screen = theServicesScreen(AStackThatSupervises::thenMeeting(
        aStackRunningTwoThings(),
        Obstacle::StackDidNotAnswer,
    ));

    $screen->wouldYouLike(WhatToDoWithIt::Stop->value, 'sonarr');
    $screen->again();

    expect($screen->asking())->not->toBeNull()
        ->and($screen->whatItTakesAway())->toBeNull();
});

it('N2-R7 — a row carries only the verbs its own state can take', function (): void {
    // The narrowing is the row's whole contribution here: the decision belongs
    // to the state, and this asserts the fold asked it rather than handing the
    // template all three and leaving a stopped service offered a stop. Read as
    // *which verbs*, so a fold that stopped narrowing names one too many rather
    // than passing on a list nobody compared.
    $screen = theServicesScreen(AStackThatSupervises::with(Daemons::of(
        HowTheStackIsRunning::Active,
        Forms::these(Form::called('downloads')),
        whatTheRunningVerbsCost(),
        aServiceRunning('sonarr', HowAServiceRuns::Stopped),
        aServiceRunning('jellyfin'),
    )));

    [$stopped, $running] = $screen->answer()->services;

    expect($stopped->verbs)->toBe([WhatToDoWithIt::Start])
        ->and($running->verbs)->toBe([WhatToDoWithIt::Stop, WhatToDoWithIt::Restart]);
});
