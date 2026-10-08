<?php

declare(strict_types=1);

use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\HowTheWalkthroughIsGoing;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatElseItIsCalled;
use Modules\Kernel\Api\WhatTheWalkSaid;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatNarrates;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatWalksThrough;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\TheWalkthroughScreenOfTheLoft;
use Tests\Support\Tree;
use Tests\Support\WalkthroughsToFollow;
use Tests\Support\WhatTheDeviceWouldDraw;

// Hearing where a walkthrough has got while it runs: the stage the stack says on
// its stream, heard while somebody can see the screen and let go once nobody can
// or the walk is over.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/**
 * Whether the screen's runloop would go round again. Named for this file (`G10`).
 *
 * Letting go of the subscription is not all that stopping does: a screen that
 * let go and went on running would be the one on the glass still.
 */
function whetherTheWalkScreenGoesRound(NativeComponent $screen): bool
{
    $asked = Closure::bind(static fn(NativeComponent $running): bool => $running->nativeRunning, null, NativeComponent::class);

    return $asked($screen);
}

it('opens the stream as a walk starts and draws the stage it says in the stack\'s word, never a bar', function (): void {
    $narrating = AStackThatNarrates::holdingOpen(TheWalkthroughScreenOfTheLoft::aStepTheWalkSaid(WalkthroughStep::Searching, 'Searching the indexers for Big Buck Bunny', 'Two indexers answered'));
    $explaining = AStackThatExplainsItsWords::with(TheGlossary::of(
        AWord::explained('search', 'Looking through the indexers for a release', '')->writtenAs(WhatElseItIsCalled::formsOf('searching')),
    ));
    $screen = TheWalkthroughScreenOfTheLoft::theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), explaining: $explaining, narrating: $narrating);
    $screen->looking = 'Big Buck Bunny';

    $screen->walk();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($narrating->asked())->toBe(1)
        ->and($drawn)->toContain(
            __('health.walkthrough.at_stage_now', ['stage' => 'search']),
            __('stacks.words.in_place', ['word' => 'search', 'short' => 'Looking through the indexers for a release']),
            'Searching the indexers for Big Buck Bunny',
            'Two indexers answered',
            __('health.walkthrough.lines_when_done'),
        )
        ->and($drawn)->not->toContain(
            __('health.walkthrough.stage_not_said_yet'),
            __('health.walkthrough.stage_unheard'),
        );

    // A stage is what the walk is doing; a bar would say only that it was
    // doing something, so the screen has none to draw.
    $template = (string) file_get_contents(Tree::at('app-modules/operator/resources/views/watching-one-arrive.blade.php'));

    expect($template)->not->toContain('progress')
        ->and($template)->not->toContain('activity-indicator');
});

it('draws a stage with nothing particular to say without a detail', function (): void {
    $screen = TheWalkthroughScreenOfTheLoft::theWalkthroughScreen(
        AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()),
        narrating: AStackThatNarrates::holdingOpen(TheWalkthroughScreenOfTheLoft::aStepTheWalkSaid(WalkthroughStep::Downloading, 'Downloading Big Buck Bunny')),
    );

    $screen->walk();

    expect($screen->stage()->step)->toBe('downloading')
        ->and($screen->stage()->said)->toBe('Downloading Big Buck Bunny')
        ->and($screen->stage()->detail)->toBe('')
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.walkthrough.at_stage_now', ['stage' => 'downloading']));
});

it('says the stack has not named a stage yet while it listens and has heard none', function (): void {
    $screen = TheWalkthroughScreenOfTheLoft::theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()));

    $screen->walk();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('health.walkthrough.stage_not_said_yet'))
        ->and($drawn)->not->toContain(__('health.walkthrough.stage_unheard'));
});

it('says the stage could not be heard, rather than drawing an idle walk, and when it listens again', function (): void {
    $screen = TheWalkthroughScreenOfTheLoft::theWalkthroughScreen(
        AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()),
        narrating: AStackThatNarrates::holdingOpen(WhatTheWalkSaid::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))),
    );

    $screen->walk();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(
        __('health.walkthrough.walking'),
        __('health.walkthrough.stage_unheard'),
    )
        ->and($drawn)->not->toContain(__('health.walkthrough.stage_not_said_yet'));
});

