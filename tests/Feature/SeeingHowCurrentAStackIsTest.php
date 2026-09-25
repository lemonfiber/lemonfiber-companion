<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAServiceTookIt;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheUpdateIsGoing;
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
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\ViewModels\WhatTheStackIsOn;
use Modules\Sdk\Api\Standings;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheContractAccepts;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatTheKeychainStillHolds;

// Where a stack stands on being up to date is reachable.
//
// The screen that answers the question an operator asks themselves on a sofa:
// *is there an update, and is tonight the night*. Not a version string to
// compare — whether there is one is the stack's answer off its pins, and the
// decision is whether anybody in the house will notice.
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

/** The same evening, where one of the two services will not come back as it was. */
function anEveningWithSomethingPermanentInIt(): Upkeep
{
    return Upkeep::runningOn(
        AgainstThePins::UpdatesAvailable,
        Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()),
        Releases::these(Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing())),
        Services::these(ServiceId::called('jellyfin'), ServiceId::called('sonarr')),
        // One of the two, deliberately. A fixture where every service is
        // permanent would pass a screen that drew the warning over the whole
        // evening, which is the thing that must not happen.
        Services::these(ServiceId::called('sonarr')),
        whatLastNightCameTo(),
    );
}

/**
 * An update waiting: two services behind their pins, and the release carrying
 * those pins one the household will notice. Its history holds that release and
 * one before it nobody noticed.
 */
function anEveningWorthSpending(): Upkeep
{
    return Upkeep::runningOn(
        AgainstThePins::UpdatesAvailable,
        Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::said('Adds series search.')),
        Releases::these(
            Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::said('Adds series search.')),
            Release::called('4.0.16', noticeable: false, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()),
        ),
        Services::these(ServiceId::called('jellyfin'), ServiceId::called('sonarr')),
        Services::none(),
        whatLastNightCameTo(),
    );
}

/**
 * What became of the last update: one service back, one that would not start.
 *
 * Two endings and not one, because the rule is that they stay told
 * apart — and two ways back, because a rollback and a
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
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new HowCurrentThisStackIs($keeping, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** The update's own report, finished, saying this of each service it touched. */
function aReportSaying(HowServicesTookIt $went): HowTheUpdateIsGoing
{
    return HowTheUpdateIsGoing::done(Upkeep::reported(AgainstThePins::Current, Releases::none(), Services::none(), Services::none(), $went));
}

/** The screen once 4.1.0 has been taken and the cadence has asked after it once. */
function aScreenThatTookTheUpdate(AStackThatKeepsCurrent $keeping, ?AKeychainInMemory $keychain = null): HowCurrentThisStackIs
{
    $screen = theUpkeepScreen($keeping, $keychain);
    $screen->wouldYouLike();
    $screen->agree();
    $screen->whileItRuns();

    return $screen;
}

it('N2-R15 — opens on the stack\'s own answer rather than on a version to compare', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()));

    expect($screen->answer()->pinsSaid)->toBe(AgainstThePins::UpdatesAvailable->saidOnTheScreen())
        ->and($screen->answer()->running)->toBe('4.1.0')
        // A stack that answered is not a session that ended. `isSignedIn` is
        // the template's first branch, so a fold reporting otherwise would put
        // the sign-in prompt in front of an operator whose session is working.
        ->and($screen->answer()->went->isSignedIn)->toBeTrue()
        ->and($screen->answer()->went->met)->toBe('');
});

it('N2-R16 — says which releases the household would notice', function (): void {
    // The distinction that makes this a decision rather than a chore. Both rows
    // are checked, because a screen that marked everything noticeable and one
    // that marked nothing would each pass a test that only looked at one.
    $history = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()))->answer()->history;

    expect($history)->toHaveCount(2)
        ->and($history[0]->version)->toBe('4.1.0')
        ->and($history[0]->theHouseholdWouldNotice)->toBeTrue()
        ->and($history[1]->version)->toBe('4.0.16')
        ->and($history[1]->theHouseholdWouldNotice)->toBeFalse();
});

