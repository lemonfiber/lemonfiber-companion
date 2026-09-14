<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Daemon;
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
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatLeansOnIt;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Operator\Internal\AStacksScreen;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\StacksInMemory;

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
        ->and($answer->isSignedIn)->toBeTrue()
        ->and($answer->met)->toBe('')
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
        aServiceRunning('sonarr', HowAServiceRuns::Starting),
    );
    $screen = theServicesScreen(AStackThatSupervises::with($settling));

    expect($screen->answer()->isSettling)->toBeTrue();

    $screen->whileItSettles();

    // Twice: the first reading, and the one the cadence asked for.
    expect($screen->answer())->not->toBeNull()
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

    expect($screen->answer()->isSignedIn)->toBeFalse()
        ->and($screen->answer()->services)->toBe([])
        ->and($supervising->askings())->toBe(0);
});

it('N1-R10 — an obstacle is what stood in the way, with what to do about it', function (): void {
    $screen = theServicesScreen(AStackThatSupervises::met(Obstacle::DeviceHasNoNetwork));
    $answer = $screen->answer();

    expect($answer->met)->toBe(Obstacle::DeviceHasNoNetwork->said())
        ->and($answer->remedy)->toBe(Obstacle::DeviceHasNoNetwork->remedy())
        // Signed in, and the listing is empty because nothing was read — not
        // because the machine is running nothing.
        ->and($answer->isSignedIn)->toBeTrue()
        ->and($answer->services)->toBe([]);
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
        ->and($screen->answer()->isSignedIn)->toBeTrue()
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
        ->and($screen->answer()->isSignedIn)->toBeFalse();
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
