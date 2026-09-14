<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAServiceTookIt;
use Modules\Kernel\Api\HowCurrent;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowToUndoIt;
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
use Modules\Kernel\Api\TakingAnUpdate;
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
        whatLastNightCameTo(),
    );
}

/**
 * What became of the last update: one service back, one that would not start.
 *
 * Two endings and not one, because `N2-R18` is the rule that they stay told
 * apart — and two ways back, because `N2-R19` is the rule that a rollback and a
 * restore are not one offer.
 */
function whatLastNightCameTo(): HowServicesTookIt
{
    return HowServicesTookIt::these(
        HowAServiceTookIt::of(ServiceId::called('jellyfin'), HowItEnded::Updated, HowToUndoIt::Rollback),
        HowAServiceTookIt::of(ServiceId::called('sonarr'), HowItEnded::NotStarted, HowToUndoIt::Restore),
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
        HowServicesTookIt::none(),
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
        HowServicesTookIt::none(),
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

it('N2-R17 — asks before it takes one, and names what it would change', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()));

    $screen->wouldYouLike('4.1.0');
    $asking = $screen->asking();

    expect($asking)->toBeInstanceOf(TakingAnUpdate::class)
        ->and($asking?->release()->version())->toBe('4.1.0')
        // Both services, and the count the confirmation leads with. A question
        // that named the release and not the evening would be asking somebody
        // to agree to the half they can already see.
        ->and($screen->wouldChange())->toBe(2);
});

it('N2-R17 — will not be talked into a release it never showed', function (): void {
    // The version arrives as a string because a template can hand over nothing
    // else, so the screen matches it against what it read rather than trusting
    // it. A withdrawn release is the case that matters: `N2-R16` keeps it off
    // the list, and this keeps it off the list of things that can be agreed to.
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()));

    $screen->wouldYouLike('9.9.9');

    expect($screen->asking())->toBeNull()
        ->and($screen->wouldChange())->toBe(0);
});

it('N2-R17 — sends what was agreed to, and nothing where nothing was', function (): void {
    $keeping = AStackThatKeepsCurrent::with(anEveningWorthSpending());
    $screen = theUpkeepScreen($keeping);

    // Nothing has been asked, so agreeing is not a yes to whatever is nearest.
    $screen->agree();

    expect($keeping->taken())->toBe([]);

    $screen->wouldYouLike('4.1.0');
    $screen->agree();

    expect($keeping->taken())->toHaveCount(1)
        ->and($keeping->taken()[0]->release()->version())->toBe('4.1.0')
        ->and($keeping->taken()[0]->changing()->count())->toBe(2)
        // The question is gone once it is answered, so a second frame does not
        // redraw a confirmation for an evening that has already started.
        ->and($screen->asking())->toBeNull();
});

it('N2-R17 — forgets what it read once an update is taken', function (): void {
    // The listing after an update is a different listing. A screen that kept
    // the old one would show an evening that has already happened.
    $keeping = AStackThatKeepsCurrent::with(anEveningWorthSpending());
    $screen = theUpkeepScreen($keeping);

    $screen->answer();
    $screen->wouldYouLike('4.1.0');
    $screen->agree();
    $screen->answer();

    expect($keeping->askings())->toBe(2);
});

it('N2-R17 — leaving it sends nothing', function (): void {
    $keeping = AStackThatKeepsCurrent::with(anEveningWorthSpending());
    $screen = theUpkeepScreen($keeping);

    $screen->wouldYouLike('4.1.0');
    $screen->neverMind();

    expect($screen->asking())->toBeNull()
        ->and($keeping->taken())->toBe([]);
});

it('N1-R44 — a yes on a phone whose session ended sends nothing', function (): void {
    // The window this covers is real: the screen was drawn while the session
    // worked, and the session ended between the reading and the tap. Sending
    // anyway would be the app using a credential it no longer holds, and a
    // screen that reported success would be reporting an evening that did not
    // happen.
    $keeping = AStackThatKeepsCurrent::with(anEveningWorthSpending());
    $keychain = AKeychainInMemory::working();
    $stack = theStackWhoseUpkeepIsRead();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'));

    $screen = new HowCurrentThisStackIs($keeping, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    $screen->wouldYouLike('4.1.0');
    $keychain->forget($stack->id());
    $screen->agree();

    expect($keeping->taken())->toBe([]);
});

it('N2-R18 — says what became of each service the last update touched', function (): void {
    $applied = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()))->answer()->applied;

    expect($applied)->toHaveCount(2)
        ->and($applied[0]->service)->toBe('jellyfin')
        ->and($applied[0]->endingSaid)->toBe(HowItEnded::Updated->saidOnTheScreen())
        ->and($applied[0]->arrived)->toBeTrue()
        ->and($applied[1]->service)->toBe('sonarr')
        // Not a failure. The row says the image arrived and the service would
        // not come back up on it, which sends somebody to its own log rather
        // than to the machine.
        ->and($applied[1]->endingSaid)->toBe(HowItEnded::NotStarted->saidOnTheScreen())
        ->and($applied[1]->arrived)->toBeFalse();
});