it('marks a withdrawn release in the history rather than dropping it', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(Upkeep::reported(
        AgainstThePins::Current,
        Releases::these(
            Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()),
            Release::called('4.0.17', noticeable: true, withdrawn: true, delivers: WhatAReleaseDelivers::saidNothing()),
        ),
        Services::none(),
        Services::none(),
        HowServicesTookIt::none(),
    )));

    $history = $screen->answer()->history;

    expect($history)->toHaveCount(2)
        ->and($history[1]->version)->toBe('4.0.17')
        ->and($history[1]->wasWithdrawn)->toBeTrue()
        ->and($history[0]->wasWithdrawn)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('updates.withdrawn'));
});

it('N2-R16 — says so where the stack is running one that was taken back', function (): void {
    // The opposite errand from the rule above, and the reason a withdrawn
    // release is carried rather than filtered upstream: dropping it from both
    // would leave an operator reading a screen that says nothing is wrong.
    // And no update is offered onto the pins that release carries, though
    // the stack says a service would move: taking them is taking the release.
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(Upkeep::runningOn(
        AgainstThePins::UpdatesAvailable,
        Release::called('4.0.17', noticeable: true, withdrawn: true, delivers: WhatAReleaseDelivers::saidNothing()),
        Releases::none(),
        Services::these(ServiceId::called('jellyfin')),
        Services::none(),
        HowServicesTookIt::none(),
    )));

    expect($screen->answer()->runningWasWithdrawn)->toBeTrue()
        ->and($screen->answer()->running)->toBe('4.0.17')
        ->and($screen->answer()->offer)->toBeNull();
});

it('N2-R20 — offers nothing where the stack reported it is current', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::withNothingWaiting());

    expect($screen->answer()->offer)->toBeNull()
        ->and($screen->answer()->pinsSaid)->toBe(AgainstThePins::Current->saidOnTheScreen());
});

it('N1-R44 — a session that has ended is a sign-in rather than an obstacle', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::withNothingWaiting(), signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        // Neither of the obstacle's keys, because nothing was met: the app did
        // not get as far as asking.
        ->and($screen->answer()->went->met)->toBe('');
});

it('N1-R10 — an obstacle carries what stood in the way and what to do about it', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::met(Obstacle::StackDidNotAnswer));

    expect($screen->answer()->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($screen->answer()->went->remedy)->toBe(Obstacle::StackDidNotAnswer->remedy())
        ->and($screen->answer()->went->isSignedIn)->toBeTrue();
});

it('N1-R65 — asks once per frame, however many fields the template reads', function (): void {
    $keeping = AStackThatKeepsCurrent::with(anEveningWorthSpending());
    $screen = theUpkeepScreen($keeping);

    $screen->answer();
    $screen->answer();
    $screen->wouldYouLike();

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

    $screen->wouldYouLike();
    $asking = $screen->asking();

    expect($asking)->toBeInstanceOf(TakingAnUpdate::class)
        // Both services, and the count the confirmation leads with.
        ->and($screen->wouldChange())->toBe(2)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())
        ->toContain('jellyfin', 'sonarr', trans_choice('updates.would_change', 2));
});

it('asking again where nothing is offered leaves the update already being asked about as it was', function (): void {
    $held = TakingAnUpdate::offeredBy(anEveningWorthSpending());
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(Upkeep::reported(
        AgainstThePins::Current,
        Releases::these(Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing())),
        Services::none(),
        Services::none(),
        HowServicesTookIt::none(),
    )));
    $screen->asking = $held;

    $screen->wouldYouLike();

    expect($screen->asking())->toBe($held);
});

it('N2-R22 — the confirmation names what taking it will not put back', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWithSomethingPermanentInIt()));

    $screen->wouldYouLike();
    $asking = $screen->asking();

    $named = [];

    foreach ($asking?->cannotBePutBack() ?? Services::none() as $service) {
        $named[] = $service->named();
    }

    // One of the two, and the evening is still two. A warning drawn over
    // everything would be as easily disbelieved as the one drawn over nothing.
    expect($asking?->cannotBeWhollyUndone())->toBeTrue()
        ->and($named)->toBe(['sonarr'])
        ->and($screen->cannotBePutBack())->toBe(1)
        ->and($screen->wouldChange())->toBe(2);
});

