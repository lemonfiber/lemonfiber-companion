<?php

declare(strict_types=1);

use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowAgreedWorkIsGoing;
use Modules\Kernel\Api\HowAServiceTookIt;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowToUndoIt;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\TheUpkeepScreenOfTheLoft;
use Tests\Support\WhatAMovedOfferSays;
use Tests\Support\WhatTheDeviceWouldDraw;

// What became of an update taken here is reachable: each service it touched, the
// way back from each, and the update itself while it runs.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application — `view()` and `__()` are not there in a
// module suite.

it('says what became of each service the update taken here touched', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), TheUpkeepScreenOfTheLoft::aReportSaying(TheUpkeepScreenOfTheLoft::whatLastNightCameTo()));
    $applied = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping)->lastUpdate()->applied;

    // What did not arrive comes first, which is `NotArrivedFirst`'s doing and
    // is asserted on its own beside the other ordering cases. Read here in the
    // order the template draws them.
    expect($applied)->toHaveCount(2)
        ->and($applied[0]->service)->toBe('sonarr')
        // Not a failure. The row says the image arrived and the service would
        // not come back up on it, which sends somebody to its own log rather
        // than to the machine.
        ->and($applied[0]->endingSaid)->toBe(HowItEnded::NotStarted->saidOnTheScreen())
        ->and($applied[1]->service)->toBe('jellyfin')
        ->and($applied[1]->endingSaid)->toBe(HowItEnded::Updated->saidOnTheScreen())
        ->and($keeping->followed())->toHaveCount(1)
        ->and($keeping->followed()[0]->shown())->toBe(AStackThatKeepsCurrent::THE_JOB);
});

it('says which way back, and whether it brings the data with it', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), TheUpkeepScreenOfTheLoft::aReportSaying(TheUpkeepScreenOfTheLoft::whatLastNightCameTo()));
    $applied = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping)->lastUpdate()->applied;

    // A restore is the larger promise and the different conversation: it puts
    // the snapshot back, and the evening's data with it. It leads because the
    // service it belongs to is the one that did not arrive.
    expect($applied[0]->undoSaid)->toBe(HowToUndoIt::Restore->saidOnTheScreen())
        ->and($applied[0]->undoCarriesTheDataWithIt)->toBeTrue()
        ->and($applied[1]->undoSaid)->toBe(HowToUndoIt::Rollback->saidOnTheScreen())
        ->and($applied[1]->undoCarriesTheDataWithIt)->toBeFalse();
});

it('says the way back under every service the update moved, the one that arrived included', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), TheUpkeepScreenOfTheLoft::aReportSaying(TheUpkeepScreenOfTheLoft::whatLastNightCameTo()));
    $drawn = WhatTheDeviceWouldDraw::by(TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping))->said();

    $arrived = array_search('jellyfin', $drawn, strict: true);

    expect(is_int($arrived))->toBeTrue()
        ->and(array_slice($drawn, (int) $arrived + 1, 2))->toBe([
            __(HowItEnded::Updated->saidOnTheScreen()),
            __(HowToUndoIt::Rollback->saidOnTheScreen()),
        ])
        ->and($drawn)->toContain(__(HowToUndoIt::Restore->saidOnTheScreen()), __('updates.undo_carries_data'));
});

it('counts what is not where the operator wanted it, apart from what is unknown', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), TheUpkeepScreenOfTheLoft::aReportSaying(TheUpkeepScreenOfTheLoft::whatLastNightCameTo()));
    $last = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping)->lastUpdate();

    expect($last->didNotArrive)->toBe(1)
        // `not-started` is something the stack knows. Unanswered is something
        // it does not, and a headline reading them as one would offer a remedy
        // for a situation it is guessing at.
        ->and($last->anythingUnanswered)->toBeFalse()
        ->and($last->wasTaken)->toBeTrue()
        ->and($last->isWorking)->toBeFalse()
        ->and($last->hasEnded)->toBeFalse();
});

it('says when the stack cannot tell what a service is doing', function (): void {
    $last = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate(AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), TheUpkeepScreenOfTheLoft::aReportSaying(HowServicesTookIt::these(
        HowAServiceTookIt::of(ServiceId::called('radarr'), HowItEnded::NotReached, HowToUndoIt::Rollback),
    ))))->lastUpdate();

    expect($last->anythingUnanswered)->toBeTrue()
        ->and($last->didNotArrive)->toBe(1);
});

it('a screen that has taken no update says so, and asks after nothing', function (): void {
    $keeping = AStackThatKeepsCurrent::withNothingWaiting();
    $screen = TheUpkeepScreenOfTheLoft::theUpkeepScreen($keeping);
    $last = $screen->lastUpdate();

    expect($last->wasTaken)->toBeFalse()
        ->and($last->went->cameBack())->toBeTrue()
        ->and($last->applied)->toBe([])
        ->and($keeping->followed())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('updates.nothing_applied'));
});