it('takes the stage on the wakes that ask after the walk, and draws the newest, earlier or not', function (): void {
    $running = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning());
    $narrating = AStackThatNarrates::holdingOpen(
        TheWalkthroughScreenOfTheLoft::aStepTheWalkSaid(WalkthroughStep::Grabbing, 'Sending the release to the download client'),
        WhatTheWalkSaid::nothing(),
        TheWalkthroughScreenOfTheLoft::aStepTheWalkSaid(WalkthroughStep::Searching, 'Searching again for a better release'),
    );
    $screen = TheWalkthroughScreenOfTheLoft::theWalkthroughScreen($running, narrating: $narrating);
    $screen->walk();
    $screen->answer();

    $screen->whileItRuns();
    $screen->answer();

    expect($screen->stage()->step)->toBe('grabbing');

    $screen->whileItRuns();
    $screen->answer();

    expect($narrating->asked())->toBe(3)
        ->and($running->followed())->toHaveCount(2)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.walkthrough.at_stage_now', ['stage' => 'searching']));
});

it('says a stage heard before the stream went quiet is not where the walk is now, and waits out the break', function (): void {
    $clock = FrozenClock::at(TheWalkthroughScreenOfTheLoft::secondsIntoFollowingAWalk(0));
    $narrating = AStackThatNarrates::holdingOpen(TheWalkthroughScreenOfTheLoft::aStepTheWalkSaid(WalkthroughStep::Importing, 'Moving it into the library'));
    $screen = TheWalkthroughScreenOfTheLoft::theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), narrating: $narrating, clock: $clock);
    $screen->walk();

    $clock->moveTo(TheWalkthroughScreenOfTheLoft::secondsIntoFollowingAWalk(30));
    $screen->whileItRuns();

    expect($screen->stage()->ago->said)->toBe('')
        ->and($narrating->lettingsGo())->toBe(1);

    $clock->moveTo(TheWalkthroughScreenOfTheLoft::secondsIntoFollowingAWalk(31));
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(
        __('health.walkthrough.stage_unheard'),
        __('health.walkthrough.last_at_stage', ['stage' => 'importing', 'ago' => trans_choice('health.ago.minutes', 0)]),
    )
        ->and($drawn)->not->toContain(__('health.walkthrough.at_stage_now', ['stage' => 'importing']))
        ->and($narrating->lettingsGo())->toBe(2)
        ->and($narrating->asked())->toBe(3);

    $clock->moveTo(TheWalkthroughScreenOfTheLoft::secondsIntoFollowingAWalk(40));
    $screen->whileItRuns();

    expect($narrating->asked())->toBe(3);

    $clock->moveTo(TheWalkthroughScreenOfTheLoft::secondsIntoFollowingAWalk(41));
    $screen->whileItRuns();

    expect($narrating->asked())->toBe(4);
});

it('lets go of the stream once the walk is over, and only once', function (): void {
    $narrating = AStackThatNarrates::holdingOpen();
    $screen = TheWalkthroughScreenOfTheLoft::theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatWorked())), narrating: $narrating);
    $screen->walk();
    $screen->whileItRuns();
    $screen->answer();

    // One let go as the walk began, before anything was held.
    expect($narrating->lettingsGo())->toBe(1)
        ->and($narrating->asked())->toBe(2);

    $screen->whileItRuns();
    $screen->whileItRuns();

    expect($narrating->lettingsGo())->toBe(2)
        ->and($narrating->asked())->toBe(2)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('health.walkthrough.stage_not_said_yet'));
});

it('lets go of the stream when nobody can see it, opens it again when somebody can, and when the screen is left', function (): void {
    $capture = ACaptureInMemory::away();
    $narrating = AStackThatNarrates::holdingOpen(TheWalkthroughScreenOfTheLoft::aStepTheWalkSaid(WalkthroughStep::Scanning, 'Telling the media server to look'));
    $listing = AStackThatSpeaksUp::holdingOpen();
    $screen = TheWalkthroughScreenOfTheLoft::theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), narrating: $narrating, capture: $capture, listing: $listing);

    $screen->walk();

    expect($narrating->asked())->toBe(0)
        ->and($narrating->lettingsGo())->toBe(2)
        ->and($screen->stage()->broke)->toBeFalse();

    $capture->cameBack();
    $screen->answer();
    $screen->whileItRuns();

    expect($narrating->asked())->toBe(1)
        ->and($screen->stage()->step)->toBe('scanning')
        ->and($screen->stage()->ago->said)->toBe('');

    $screen->stop();

    // The list of stacks the top bar's name opens is let go of in the same stop.
    expect($narrating->lettingsGo())->toBe(3)
        ->and($listing->lettingsGo())->toBe(1)
        ->and($screen->stage()->ago->said)->not->toBe('')
        ->and(whetherTheWalkScreenGoesRound($screen))->toBeFalse();
});