it('N2-R22 — counts nothing permanent where nothing has been asked yet', function (): void {
    // Before the offer is taken up there is no confirmation, so there is nothing
    // it would not put back. Asserted rather than left to the accessor's
    // fall-through: a template asking this on every frame asks it first on the
    // frame where the answer is nobody's yet.
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWithSomethingPermanentInIt()));

    expect($screen->asking())->toBeNull()
        ->and($screen->cannotBePutBack())->toBe(0)
        ->and($screen->wouldChange())->toBe(0);
});

it('N2-R22 — says so before the yes, and not after it', function (): void {
    // Rendered from the pending question rather than from the reading, which
    // is what puts it in front of somebody who has not agreed yet. Asked of
    // the drawn screen, so a template that read the field and drew nothing
    // fails here rather than passing on the accessor alone.
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWithSomethingPermanentInIt()));

    $screen->wouldYouLike();

    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain('sonarr')
        ->and($drawn)->toContain(__('updates.cannot_be_put_back_after'));
});

it('N2-R22 — an evening that can be undone says nothing about permanence', function (): void {
    // The ordinary case, and the one a warning must not leak into: a screen
    // drawing the sentence whenever there is a confirmation would teach an
    // operator to read past it by the third update.
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()));

    $screen->wouldYouLike();

    expect($screen->asking()?->cannotBeWhollyUndone())->toBeFalse()
        ->and($screen->cannotBePutBack())->toBe(0)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())
        ->not->toContain(__('updates.cannot_be_put_back_after'));
});

it('asks nothing where the stack offered nothing', function (): void {
    // The offer comes from the reading, so a tap reaching this on a stack whose
    // services are on their pins has nothing to ask about.
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::withNothingWaiting());

    $screen->wouldYouLike();

    expect($screen->asking())->toBeNull()
        ->and($screen->wouldChange())->toBe(0);
});

it('N2-R17 — sends what was agreed to, and nothing where nothing was', function (): void {
    $keeping = AStackThatKeepsCurrent::with(anEveningWorthSpending());
    $screen = theUpkeepScreen($keeping);

    // Nothing has been asked, so agreeing is not a yes to whatever is nearest.
    $screen->agree();

    expect($keeping->taken())->toBe([]);

    $screen->wouldYouLike();
    $screen->agree();

    expect($keeping->taken())->toHaveCount(1)
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
    $screen->wouldYouLike();
    $screen->agree();
    $screen->answer();

    expect($keeping->askings())->toBe(2);
});

it('N2-R17 — leaving it sends nothing', function (): void {
    $keeping = AStackThatKeepsCurrent::with(anEveningWorthSpending());
    $screen = theUpkeepScreen($keeping);

    $screen->wouldYouLike();
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
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new HowCurrentThisStackIs($keeping, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    $screen->wouldYouLike();
    $keychain->forget($stack->id());
    $screen->agree();

    expect($keeping->taken())->toBe([]);
});

it('N2-R18 — says what became of each service the update taken here touched', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(anEveningWorthSpending(), aReportSaying(whatLastNightCameTo()));
    $applied = aScreenThatTookTheUpdate($keeping)->lastUpdate()->applied;

    // What did not arrive comes first, which is `NotArrivedFirst`'s doing and
    // is asserted on its own beside the other ordering cases. Read here in the
    // order the template draws them.
    expect($applied)->toHaveCount(2)
        ->and($applied[0]->service)->toBe('sonarr')
        // Not a failure. The row says the image arrived and the service would
        // not come back up on it, which sends somebody to its own log rather
        // than to the machine.
        ->and($applied[0]->endingSaid)->toBe(HowItEnded::NotStarted->saidOnTheScreen())
        ->and($applied[0]->arrived)->toBeFalse()
        ->and($applied[1]->service)->toBe('jellyfin')
        ->and($applied[1]->endingSaid)->toBe(HowItEnded::Updated->saidOnTheScreen())
        ->and($applied[1]->arrived)->toBeTrue()
        ->and($keeping->followed())->toHaveCount(1)
        ->and($keeping->followed()[0]->shown())->toBe(AStackThatKeepsCurrent::THE_JOB);
});