it('an update just taken is running, and the screen says so', function (): void {
    $keeping = AStackThatKeepsCurrent::with(TheUpkeepScreenOfTheLoft::anEveningWorthSpending());
    $screen = TheUpkeepScreenOfTheLoft::theUpkeepScreen($keeping);
    $screen->wouldYouLike();
    $screen->agree();

    expect($screen->lastUpdate()->isWorking)->toBeTrue()
        ->and([$screen->lastUpdate()->applied, $screen->lastUpdate()->didNotArrive, $screen->lastUpdate()->anythingUnanswered])->toBe([[], 0, false])
        ->and($keeping->followed())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())
        ->toContain(__('updates.still_updating'));
});

it('asks after an update again only while it runs', function (): void {
    $running = AStackThatKeepsCurrent::with(TheUpkeepScreenOfTheLoft::anEveningWorthSpending());
    // Each tick while it runs forgets the answer, and the next one asks.
    $screen = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($running);
    $screen->whileItRuns();
    $screen->whileItRuns();

    $finished = AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), TheUpkeepScreenOfTheLoft::aReportSaying(TheUpkeepScreenOfTheLoft::whatLastNightCameTo()));
    $done = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($finished);
    $done->whileItRuns();
    $done->whileItRuns();

    expect($running->followed())->toHaveCount(2)
        ->and($finished->followed())->toHaveCount(1);
});

it('reads what is behind again while it is open, and leaves an update it is following to its own cadence', function (): void {
    $idle = AStackThatKeepsCurrent::with(TheUpkeepScreenOfTheLoft::anEveningWorthSpending());
    $screen = TheUpkeepScreenOfTheLoft::theUpkeepScreen($idle);
    $screen->answer();
    $screen->whileOpen();
    $screen->answer();

    $running = AStackThatKeepsCurrent::with(TheUpkeepScreenOfTheLoft::anEveningWorthSpending());
    $following = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($running);
    WhatTheDeviceWouldDraw::by($following);
    $askedBefore = $running->askings();
    $followedBefore = count($running->followed());
    $following->whileOpen();
    WhatTheDeviceWouldDraw::by($following);

    expect($idle->askings())->toBe(2)
        ->and($running->askings())->toBe($askedBefore)
        ->and($running->followed())->toHaveCount($followedBefore);
});

it('asking again asks after the update again', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), TheUpkeepScreenOfTheLoft::aReportSaying(TheUpkeepScreenOfTheLoft::whatLastNightCameTo()));
    $screen = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping);
    $screen->lastUpdate();
    $screen->again();
    $screen->lastUpdate();

    expect($keeping->followed())->toHaveCount(2);
});

it('draws the report of a finished update in place of the sentence saying none was taken', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), TheUpkeepScreenOfTheLoft::aReportSaying(TheUpkeepScreenOfTheLoft::whatLastNightCameTo()));
    $drawn = WhatTheDeviceWouldDraw::by(TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping))->said();

    expect($drawn)->toContain('sonarr')
        ->toContain(__(HowItEnded::NotStarted->saidOnTheScreen()))
        ->toContain(__(HowToUndoIt::Restore->saidOnTheScreen()))
        ->toContain(trans_choice('updates.did_not_arrive', 1));
    expect($drawn)->not->toContain(__('updates.nothing_applied'));
    expect($drawn)->not->toContain(__('updates.still_updating'));
});

it('a finished update that touched no service says so', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), TheUpkeepScreenOfTheLoft::aReportSaying(HowServicesTookIt::none()));

    expect(WhatTheDeviceWouldDraw::by(TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping))->said())->toContain(__('updates.touched_nothing'));
});

it('an update the stack has no outcome for is said to be that, not a failure', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), HowAgreedWorkIsGoing::ended());
    $screen = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping);

    expect($screen->lastUpdate()->hasEnded)->toBeTrue()
        ->and([$screen->lastUpdate()->applied, $screen->lastUpdate()->didNotArrive, $screen->lastUpdate()->anythingUnanswered])->toBe([[], 0, false])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('updates.no_outcome'));
});

it('a take the stack refused says what stood in the way, and follows nothing', function (): void {
    $keeping = AStackThatKeepsCurrent::withButRefusing(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $screen = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping);

    expect($screen->lastUpdate()->went->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($screen->lastUpdate()->isWorking)->toBeFalse()
        ->and($keeping->taken())->toHaveCount(1)
        ->and($keeping->followed())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));
});