it('N2-R19 — says which way back, and whether it brings the data with it', function (): void {
    $applied = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()))->answer()->applied;

    expect($applied[0]->undoSaid)->toBe(HowToUndoIt::Rollback->saidOnTheScreen())
        ->and($applied[0]->undoCarriesTheDataWithIt)->toBeFalse()
        // A restore is the larger promise and the different conversation: it
        // puts the snapshot back, and the evening's data with it.
        ->and($applied[1]->undoSaid)->toBe(HowToUndoIt::Restore->saidOnTheScreen())
        ->and($applied[1]->undoCarriesTheDataWithIt)->toBeTrue();
});

it('N2-R18 — counts what is not where the operator wanted it, apart from what is unknown', function (): void {
    $answer = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()))->answer();

    expect($answer->didNotArrive)->toBe(1)
        // `not-started` is something the stack knows. Unanswered is something
        // it does not, and a headline reading them as one would offer a remedy
        // for a situation it is guessing at.
        ->and($answer->anythingUnanswered)->toBeFalse();
});

it('N2-R18 — says when the stack cannot tell what a service is doing', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(Upkeep::reported(
        HowCurrent::Current,
        Releases::none(),
        Services::none(),
        HowServicesTookIt::these(
            HowAServiceTookIt::of(ServiceId::called('radarr'), HowItEnded::NotReached, HowToUndoIt::Rollback),
        ),
    )));

    expect($screen->answer()->anythingUnanswered)->toBeTrue()
        ->and($screen->answer()->didNotArrive)->toBe(1);
});

it('N2-R18 — a stack that has taken no update says so rather than showing a blank', function (): void {
    $answer = theUpkeepScreen(AStackThatKeepsCurrent::withNothingWaiting())->answer();

    expect($answer->applied)->toBe([])
        ->and($answer->didNotArrive)->toBe(0)
        ->and($answer->anythingUnanswered)->toBeFalse();
});

it('N1-R46 — an obstacle meaning the session ended renders the sign-in', function (): void {
    // The fold's own branch rather than the screen's: *your session ended, sign
    // in again* and *the stack refused that credential* send an operator to two
    // different places, and only one of them offers a way back in.
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::met(Obstacle::CredentialWasRefused));

    expect($screen->answer()->isSignedIn)->toBeFalse()
        ->and($screen->answer()->met)->toBe('');
});

it('reaches this machine\'s other screens', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::withNothingWaiting());

    expect($screen->goes()->signIn())->toContain(theStackWhoseUpkeepIsRead()->id()->stored());
});

it('N2-R20 — offers taking one only where the stack said there is one', function (): void {
    // Asked of the reading rather than worked out from the list. A stack that
    // lists releases while calling itself current is the case this exists for,
    // and a screen counting rows would offer an update to it.
    $waiting = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()));
    $current = theUpkeepScreen(AStackThatKeepsCurrent::with(Upkeep::reported(
        HowCurrent::Current,
        Releases::these(Release::called('4.1.0', noticeable: true, withdrawn: false)),
        Services::none(),
        HowServicesTookIt::none(),
    )));

    expect($waiting->answer()->canTakeOne)->toBeTrue()
        // A release is listed and the stack says it is up to date, so there is
        // a row and nothing to offer on it.
        ->and($current->answer()->canTakeOne)->toBeFalse()
        ->and($current->howMany())->toBe(1);
});

it('renders the template it is paired with', function (): void {
    // Named rather than drawn. `EveryTemplateCallsMethodsItsScreenHasTest`
    // reads this pairing out of the source to check every method a template
    // calls; this runs the method, so a screen that named a view nobody wrote
    // fails here rather than on a phone.
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()));

    expect($screen->render()->name())->toBe('operator::how-current-this-stack-is');
});