it('N2-R19 — says which way back, and whether it brings the data with it', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(anEveningWorthSpending(), aReportSaying(whatLastNightCameTo()));
    $applied = aScreenThatTookTheUpdate($keeping)->lastUpdate()->applied;

    // A restore is the larger promise and the different conversation: it puts
    // the snapshot back, and the evening's data with it. It leads because the
    // service it belongs to is the one that did not arrive.
    expect($applied[0]->undoSaid)->toBe(HowToUndoIt::Restore->saidOnTheScreen())
        ->and($applied[0]->undoCarriesTheDataWithIt)->toBeTrue()
        ->and($applied[1]->undoSaid)->toBe(HowToUndoIt::Rollback->saidOnTheScreen())
        ->and($applied[1]->undoCarriesTheDataWithIt)->toBeFalse();
});

it('N2-R18 — counts what is not where the operator wanted it, apart from what is unknown', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(anEveningWorthSpending(), aReportSaying(whatLastNightCameTo()));
    $last = aScreenThatTookTheUpdate($keeping)->lastUpdate();

    expect($last->didNotArrive)->toBe(1)
        // `not-started` is something the stack knows. Unanswered is something
        // it does not, and a headline reading them as one would offer a remedy
        // for a situation it is guessing at.
        ->and($last->anythingUnanswered)->toBeFalse()
        ->and($last->wasTaken)->toBeTrue()
        ->and($last->isWorking)->toBeFalse()
        ->and($last->hasEnded)->toBeFalse();
});

it('N2-R18 — says when the stack cannot tell what a service is doing', function (): void {
    $last = aScreenThatTookTheUpdate(AStackThatKeepsCurrent::whichTook(anEveningWorthSpending(), aReportSaying(HowServicesTookIt::these(
        HowAServiceTookIt::of(ServiceId::called('radarr'), HowItEnded::NotReached, HowToUndoIt::Rollback),
    ))))->lastUpdate();

    expect($last->anythingUnanswered)->toBeTrue()
        ->and($last->didNotArrive)->toBe(1);
});

it('N2-R18 — a screen that has taken no update says so, and asks after nothing', function (): void {
    $keeping = AStackThatKeepsCurrent::withNothingWaiting();
    $screen = theUpkeepScreen($keeping);
    $last = $screen->lastUpdate();

    expect($last->wasTaken)->toBeFalse()
        ->and($last->went->cameBack())->toBeTrue()
        ->and($last->applied)->toBe([])
        ->and($keeping->followed())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('updates.nothing_applied'));
});

it('N2-R18 — an update just taken is running, and the screen says how often it asks after it', function (): void {
    $keeping = AStackThatKeepsCurrent::with(anEveningWorthSpending());
    $screen = theUpkeepScreen($keeping);
    $screen->wouldYouLike();
    $screen->agree();

    expect($screen->lastUpdate()->isWorking)->toBeTrue()
        ->and($keeping->followed())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())
        ->toContain(__('updates.still_updating'))
        ->toContain(__($screen->cadence()->saidOnTheScreen(), ['count' => $screen->cadence()->seconds()]));
});

it('N2-R18 — asks after an update again only while it runs', function (): void {
    $running = AStackThatKeepsCurrent::with(anEveningWorthSpending());
    // Each tick while it runs forgets the answer, and the next one asks.
    $screen = aScreenThatTookTheUpdate($running);
    $screen->whileItRuns();
    $screen->whileItRuns();

    $finished = AStackThatKeepsCurrent::whichTook(anEveningWorthSpending(), aReportSaying(whatLastNightCameTo()));
    $done = aScreenThatTookTheUpdate($finished);
    $done->whileItRuns();
    $done->whileItRuns();

    expect($running->followed())->toHaveCount(2)
        ->and($finished->followed())->toHaveCount(1);
});

