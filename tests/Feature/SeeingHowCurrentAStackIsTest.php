<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowCurrent;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Upkeep;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\Fakes\StacksInMemory;

// N2-R15 — where a stack stands on being up to date is reachable.
//
// The screen that answers the question an operator asks themselves on a sofa:
// *is there an update, and is tonight the night*. Not a version string to
// compare — the decision is whether anybody in the house will notice, and
// whether the evening is worth spending.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application — `view()` and `__()` are not there in a
// module suite.

/** The machine whose upkeep this screen is about. */
function theStackWhoseUpkeepIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('g', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('a', Fingerprint::CHARACTERS)),
    );
}

/** An update waiting, with one release nobody will notice and one they will. */
function anEveningWorthSpending(): Upkeep
{
    return Upkeep::runningOn(
        HowCurrent::Pending,
        Release::called('4.0.15', noticeable: false, withdrawn: false),
        Releases::these(
            Release::called('4.1.0', noticeable: true, withdrawn: false),
            Release::called('4.0.16', noticeable: false, withdrawn: false),
        ),
        Services::these(ServiceId::called('jellyfin'), ServiceId::called('sonarr')),
    );
}

/**
 * The screen, with a stack it knows and a keychain holding whatever a test says.
 * Named for this file, since the root suites share one namespace (`G10`).
 */
function theUpkeepScreen(
    AStackThatKeepsCurrent $keeping,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): HowCurrentThisStackIs {
    $stack = theStackWhoseUpkeepIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'));
    }

    $screen = new HowCurrentThisStackIs($keeping, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('N2-R15 — opens on the stack\'s own answer rather than on a version to compare', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()));

    expect($screen->answer()->howSaid)->toBe(HowCurrent::Pending->saidOnTheScreen())
        ->and($screen->answer()->running)->toBe('4.0.15')
        // A stack that answered is not a session that ended. `isSignedIn` is
        // the template's first branch, so a fold reporting otherwise would put
        // the sign-in prompt in front of an operator whose session is working.
        ->and($screen->answer()->isSignedIn)->toBeTrue()
        ->and($screen->answer()->met)->toBe('');
});

it('N2-R16 — says which releases the household would notice', function (): void {
    // The distinction that makes this a decision rather than a chore. Both rows
    // are checked, because a screen that marked everything noticeable and one
    // that marked nothing would each pass a test that only looked at one.
    $waiting = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()))->answer()->waiting;

    expect($waiting)->toHaveCount(2)
        ->and($waiting[0]->version)->toBe('4.1.0')
        ->and($waiting[0]->theHouseholdWouldNotice)->toBeTrue()
        ->and($waiting[1]->version)->toBe('4.0.16')
        ->and($waiting[1]->theHouseholdWouldNotice)->toBeFalse();
});

it('N2-R16 — leaves a withdrawn release out of what is offered', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(Upkeep::reported(
        HowCurrent::Pending,
        Releases::these(
            Release::called('4.1.0', noticeable: true, withdrawn: false),
            Release::called('4.0.17', noticeable: true, withdrawn: true),
        ),
        Services::these(ServiceId::called('jellyfin')),
    )));

    expect($screen->howMany())->toBe(1)
        ->and($screen->answer()->waiting[0]->version)->toBe('4.1.0');
});

it('N2-R16 — says so where the stack is running one that was taken back', function (): void {
    // The opposite errand from the rule above, and the reason a withdrawn
    // release is carried rather than filtered upstream: dropping it from both
    // would leave an operator reading a screen that says nothing is wrong.
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(Upkeep::runningOn(
        HowCurrent::Pending,
        Release::called('4.0.17', noticeable: true, withdrawn: true),
        Releases::none(),
        Services::none(),
    )));

    expect($screen->answer()->runningWasWithdrawn)->toBeTrue()
        ->and($screen->answer()->running)->toBe('4.0.17');
});

it('N2-R20 — offers nothing where the stack reported it is current', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::withNothingWaiting());

    expect($screen->howMany())->toBe(0)
        ->and($screen->answer()->howSaid)->toBe(HowCurrent::Current->saidOnTheScreen());
});

it('N1-R44 — a session that has ended is a sign-in rather than an obstacle', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::withNothingWaiting(), signedIn: false);

    expect($screen->answer()->isSignedIn)->toBeFalse()
        // Neither of the obstacle's keys, because nothing was met: the app did
        // not get as far as asking.
        ->and($screen->answer()->met)->toBe('');
});

it('N1-R10 — an obstacle carries what stood in the way and what to do about it', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::met(Obstacle::StackDidNotAnswer));

    expect($screen->answer()->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($screen->answer()->remedy)->toBe(Obstacle::StackDidNotAnswer->remedy())
        ->and($screen->answer()->isSignedIn)->toBeTrue();
});

it('N1-R17 — asks once per frame, however many fields the template reads', function (): void {
    $keeping = AStackThatKeepsCurrent::with(anEveningWorthSpending());
    $screen = theUpkeepScreen($keeping);

    $screen->answer();
    $screen->answer();
    $screen->howMany();

    expect($keeping->askings())->toBe(1);
});

it('N1-R3 — asking again is offered, and asks again', function (): void {
    $keeping = AStackThatKeepsCurrent::with(anEveningWorthSpending());
    $screen = theUpkeepScreen($keeping);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($keeping->askings())->toBe(2);
});