it('says a stage not said yet, never one that could not be heard, while nobody can see the screen and until the wake after it is back', function (): void {
    $capture = ACaptureInMemory::away();
    $narrating = AStackThatNarrates::holdingOpen(TheWalkthroughScreenOfTheLoft::aStepTheWalkSaid(WalkthroughStep::Scanning, 'Telling the media server to look'));
    $screen = TheWalkthroughScreenOfTheLoft::theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), narrating: $narrating, capture: $capture);
    $screen->walk();
    $capture->cameBack();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($narrating->asked())->toBe(0)
        ->and($drawn)->toContain(__('health.walkthrough.stage_not_said_yet'))
        ->and($drawn)->not->toContain(__('health.walkthrough.stage_unheard'));
});

it('says the last stage heard, with when, and not that it could not be heard, when the screen is back in front before the next wake', function (): void {
    $capture = ACaptureInMemory::inFront();
    $clock = FrozenClock::at(TheWalkthroughScreenOfTheLoft::secondsIntoFollowingAWalk(0));
    $narrating = AStackThatNarrates::holdingOpen(
        TheWalkthroughScreenOfTheLoft::aStepTheWalkSaid(WalkthroughStep::Downloading, 'Downloading Big Buck Bunny'),
        TheWalkthroughScreenOfTheLoft::aStepTheWalkSaid(WalkthroughStep::Importing, 'Moving it into the library'),
    );
    $screen = TheWalkthroughScreenOfTheLoft::theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), narrating: $narrating, clock: $clock, capture: $capture);
    $screen->walk();

    $capture->backgrounded();
    $clock->moveTo(TheWalkthroughScreenOfTheLoft::secondsIntoFollowingAWalk(10));
    $screen->whileItRuns();
    $capture->cameBack();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->stage()->broke)->toBeFalse()
        ->and($drawn)->toContain(__('health.walkthrough.last_at_stage', ['stage' => 'downloading', 'ago' => trans_choice('health.ago.minutes', 0)]))
        ->and($drawn)->not->toContain(
            __('health.walkthrough.stage_unheard'),
            __('health.walkthrough.at_stage_now', ['stage' => 'downloading']),
        );

    $screen->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.walkthrough.at_stage_now', ['stage' => 'importing']));
});

it('lets go of a session the stream refuses while a walk runs, and keeps one the stack only failed to answer on', function (): void {
    $refusing = AKeychainInMemory::working();
    TheWalkthroughScreenOfTheLoft::theWalkthroughScreen(
        AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()),
        keychain: $refusing,
        narrating: AStackThatNarrates::holdingOpen(WhatTheWalkSaid::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))),
    )->walk();

    $unanswering = AKeychainInMemory::working();
    TheWalkthroughScreenOfTheLoft::theWalkthroughScreen(
        AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()),
        keychain: $unanswering,
        narrating: AStackThatNarrates::holdingOpen(WhatTheWalkSaid::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))),
    )->walk();

    expect($refusing->isHolding(TheWalkthroughScreenOfTheLoft::theStackAWalkRunsOn()->id()))->toBeFalse()
        ->and($unanswering->isHolding(TheWalkthroughScreenOfTheLoft::theStackAWalkRunsOn()->id()))->toBeTrue();
});

it('forgets the stage an earlier walk said when another is started', function (): void {
    $narrating = AStackThatNarrates::holdingOpen(TheWalkthroughScreenOfTheLoft::aStepTheWalkSaid(WalkthroughStep::Available, 'It is in the library'));
    $screen = TheWalkthroughScreenOfTheLoft::theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), narrating: $narrating);
    $screen->walk();

    expect($screen->stage()->step)->toBe('available');

    $screen->walk();

    expect([$screen->stage()->step, $screen->stage()->said, $screen->stage()->detail])->toBe(['', '', ''])
        ->and($screen->stage()->broke)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.walkthrough.stage_not_said_yet'));
});

it('hears nothing about a walk on a phone whose session has gone, and holds the stage as not current', function (): void {
    $keychain = AKeychainInMemory::working();
    $narrating = AStackThatNarrates::holdingOpen(TheWalkthroughScreenOfTheLoft::aStepTheWalkSaid(WalkthroughStep::Choosing, 'Choosing something likely to work'));
    $screen = TheWalkthroughScreenOfTheLoft::theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), keychain: $keychain, narrating: $narrating);
    $screen->walk();
    $screen->answer();
    $keychain->forget(TheWalkthroughScreenOfTheLoft::theStackAWalkRunsOn()->id());

    $screen->whileItRuns();

    expect($narrating->asked())->toBe(1)
        ->and($screen->stage()->broke)->toBeTrue()
        ->and($screen->stage()->step)->toBe('choosing')
        ->and($screen->stage()->ago->said)->not->toBe('');
});