it('N2-R18 — asking again asks after the update again', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(anEveningWorthSpending(), aReportSaying(whatLastNightCameTo()));
    $screen = aScreenThatTookTheUpdate($keeping);
    $screen->lastUpdate();
    $screen->again();
    $screen->lastUpdate();

    expect($keeping->followed())->toHaveCount(2);
});

it('N2-R18 — draws the report of a finished update in place of the sentence saying none was taken', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(anEveningWorthSpending(), aReportSaying(whatLastNightCameTo()));
    $drawn = WhatTheDeviceWouldDraw::by(aScreenThatTookTheUpdate($keeping))->said();

    expect($drawn)->toContain('sonarr')
        ->toContain(__(HowItEnded::NotStarted->saidOnTheScreen()))
        ->toContain(__(HowToUndoIt::Restore->saidOnTheScreen()))
        ->toContain(trans_choice('updates.did_not_arrive', 1));
    expect($drawn)->not->toContain(__('updates.nothing_applied'));
    expect($drawn)->not->toContain(__('updates.still_updating'));
});

it('N2-R18 — a finished update that touched no service says so', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(anEveningWorthSpending(), aReportSaying(HowServicesTookIt::none()));

    expect(WhatTheDeviceWouldDraw::by(aScreenThatTookTheUpdate($keeping))->said())->toContain(__('updates.touched_nothing'));
});

it('N2-R18 — an update the stack has no outcome for is said to be that, not a failure', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(anEveningWorthSpending(), HowTheUpdateIsGoing::ended());
    $screen = aScreenThatTookTheUpdate($keeping);

    expect($screen->lastUpdate()->hasEnded)->toBeTrue()
        ->and($screen->lastUpdate()->applied)->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('updates.no_outcome'));
});

it('N1-R10 — a take the stack refused says what stood in the way, and follows nothing', function (): void {
    $keeping = AStackThatKeepsCurrent::withButRefusing(anEveningWorthSpending(), Obstacle::StackDidNotAnswer);
    $screen = aScreenThatTookTheUpdate($keeping);

    expect($screen->lastUpdate()->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($screen->lastUpdate()->isWorking)->toBeFalse()
        ->and($keeping->taken())->toHaveCount(1)
        ->and($keeping->followed())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(Obstacle::StackDidNotAnswer->said()));
});

it('N1-R10 — asking after an update says what stood in the way', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(anEveningWorthSpending(), HowTheUpdateIsGoing::met(Obstacle::StackDidNotAnswer));

    expect(aScreenThatTookTheUpdate($keeping)->lastUpdate()->went->met)->toBe(Obstacle::StackDidNotAnswer->said());
});

it('N1-R46 — lets go of a session the stack refused while taking or following an update', function (): void {
    $refusingTheTake = AKeychainInMemory::working();
    aScreenThatTookTheUpdate(AStackThatKeepsCurrent::withButRefusing(anEveningWorthSpending(), Obstacle::CredentialWasRefused), $refusingTheTake);

    $refusingTheQuestion = AKeychainInMemory::working();
    aScreenThatTookTheUpdate(
        AStackThatKeepsCurrent::whichTook(anEveningWorthSpending(), HowTheUpdateIsGoing::met(Obstacle::CredentialWasRefused)),
        $refusingTheQuestion,
    )->lastUpdate();

    expect($refusingTheTake->isHolding(theStackWhoseUpkeepIsRead()->id()))->toBeFalse()
        ->and($refusingTheQuestion->isHolding(theStackWhoseUpkeepIsRead()->id()))->toBeFalse();
});