it('offers taking the same update again where other work held the stack, and takes it only when tapped', function (): void {
    $keeping = AStackThatKeepsCurrent::withButRefusing(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), Obstacle::of(KindOfObstacle::StackIsBusy));
    $screen = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping);

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('connection.try_again'))
        ->and($keeping->taken())->toHaveCount(1);

    $screen->tryAgain();

    expect($keeping->taken())->toHaveCount(2)
        ->and($keeping->taken()[1])->toEqual($keeping->taken()[0]);
});

it('does not offer an update again where anything but other work stood in its way', function (): void {
    $keeping = AStackThatKeepsCurrent::withButRefusing(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $screen = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping);
    $screen->tryAgain();

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('connection.try_again'))
        ->and($keeping->taken())->toHaveCount(1);
});

it('asking after an update says what stood in the way', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), HowAgreedWorkIsGoing::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect(TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping)->lastUpdate()->went->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said());
});

it('lets go of a session the stack refused while taking or following an update', function (): void {
    $refusingTheTake = AKeychainInMemory::working();
    TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate(AStackThatKeepsCurrent::withButRefusing(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), Obstacle::of(KindOfObstacle::CredentialWasRefused)), $refusingTheTake);

    $refusingTheQuestion = AKeychainInMemory::working();
    TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate(
        AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), HowAgreedWorkIsGoing::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))),
        $refusingTheQuestion,
    )->lastUpdate();

    expect($refusingTheTake->isHolding(TheUpkeepScreenOfTheLoft::theStackWhoseUpkeepIsRead()->id()))->toBeFalse()
        ->and($refusingTheQuestion->isHolding(TheUpkeepScreenOfTheLoft::theStackWhoseUpkeepIsRead()->id()))->toBeFalse();
});

it('an obstacle meaning the session ended renders the sign-in', function (): void {
    // The fold's own branch rather than the screen's: *your session ended, sign
    // in again* and *the stack refused that credential* send an operator to two
    // different places, and only one of them offers a way back in.
    $screen = TheUpkeepScreenOfTheLoft::theUpkeepScreen(AStackThatKeepsCurrent::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)));

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->met)->toBe('');
});

/** The evening's update, offered under the name the reading gave it. */
function anEveningOfferedByName(): Upkeep
{
    return TheUpkeepScreenOfTheLoft::anEveningWorthSpending()->offering(AnOffer::named(AStackThatKeepsCurrent::THE_OFFER));
}

/** A stack offering that update, which refuses the yes because what it would apply moved. */
function aStackWhoseUpdateMoved(): AStackThatKeepsCurrent
{
    return AStackThatKeepsCurrent::whichTook(anEveningOfferedByName(), HowAgreedWorkIsGoing::moved(
        ARefusalInItsWords::said(WhatAMovedOfferSays::SUMMARY, WhatAMovedOfferSays::MEANING, WhatTheRefusalNamed::nothing()),
    ));
}

it('carries the name the reading gave its offer back with the update taken', function (): void {
    $keeping = AStackThatKeepsCurrent::with(anEveningOfferedByName());

    TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping);

    expect($keeping->taken())->toHaveCount(1)
        ->and($keeping->taken()[0]->offer())->toEqual(AnOffer::named(AStackThatKeepsCurrent::THE_OFFER));
});

it('says the stack\'s own words and reads again where the update agreed to was refused because what it would apply moved', function (): void {
    $keeping = aStackWhoseUpdateMoved();
    $screen = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate($keeping);
    $askedBefore = $keeping->askings();
    // The frame that heard the refusal read the offer again; the screen's
    // cadence draws what the stack said on the next.
    WhatTheDeviceWouldDraw::by($screen);
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->wouldYouLike();

    expect($drawn)->toContain(WhatAMovedOfferSays::SUMMARY)
        ->and($drawn)->toContain(WhatAMovedOfferSays::MEANING)
        ->and($drawn)->toContain(__('updates.nothing_applied'))
        // Nothing is followed any more, what the stack would do now was read
        // again, and it can be agreed to afresh.
        ->and($screen->took)->toBeNull()
        ->and($keeping->askings())->toBeGreaterThan($askedBefore)
        ->and($screen->asking())->not->toBeNull()
        ->and($keeping->taken())->toHaveCount(1);
});

it('puts what the stack said about a moved update away once the fresh offer is answered', function (string $answer): void {
    $screen = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate(aStackWhoseUpdateMoved());
    WhatTheDeviceWouldDraw::by($screen);
    $before = $screen->movedOn;

    $screen->wouldYouLike();
    $answer === 'agreeing' ? $screen->agree() : $screen->neverMind();

    expect($before)->not->toBeNull()
        ->and($screen->movedOn)->toBeNull();
})->with(['agreeing', 'never minding']);