it('N1-R46 — an obstacle meaning the session ended renders the sign-in', function (): void {
    // The fold's own branch rather than the screen's: *your session ended, sign
    // in again* and *the stack refused that credential* send an operator to two
    // different places, and only one of them offers a way back in.
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::met(Obstacle::CredentialWasRefused));

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->met)->toBe('');
});

it('reaches this machine\'s other screens', function (): void {
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::withNothingWaiting());

    expect($screen->goes()->signIn())->toContain(theStackWhoseUpkeepIsRead()->id()->stored());
});

it('N2-R20 — offers taking one only where the stack said there is one', function (): void {
    // Asked of the reading rather than worked out from the history. Releases
    // are listed whatever the pins say, so a screen counting rows would offer
    // an update to every stack that has a history.
    $waiting = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()));
    $current = theUpkeepScreen(AStackThatKeepsCurrent::with(Upkeep::reported(
        AgainstThePins::Current,
        Releases::these(Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing())),
        Services::these(ServiceId::called('jellyfin')),
        Services::none(),
        HowServicesTookIt::none(),
    )));

    expect($waiting->answer()->offer)->toBeInstanceOf(TakingAnUpdate::class)
        // A release is listed and the stack says every service is on its pin,
        // so there is a row and nothing to offer.
        ->and($current->answer()->offer)->toBeNull()
        ->and($current->answer()->history)->toHaveCount(1);
});

it('renders the template it is paired with', function (): void {
    // Named rather than drawn. `EveryTemplateCallsMethodsItsScreenHasTest`
    // reads this pairing out of the source to check every method a template
    // calls; this runs the method, so a screen that named a view nobody wrote
    // fails here rather than on a phone.
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()));

    expect($screen->render()->name())->toBe('operator::how-current-this-stack-is');
});


it('N1-R46 — lets go of a session the stack refused', function (): void {
    // Not only reported. A phone holding a credential the stack has refused
    // would go on offering to ask again with it, and every ask would fail the
    // same way — so the session is dropped and the next frame offers a sign-in.
    $keychain = AKeychainInMemory::working();
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::met(Obstacle::CredentialWasRefused), $keychain);

    $screen->answer();

    expect(WhatTheKeychainStillHolds::forThe($keychain, theStackWhoseUpkeepIsRead()->id())->held)->toBeFalse();
});

it('keeps a session the stack merely could not answer with', function (): void {
    // The other half, and the reason the first is not simply *forget on any
    // obstacle*: a stack that is switched off has refused nothing, and dropping
    // the session would make somebody sign in again to fix a router.
    $keychain = AKeychainInMemory::working();
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::met(Obstacle::StackDidNotAnswer), $keychain);

    $screen->answer();

    expect(WhatTheKeychainStillHolds::forThe($keychain, theStackWhoseUpkeepIsRead()->id())->held)->toBeTrue();
});

it('refuses a route that named no stack it can identify', function (): void {
    // A route parameter that is not a name becomes the blank one rather than
    // whatever it happened to be, so the refusal says *retained state named a
    // stack with a blank identifier* — the honest complaint — instead of *no
    // such stack*, which would send somebody looking for a machine.
    //
    // Named rather than `Throwable`: both this and a placeholder that was not
    // blank would throw something, and only one of them says the right thing.
    $screen = theUpkeepScreen(AStackThatKeepsCurrent::withNothingWaiting());
    $screen->setParams(['stack' => ['not', 'a', 'name']]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});


it('N1-R44 — a signed-out screen states nothing about the stack at all', function (): void {
    // Every field, not just the two the template branches on. A fold that left
    // a version, a count or a flag behind would have a phone whose session
    // ended report a house it can no longer see — and the one that matters most
    // is `offer`: a signed-out screen offering to apply an update is an offer
    // nothing can honour.
    $answer = theUpkeepScreen(AStackThatKeepsCurrent::withNothingWaiting(), signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->went->met)->toBe('')
        ->and($answer->went->remedy)->toBe('')
        ->and($answer->pinsSaid)->toBe('')
        ->and($answer->running)->toBe('')
        ->and($answer->runningWasWithdrawn)->toBeFalse()
        ->and($answer->inUse)->toBeNull()
        ->and($answer->history)->toBe([])
        ->and($answer->offer)->toBeNull();
});

it('N1-R10 — an obstacle states what stood in the way and nothing about the stack', function (): void {
    // The same completeness, one state over. An obstacle means nothing was
    // read, so anything this carried about the stack would be left over from a
    // reading that did not happen.
    $answer = theUpkeepScreen(AStackThatKeepsCurrent::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->isSignedIn)->toBeTrue()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->went->remedy)->toBe(Obstacle::StackDidNotAnswer->remedy())
        ->and($answer->pinsSaid)->toBe('')
        ->and($answer->running)->toBe('')
        ->and($answer->runningWasWithdrawn)->toBeFalse()
        ->and($answer->inUse)->toBeNull()
        ->and($answer->history)->toBe([])
        ->and($answer->offer)->toBeNull();
});

it('N2-R15 — a stack that named no release in use says so rather than showing a blank', function (): void {
    // The dash is the fold's, not the stack's, and it is here so a template
    // reading `running` in every state has something to draw. A screen silent
    // about the version teaches an operator to read silence, and silence is
    // also what a screen that forgot the field produces.
    $answer = theUpkeepScreen(AStackThatKeepsCurrent::with(Upkeep::reported(
        AgainstThePins::Current,
        Releases::none(),
        Services::none(),
        Services::none(),
        HowServicesTookIt::none(),
    )))->answer();

    expect($answer->running)->toBe(WhatTheStackIsOn::NOT_NAMED)
        ->and($answer->pinsSaid)->toBe(AgainstThePins::Current->saidOnTheScreen())
        // No release named, so nothing is said about what one changed.
        ->and($answer->inUse)->toBeNull()
        ->and($answer->runningWasWithdrawn)->toBeFalse()
        // A reading that came through carries neither of the obstacle's keys.
        // They are read as a pair, and a template branching on one while
        // printing the other would put *what to do about it* under a machine
        // where nothing went wrong.
        ->and($answer->went->met)->toBe('')
        ->and($answer->went->remedy)->toBe('');
});

it('N2-R18 — reads what needs attention before what is fine', function (): void {
    // The ordering is the `updates` module's decision, made before the fold so
    // the template draws rows in the order they are read in.
    $keeping = AStackThatKeepsCurrent::whichTook(anEveningWorthSpending(), aReportSaying(HowServicesTookIt::these(
        HowAServiceTookIt::of(ServiceId::called('jellyfin'), HowItEnded::Updated, HowToUndoIt::Rollback),
        HowAServiceTookIt::of(ServiceId::called('sonarr'), HowItEnded::NotStarted, HowToUndoIt::Restore),
        HowAServiceTookIt::of(ServiceId::called('radarr'), HowItEnded::Updated, HowToUndoIt::Rollback),
    )));

    $named = [];

    foreach (aScreenThatTookTheUpdate($keeping)->lastUpdate()->applied as $took) {
        $named[] = $took->service;
    }

    expect($named)->toBe(['sonarr', 'jellyfin', 'radarr']);
});

it('says whether the household will notice what the release carrying the pins changed', function (): void {
    $noticed = theUpkeepScreen(AStackThatKeepsCurrent::with(anEveningWorthSpending()))->answer()->inUse;
    $chore = theUpkeepScreen(AStackThatKeepsCurrent::with(Upkeep::runningOn(
        AgainstThePins::UpdatesAvailable,
        Release::called('4.0.16', noticeable: false, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()),
        Releases::none(),
        Services::these(ServiceId::called('jellyfin')),
        Services::none(),
        HowServicesTookIt::none(),
    )))->answer()->inUse;

    expect($noticed?->theHouseholdWouldNotice)->toBeTrue()
        ->and($noticed?->deliversSaid)->toBe('Adds series search.')
        ->and($chore?->theHouseholdWouldNotice)->toBeFalse()
        ->and($chore?->deliversSaid)->toBeNull();
});

/**
 * The `update` payload a stack sends just after it was updated, before the
 * notes for its build are written.
 *
 * Every service on its pin, so the top-level `state` is `current`. The
 * changelog says `pending` — the running build's release has no notes yet —
 * names no running release, and lists the releases before this build.
 *
 * @return array<string, mixed>
 */
function aStackJustUpdatedWithItsNotesPending(): array
{
    return [
        'state' => 'current',
        'confirmed' => false,
        'in_flight' => [],
        'stack_edits' => [],
        'applied' => [],
        'changes' => [],
        'changelog' => [
            'state' => 'pending',
            'requirements' => [],
            'releases' => [
                ['version' => '4.0.16', 'user_facing' => true, 'delivers' => 'Adds series search.'],
                ['version' => '4.0.15', 'user_facing' => false],
            ],
        ],
    ];
}

/**
 * The same stack a release later, with one service behind its pin.
 *
 * @return array<string, mixed>
 */
function aStackWithOneServiceBehindItsPin(): array
{
    return [
        'state' => 'updates-available',
        'confirmed' => false,
        'in_flight' => [],
        'stack_edits' => [],
        'applied' => [],
        'changes' => [[
            'service' => 'jellyfin',
            'refused' => false,
            'irreversible' => false,
            'because' => 'a newer image is pinned',
            'current' => '10.9.0',
            'target' => '10.10.0',
            'jump' => 'minor',
        ]],
        'changelog' => [
            'state' => 'current',
            'requirements' => [],
            'running' => ['version' => '4.1.0', 'user_facing' => true, 'tag' => 'v4.1.0', 'groups' => []],
            'releases' => [
                ['version' => '4.1.0', 'user_facing' => true],
                ['version' => '4.0.16', 'user_facing' => true],
            ],
        ],
    ];
}

/**
 * The screen drawn over what the adapter's reader makes of an `update` payload.
 *
 * @param  array<string, mixed>  $payload
 * @return list<string>
 */
function theUpkeepScreenDrawnOver(array $payload): array
{
    $upkeep = Standings::in(new Envelope(1, 'update', $payload));

    return WhatTheDeviceWouldDraw::by(theUpkeepScreen(AStackThatKeepsCurrent::with($upkeep)))->said();
}

it('does not say an update is waiting where the notes are pending and the services are on their pins', function (): void {
    // The payload the screen used to read as *an update is waiting*: the
    // changelog's `pending` is about the notes, and its releases are past
    // ones. Drawn in both locales, because the sentence is the catalogue's.
    foreach (['en', 'nl'] as $locale) {
        app()->setLocale($locale);

        $drawn = theUpkeepScreenDrawnOver(aStackJustUpdatedWithItsNotesPending());

        expect($drawn)->toContain(__('updates.pins.current'), __('updates.history'), '4.0.16', '4.0.15')
            ->and($drawn)->not->toContain(__('updates.pins.updates-available'))
            ->and($drawn)->not->toContain(__('updates.take_it'));
    }
});

it('says an update is waiting where a service is behind its pin, and offers it', function (): void {
    foreach (['en', 'nl'] as $locale) {
        app()->setLocale($locale);

        $drawn = theUpkeepScreenDrawnOver(aStackWithOneServiceBehindItsPin());

        expect($drawn)->toContain(
            __('updates.pins.updates-available'),
            __('updates.take_it'),
            trans_choice('updates.would_change', 1),
            __('updates.what_it_changed', ['version' => '4.1.0']),
        )->and($drawn)->not->toContain(__('updates.pins.current'));
    }
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    foreach ([aStackJustUpdatedWithItsNotesPending(), aStackWithOneServiceBehindItsPin()] as $payload) {
        expect(WhatTheContractAccepts::complaintsAbout('UpdateEnvelope', ['kind' => 'update', 'data' => $payload]))
            ->toBe([], "The payload this suite draws the screen over is not one a stack would send.\n");
    }
});
