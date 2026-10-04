<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\AWalkthrough;
use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheImportLinked;
use Modules\Kernel\Api\HowTheWalkthroughIsGoing;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\KindOfWork;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\TheLinesItSaid;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatElseItIsCalled;
use Modules\Kernel\Api\WhatTheServicesWereSaying;
use Modules\Kernel\Api\WhatTheWalkSaid;
use Modules\Kernel\Api\WhatToDoNext;
use Modules\Kernel\Api\WhatToWalk;
use Modules\Kernel\Api\WhatWasWalked;
use Modules\Kernel\Api\WhereItStopped;
use Modules\Kernel\Api\WhereTheWalkthroughIs;
use Modules\Kernel\Api\WhichWalk;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyTheWalkthroughStopped;
use Modules\Operator\Internal\Screens\WatchingOneArrive;
use Modules\Operator\Internal\ViewModels\AStepOnAsShown;
use Modules\Operator\Internal\ViewModels\TheWalkthroughAsRecorded;
use Modules\Operator\Internal\ViewModels\WhatTheWalkthroughTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhereItStoppedAsShown;
use Modules\Wayfinding\Internal\TheMenu;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatNarrates;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatWalksThrough;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\WorkLeftRunningInMemory;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\Tree;
use Tests\Support\WalkthroughsToFollow;
use Tests\Support\WhatTheDeviceWouldDraw;

// Watching one thing arrive: a walkthrough started, followed while it runs,
// and its record drawn once it finishes.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine a walkthrough is started on. */
function theStackAWalkRunsOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('k', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/**
 * The screen, opened as the router opens it, with a stack it knows and a keychain holding whatever a test says.
 *
 * Mounted, because the router mounts a screen before its first frame and that
 * is where a walk left running is picked up. Named for this file (`G10`).
 */
function theWalkthroughScreen(
    AStackThatWalksThrough $walking,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
    ?AStackThatExplainsItsWords $explaining = null,
    ?WorkLeftRunningInMemory $left = null,
    ?AStackThatNarrates $narrating = null,
    ?FrozenClock $clock = null,
    ?ACaptureInMemory $capture = null,
    ?AStackThatSpeaksUp $listing = null,
): WatchingOneArrive {
    $stack = theStackAWalkRunsOn();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WatchingOneArrive(
        $walking,
        $explaining ?? AStackThatExplainsItsWords::with(TheGlossary::of()),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack), hearing: $listing),
        $left ?? WorkLeftRunningInMemory::working(),
        $narrating ?? AStackThatNarrates::holdingOpen(),
        $clock ?? FrozenClock::at(secondsIntoFollowingAWalk(0)),
        $capture ?? ACaptureInMemory::inFront(),
        settings: new AppsSettingsThatOpen(),
        listening: AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);
    $screen->mount();

    return $screen;
}

/** A moment while a walk is followed, counted in seconds from one a test starts at. */
function secondsIntoFollowingAWalk(int $seconds): Instant
{
    return Instant::atEpochSeconds(1_790_000_000 + $seconds);
}

/** A screen that started a walkthrough of `Big Buck Bunny` and has asked after it once. */
function aScreenThatWalked(
    AStackThatWalksThrough $walking,
    ?AKeychainInMemory $keychain = null,
    ?AStackThatExplainsItsWords $explaining = null,
    ?WorkLeftRunningInMemory $left = null,
): WatchingOneArrive {
    $screen = theWalkthroughScreen($walking, $keychain, explaining: $explaining, left: $left);
    $screen->looking = 'Big Buck Bunny';
    $screen->walk();
    $screen->whileItRuns();

    return $screen;
}

/** The handle a return to the screen would follow, or a word saying there is none. */
function theWalkLeftOn(WorkLeftRunningInMemory $left): string
{
    return $left->whatWasLeft(theStackAWalkRunsOn()->id(), KindOfWork::Walkthrough)->either(
        job: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut($job->shown()),
        nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('(nothing left running)'),
    )->said;
}

/** A device that holds the handle of a walk an earlier screen left running on the stack. */
function aPhoneThatLeftAWalkRunning(string $job = AStackThatWalksThrough::THE_JOB): WorkLeftRunningInMemory
{
    return WorkLeftRunningInMemory::working()->leftBefore(theStackAWalkRunsOn()->id(), KindOfWork::Walkthrough, Job::named($job));
}

/** What the screen was asked to walk, as the stack would be told. */
function whatItWasAskedToWalk(WhatToWalk $asked): string
{
    return $asked->either(
        named: static fn(string $item): TheWordCarriedOut => new TheWordCarriedOut($item),
        likeliest: static fn(): TheWordCarriedOut => new TheWordCarriedOut('(whatever is likely)'),
    )->said;
}

/** The record a finished screen holds, or a failure saying it holds none. */
function theRecordOn(WatchingOneArrive $screen): TheWalkthroughAsRecorded
{
    $record = $screen->answer()->record;

    if (! $record instanceof TheWalkthroughAsRecorded) {
        throw new RuntimeException('The screen holds no record, so this case read nothing.');
    }

    return $record;
}

/**
 * Where a line is drawn among the others, refused where it is not drawn at all.
 *
 * @param list<string> $drawn
 */
function whereItIsDrawn(array $drawn, mixed $said): int
{
    $at = array_search($said, $drawn, strict: true);

    return is_int($at) ? $at : throw new RuntimeException('That line is not drawn, so its place cannot be compared.');
}

/**
 * Every field of a state that carries no record, so a state is held to saying only its own.
 *
 * @return array{isSignedIn: bool, met: string, wasStarted: bool, isWorking: bool, hasEnded: bool, record: null, road: list<string>}
 */
function everythingAStateSays(WhatTheWalkthroughTurnedOutToBe $answer): array
{
    return [
        'isSignedIn' => $answer->went->isSignedIn,
        'met' => $answer->went->met,
        'wasStarted' => $answer->wasStarted,
        'isWorking' => $answer->isWorking,
        'hasEnded' => $answer->hasEnded,
        'record' => $answer->record instanceof TheWalkthroughAsRecorded ? throw new RuntimeException('a state without a record carried one') : null,
        'road' => $answer->road,
    ];
}

it('before a walk, asks only for the glossary and draws the road a walk takes', function (): void {
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning());
    $explaining = AStackThatExplainsItsWords::with(TheGlossary::of(
        AWord::explained('grab', 'Sending a release to the download client', '')->writtenAs(WhatElseItIsCalled::formsOf('grabbing')),
    ));
    $screen = theWalkthroughScreen($walking, explaining: $explaining);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingAStateSays($screen->answer()))->toBe([
        'isSignedIn' => true, 'met' => '', 'wasStarted' => false, 'isWorking' => false, 'hasEnded' => false, 'record' => null,
        'road' => ['choosing', 'searching', 'grabbing', 'downloading', 'importing', 'scanning', 'available'],
    ])
        ->and($walking->walked())->toBe([])
        ->and($walking->followed())->toBe([])
        ->and($explaining->askings())->toBe(1)
        ->and($drawn->said())->toContain(
            __('health.walkthrough.offer'),
            __('health.walkthrough.blank_picks'),
            __('health.walkthrough.road'),
            __('health.at_stage', ['stage' => 'choosing']),
            __('health.at_stage', ['stage' => 'grab']),
            __('stacks.words.in_place', ['word' => 'grab', 'short' => 'Sending a release to the download client']),
            __('health.at_stage', ['stage' => 'available']),
        )
        ->and($drawn->said())->not->toContain(__('health.walkthrough.no_road'))
        ->and($drawn->offers())->toContain(__('health.walkthrough.walk'))
        ->and($drawn->offers())->not->toContain(__('health.ask_again'));
});

it('before a walk, says what stood in the way of reaching the machine, and leaves a way back', function (): void {
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning());
    $screen = theWalkthroughScreen($walking, explaining: AStackThatExplainsItsWords::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingAStateSays($screen->answer()))->toBe([
        'isSignedIn' => true, 'met' => KindOfObstacle::StackDidNotAnswer->said(), 'wasStarted' => true, 'isWorking' => false, 'hasEnded' => false, 'record' => null, 'road' => [],
    ])
        ->and($drawn->offers())->toContain(__('health.ask_again'))
        ->and($walking->walked())->toBe([]);
});

it('walks what was typed, empties the box, and says it is running', function (): void {
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning());
    $screen = theWalkthroughScreen($walking);
    $screen->looking = '  Big Buck Bunny ';

    $screen->walk();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($walking->walked())->toHaveCount(1)
        ->and(whatItWasAskedToWalk($walking->walked()[0]))->toBe('Big Buck Bunny')
        ->and($screen->looking)->toBe('')
        ->and($screen->took)->toBe(AStackThatWalksThrough::THE_JOB)
        ->and(everythingAStateSays($screen->answer()))->toBe([
            'isSignedIn' => true, 'met' => '', 'wasStarted' => true, 'isWorking' => true, 'hasEnded' => false, 'record' => null, 'road' => [],
        ])
        ->and($drawn->said())->toContain(
            __('health.walkthrough.walking'),
        )
        // Running, so there is no second walk to start and no record to draw.
        ->and($drawn->offers())->not->toContain(__('health.walkthrough.walk'))
        ->and($drawn->said())->not->toContain(__('health.walkthrough.record'));
});

it('leaves the choice to the stack where nothing was typed', function (string $typed): void {
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning());
    $screen = theWalkthroughScreen($walking);
    $screen->looking = $typed;

    $screen->walk();

    expect(whatItWasAskedToWalk($walking->walked()[0]))->toBe('(whatever is likely)');
})->with(['nothing' => [''], 'only spaces' => ['   ']]);

it('asks after the handle it was given while the walk runs, and not once it has finished', function (): void {
    $running = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning());
    $screen = aScreenThatWalked($running);
    $screen->answer();
    $screen->whileItRuns();
    $screen->answer();

    $finished = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatWorked()));
    $done = aScreenThatWalked($finished);
    $done->answer();
    $done->whileItRuns();
    $done->answer();

    expect($running->followed())->toHaveCount(2)
        ->and($running->followed()[0]->shown())->toBe(AStackThatWalksThrough::THE_JOB)
        ->and($finished->followed())->toHaveCount(1);
});

it('asks again when asked to', function (): void {
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatWorked()));
    $screen = aScreenThatWalked($walking);
    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($walking->followed())->toHaveCount(2)
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('health.ask_again'));
});

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

/** A step the walk says on the stream, as the adapter hands it over. */
function aStepTheWalkSaid(WalkthroughStep $step, string $said, string $detail = ''): WhatTheWalkSaid
{
    return WhatTheWalkSaid::said($detail === ''
        ? ALineItSaid::withoutDetail($step, $said)
        : ALineItSaid::withDetail($step, $said, $detail));
}

it('opens the stream as a walk starts and draws the stage it says in the stack\'s word, never a bar', function (): void {
    $narrating = AStackThatNarrates::holdingOpen(aStepTheWalkSaid(WalkthroughStep::Searching, 'Searching the indexers for Big Buck Bunny', 'Two indexers answered'));
    $explaining = AStackThatExplainsItsWords::with(TheGlossary::of(
        AWord::explained('search', 'Looking through the indexers for a release', '')->writtenAs(WhatElseItIsCalled::formsOf('searching')),
    ));
    $screen = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), explaining: $explaining, narrating: $narrating);
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
    $screen = theWalkthroughScreen(
        AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()),
        narrating: AStackThatNarrates::holdingOpen(aStepTheWalkSaid(WalkthroughStep::Downloading, 'Downloading Big Buck Bunny')),
    );

    $screen->walk();

    expect($screen->stage()->step)->toBe('downloading')
        ->and($screen->stage()->said)->toBe('Downloading Big Buck Bunny')
        ->and($screen->stage()->detail)->toBe('')
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.walkthrough.at_stage_now', ['stage' => 'downloading']));
});

it('says the stack has not named a stage yet while it listens and has heard none', function (): void {
    $screen = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()));

    $screen->walk();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('health.walkthrough.stage_not_said_yet'))
        ->and($drawn)->not->toContain(__('health.walkthrough.stage_unheard'));
});

it('says the stage could not be heard, rather than drawing an idle walk, and when it listens again', function (): void {
    $screen = theWalkthroughScreen(
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
        aStepTheWalkSaid(WalkthroughStep::Grabbing, 'Sending the release to the download client'),
        WhatTheWalkSaid::nothing(),
        aStepTheWalkSaid(WalkthroughStep::Searching, 'Searching again for a better release'),
    );
    $screen = theWalkthroughScreen($running, narrating: $narrating);
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
    $clock = FrozenClock::at(secondsIntoFollowingAWalk(0));
    $narrating = AStackThatNarrates::holdingOpen(aStepTheWalkSaid(WalkthroughStep::Importing, 'Moving it into the library'));
    $screen = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), narrating: $narrating, clock: $clock);
    $screen->walk();

    $clock->moveTo(secondsIntoFollowingAWalk(30));
    $screen->whileItRuns();

    expect($screen->stage()->ago->said)->toBe('')
        ->and($narrating->lettingsGo())->toBe(1);

    $clock->moveTo(secondsIntoFollowingAWalk(31));
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(
        __('health.walkthrough.stage_unheard'),
        __('health.walkthrough.last_at_stage', ['stage' => 'importing', 'ago' => trans_choice('health.ago.minutes', 0)]),
    )
        ->and($drawn)->not->toContain(__('health.walkthrough.at_stage_now', ['stage' => 'importing']))
        ->and($narrating->lettingsGo())->toBe(2)
        ->and($narrating->asked())->toBe(3);

    $clock->moveTo(secondsIntoFollowingAWalk(40));
    $screen->whileItRuns();

    expect($narrating->asked())->toBe(3);

    $clock->moveTo(secondsIntoFollowingAWalk(41));
    $screen->whileItRuns();

    expect($narrating->asked())->toBe(4);
});

it('lets go of the stream once the walk is over, and only once', function (): void {
    $narrating = AStackThatNarrates::holdingOpen();
    $screen = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatWorked())), narrating: $narrating);
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
    $narrating = AStackThatNarrates::holdingOpen(aStepTheWalkSaid(WalkthroughStep::Scanning, 'Telling the media server to look'));
    $listing = AStackThatSpeaksUp::holdingOpen();
    $screen = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), narrating: $narrating, capture: $capture, listing: $listing);

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
    $narrating = AStackThatNarrates::holdingOpen(aStepTheWalkSaid(WalkthroughStep::Scanning, 'Telling the media server to look'));
    $screen = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), narrating: $narrating, capture: $capture);
    $screen->walk();
    $capture->cameBack();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($narrating->asked())->toBe(0)
        ->and($drawn)->toContain(__('health.walkthrough.stage_not_said_yet'))
        ->and($drawn)->not->toContain(__('health.walkthrough.stage_unheard'));
});

it('says the last stage heard, with when, and not that it could not be heard, when the screen is back in front before the next wake', function (): void {
    $capture = ACaptureInMemory::inFront();
    $clock = FrozenClock::at(secondsIntoFollowingAWalk(0));
    $narrating = AStackThatNarrates::holdingOpen(
        aStepTheWalkSaid(WalkthroughStep::Downloading, 'Downloading Big Buck Bunny'),
        aStepTheWalkSaid(WalkthroughStep::Importing, 'Moving it into the library'),
    );
    $screen = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), narrating: $narrating, clock: $clock, capture: $capture);
    $screen->walk();

    $capture->backgrounded();
    $clock->moveTo(secondsIntoFollowingAWalk(10));
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
    theWalkthroughScreen(
        AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()),
        keychain: $refusing,
        narrating: AStackThatNarrates::holdingOpen(WhatTheWalkSaid::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))),
    )->walk();

    $unanswering = AKeychainInMemory::working();
    theWalkthroughScreen(
        AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()),
        keychain: $unanswering,
        narrating: AStackThatNarrates::holdingOpen(WhatTheWalkSaid::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))),
    )->walk();

    expect($refusing->isHolding(theStackAWalkRunsOn()->id()))->toBeFalse()
        ->and($unanswering->isHolding(theStackAWalkRunsOn()->id()))->toBeTrue();
});

it('forgets the stage an earlier walk said when another is started', function (): void {
    $narrating = AStackThatNarrates::holdingOpen(aStepTheWalkSaid(WalkthroughStep::Available, 'It is in the library'));
    $screen = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), narrating: $narrating);
    $screen->walk();

    expect($screen->stage()->step)->toBe('available');

    $screen->walk();

    expect([$screen->stage()->step, $screen->stage()->said, $screen->stage()->detail])->toBe(['', '', ''])
        ->and($screen->stage()->broke)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.walkthrough.stage_not_said_yet'));
});

it('hears nothing about a walk on a phone whose session has gone, and holds the stage as not current', function (): void {
    $keychain = AKeychainInMemory::working();
    $narrating = AStackThatNarrates::holdingOpen(aStepTheWalkSaid(WalkthroughStep::Choosing, 'Choosing something likely to work'));
    $screen = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), keychain: $keychain, narrating: $narrating);
    $screen->walk();
    $screen->answer();
    $keychain->forget(theStackAWalkRunsOn()->id());

    $screen->whileItRuns();

    expect($narrating->asked())->toBe(1)
        ->and($screen->stage()->broke)->toBeTrue()
        ->and($screen->stage()->step)->toBe('choosing')
        ->and($screen->stage()->ago->said)->not->toBe('');
});

it('draws every line as said and in the order said, as a record', function (): void {
    $screen = aScreenThatWalked(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatWorked())));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $saidAt = static fn(mixed $said): int => whereItIsDrawn($drawn, $said);

    expect($drawn)->toContain(__('health.walkthrough.record'), 'Searching indexers…', '3 indexers, 47 results', 'Available in Jellyfin')
        ->and($saidAt('Searching indexers…'))->toBeLessThan($saidAt('Selecting best match…'))
        ->and($saidAt('Selecting best match…'))->toBeLessThan($saidAt('Sending to download client…'))
        ->and($saidAt('Importing…'))->toBeLessThan($saidAt('Available in Jellyfin'))
        ->and($saidAt(__('health.walkthrough.record')))->toBeLessThan($saidAt('Searching indexers…'))
        // Nothing arrives line by line: the record is drawn whole, and nothing
        // on it says the walk is still going.
        ->and($drawn)->not->toContain(__('health.walkthrough.walking'));
});

it('carries every field of a walk that worked, and a line without a detail has none', function (): void {
    $record = theRecordOn(aScreenThatWalked(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatWorked()))));
    $lines = [];

    foreach ($record->lines as $line) {
        $lines[] = [$line->step, $line->said, $line->detail];
    }

    expect($record->item)->toBe('Big Buck Bunny')
        ->and($record->alreadyHere)->toBeFalse()
        ->and($record->shapeSaid)->toBe(WhichWalk::Pipeline->saidOnTheScreen())
        ->and($record->stateSaid)->toBe(WhereTheWalkthroughIs::Complete->saidOnTheScreen())
        ->and($record->proves)->toBe('That every link from the indexers to the library works.')
        ->and($lines)->toBe([
            ['searching', 'Searching indexers…', '3 indexers, 47 results'],
            ['choosing', 'Selecting best match…', '1080p, matches your Balanced preset'],
            ['grabbing', 'Sending to download client…', 'SABnzbd, via usenet'],
            ['downloading', 'Downloading…', '2.1 GB · 14 MB/s · ~2m'],
            ['importing', 'Importing…', 'copied to /data/media/movies'],
            ['available', 'Available in Jellyfin', ''],
        ])
        ->and($record->suggestions)->toBe([])
        ->and($record->inBackground)->toBeFalse()
        ->and($record->linkSaid)->toBe(HowTheImportLinked::Copied->saidOnTheScreen())
        ->and(array_map(static fn(AStepOnAsShown $step): array => [$step->said, $step->leadsToWhereToWatch], $record->next))->toBe([
            [WhatToDoNext::MoreContent->saidOnTheScreen(), false],
            [WhatToDoNext::Household->saidOnTheScreen(), false],
            [WhatToDoNext::ClientApps->saidOnTheScreen(), true],
        ])
        ->and($record->stopped)->toEqual(WhereItStoppedAsShown::nowhere())
        ->and([$record->stopped->didStop, $record->stopped->step, $record->stopped->whySaid, $record->stopped->remedy, $record->stopped->logs])->toBe([false, '', '', '', []]);
});

it('names what to do next where it finished, and what the import did', function (): void {
    $screen = aScreenThatWalked(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatWorked())));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    // The one next step this app has a screen for is a row that leads there,
    // saying where; the others are lines that lead nowhere.
    $offered = WhatTheDeviceWouldDraw::by($screen)->offers();

    expect($offered)->toContain(__(WhatToDoNext::ClientApps->saidOnTheScreen()))
        ->and($offered)->not->toContain(__(WhatToDoNext::MoreContent->saidOnTheScreen()))
        ->and($offered)->not->toContain(__(WhatToDoNext::Household->saidOnTheScreen()))
        ->and($drawn)->toContain(__('health.walkthrough.where_to_watch'))
        ->and(NativeRouter::resolve($screen->goes()->whoGetsIn()->clients()))->not->toBeNull();

    expect($drawn)->toContain(
        __('health.walkthrough.what_next'),
        __(WhatToDoNext::MoreContent->saidOnTheScreen()),
        __(WhatToDoNext::Household->saidOnTheScreen()),
        __(WhatToDoNext::ClientApps->saidOnTheScreen()),
        __(HowTheImportLinked::Copied->saidOnTheScreen()),
        __('health.walkthrough.walked', ['item' => 'Big Buck Bunny']),
        __(WhereTheWalkthroughIs::Complete->saidOnTheScreen()),
        __(WhichWalk::Pipeline->saidOnTheScreen()),
        __('health.walkthrough.proves', ['proves' => 'That every link from the indexers to the library works.']),
    )
        ->and($drawn)->not->toContain(__('health.walkthrough.in_background'))
        ->and($drawn)->not->toContain(__('health.walkthrough.suggested'));
});

it('draws no handover where it names nothing to do next', function (): void {
    $empty = WhatTheDeviceWouldDraw::by(aScreenThatWalked(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkOfSomethingAlreadyHere()))))->said();
    $none = WhatTheDeviceWouldDraw::by(aScreenThatWalked(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatMatchedNothing()))))->said();

    expect($empty)->not->toContain(__('health.walkthrough.what_next'))
        ->and($none)->not->toContain(__('health.walkthrough.what_next'));
});

it('says already here first and as its own outcome, never as a search that matched nothing', function (): void {
    $screen = aScreenThatWalked(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkOfSomethingAlreadyHere())));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $already = __('health.walkthrough.already_here', ['item' => 'Sintel']);

    expect(theRecordOn($screen)->alreadyHere)->toBeTrue()
        ->and(theRecordOn($screen)->stopped->didStop)->toBeFalse()
        ->and($drawn)->toContain($already, __('health.walkthrough.in_background'), __(HowTheImportLinked::Hardlinked->saidOnTheScreen()))
        ->and(whereItIsDrawn($drawn, $already))->toBeLessThan(whereItIsDrawn($drawn, __(WhereTheWalkthroughIs::Complete->saidOnTheScreen())))
        ->and($drawn)->not->toContain(__(WhyTheWalkthroughStopped::NothingMatched->saidOnTheScreen()))
        ->and($drawn)->not->toContain(__('health.walkthrough.walked', ['item' => 'Sintel']));
});

it('says already here without a name where it never named what it found', function (): void {
    $walk = AWalkthrough::reported(
        WhichWalk::Pipeline,
        WhereTheWalkthroughIs::Complete,
        'That it works.',
        WhatWasWalked::nothingChosen(),
        TheLinesItSaid::of(),
        inBackground: false,
        alreadyHere: true,
    );
    $drawn = WhatTheDeviceWouldDraw::by(aScreenThatWalked(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done($walk))))->said();

    expect($drawn)->toContain(__('health.walkthrough.already_here_unnamed'), __('health.walkthrough.said_nothing'))
        ->and(theRecordOn(aScreenThatWalked(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done($walk))))->item)->toBe('');
});

it('names no item where it never chose one and nothing was already here', function (): void {
    $walk = AWalkthrough::reported(
        WhichWalk::Pipeline,
        WhereTheWalkthroughIs::Offered,
        'That it works.',
        WhatWasWalked::nothingChosen(),
        TheLinesItSaid::of(),
        inBackground: false,
        alreadyHere: false,
    );
    $drawn = WhatTheDeviceWouldDraw::by(aScreenThatWalked(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done($walk))))->said();

    expect($drawn)->toContain(__(WhereTheWalkthroughIs::Offered->saidOnTheScreen()))
        ->and($drawn)->not->toContain(__('health.walkthrough.already_here_unnamed'))
        ->and(array_filter($drawn, static fn(string $line): bool => str_contains($line, '“')))->toBe([]);
});

it('says where it stopped, why, what to try, and what the services were saying', function (): void {
    $screen = aScreenThatWalked(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatMatchedNothing())));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $stopped = theRecordOn($screen)->stopped;

    expect($stopped->didStop)->toBeTrue()
        ->and($stopped->step)->toBe('searching')
        ->and($stopped->whySaid)->toBe(WhyTheWalkthroughStopped::NothingMatched->saidOnTheScreen())
        ->and($stopped->remedy)->toBe('Try one of the suggestions, which are well seeded.')
        ->and($stopped->logs)->toBe(['prowlarr: query returned 0 results', ''])
        ->and(theRecordOn($screen)->linkSaid)->toBe('')
        ->and(theRecordOn($screen)->next)->toBe([])
        ->and($drawn)->toContain(
            __('health.walkthrough.stopped_at', ['step' => 'searching']),
            __(WhyTheWalkthroughStopped::NothingMatched->saidOnTheScreen()),
            __('health.walkthrough.try', ['remedy' => 'Try one of the suggestions, which are well seeded.']),
            __('health.walkthrough.logs'),
            'prowlarr: query returned 0 results',
        )
        ->and($drawn)->not->toContain(__('health.walkthrough.no_logs'));
});

it('says the services said nothing where a stop carries no logs', function (): void {
    $walk = AWalkthrough::reported(
        WhichWalk::Pipeline,
        WhereTheWalkthroughIs::Failed,
        'That it works.',
        WhatWasWalked::nothingChosen(),
        TheLinesItSaid::of(ALineItSaid::withoutDetail(WalkthroughStep::Downloading, 'Downloading…')),
        inBackground: false,
        alreadyHere: false,
    )->stoppedAt(WhereItStopped::at(WalkthroughStep::Downloading, WhyTheWalkthroughStopped::Stalled, 'Look at the download client.', WhatTheServicesWereSaying::of()));

    expect(WhatTheDeviceWouldDraw::by(aScreenThatWalked(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done($walk))))->said())
        ->toContain(__('health.walkthrough.no_logs'), __(WhyTheWalkthroughStopped::Stalled->saidOnTheScreen()));
});

it('offers what it suggests, and walks the one tapped', function (): void {
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatMatchedNothing()));
    $screen = aScreenThatWalked($walking);

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(
        __('health.walkthrough.walk_this', ['item' => 'Big Buck Bunny']),
        __('health.walkthrough.walk_this', ['item' => 'Sintel']),
    );

    $screen->walkSuggested('1');

    expect($walking->walked())->toHaveCount(2)
        ->and(whatItWasAskedToWalk($walking->walked()[1]))->toBe('Sintel');

    $screen->whileItRuns();
    $screen->walkSuggested('0');

    expect($walking->walked())->toHaveCount(3)
        ->and(whatItWasAskedToWalk($walking->walked()[2]))->toBe('Big Buck Bunny');
});

it('walks nothing for a place the suggestions do not have, or before there are any', function (string $place): void {
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatMatchedNothing()));
    $screen = aScreenThatWalked($walking);

    $screen->walkSuggested($place);

    $running = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning());
    $still = aScreenThatWalked($running);
    $still->walkSuggested('0');

    $fresh = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning());
    theWalkthroughScreen($fresh)->walkSuggested('0');

    expect($walking->walked())->toHaveCount(1)
        ->and($running->walked())->toHaveCount(1)
        ->and($fresh->walked())->toBe([]);
})->with(['past the end' => ['2'], 'not a place' => ['Sintel'], 'empty' => ['']]);

it('draws each step as the glossary\'s word for it, explained in place', function (): void {
    $explaining = AStackThatExplainsItsWords::with(TheGlossary::of(
        AWord::explained('search', 'Looking through the indexers for a release', '')->writtenAs(WhatElseItIsCalled::formsOf('searching')),
    ));
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame(aScreenThatWalked(
        AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatWorked())),
        explaining: $explaining,
    ))->said();

    expect($drawn)->toContain(
        __('health.at_stage', ['stage' => 'search']),
        __('stacks.words.in_place', ['word' => 'search', 'short' => 'Looking through the indexers for a release']),
        // A step the glossary does not carry is drawn as it came, unexplained.
        __('health.at_stage', ['stage' => 'grabbing']),
    )
        ->and($drawn)->not->toContain(__('health.at_stage', ['stage' => 'searching']))
        ->and($explaining->askings())->toBe(1);
});

it('reads a walk that finished while nobody was looking the same as one that was watched', function (): void {
    // The handle is all a screen keeps. One opened again after the walk has
    // finished asks once, and draws the same record in the same order.
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatWorked()));
    $left = WorkLeftRunningInMemory::working();
    $watched = aScreenThatWalked($walking, left: $left);
    $returnedTo = theWalkthroughScreen($walking, left: $left);

    expect(WhatTheDeviceWouldDraw::by($returnedTo)->said())->toBe(WhatTheDeviceWouldDraw::by($watched)->said())
        ->and($walking->walked())->toHaveCount(1);
});

it('says while a walk runs that leaving does not stop it, and keeps what to find it by', function (): void {
    $left = WorkLeftRunningInMemory::working();
    $screen = aScreenThatWalked(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), left: $left);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('health.walkthrough.leaving'))
        ->and($drawn)->not->toContain(__('health.walkthrough.leaving_not_noted'))
        ->and($screen->willBeFoundAgain)->toBeTrue()
        ->and(theWalkLeftOn($left))->toBe(AStackThatWalksThrough::THE_JOB);
});

it('says coming back will not find a walk this phone could not note, and still follows it here', function (): void {
    // The walk goes on either way; what is lost is the way back to it, and
    // this screen is the last place anybody can be told. Said on every frame
    // while it runs, not only on the one that started it.
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning());
    $left = WorkLeftRunningInMemory::refusing();
    $screen = aScreenThatWalked($walking, left: $left);
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('health.walkthrough.leaving_not_noted'))
        ->and($drawn)->not->toContain(__('health.walkthrough.leaving'))
        ->and($screen->answer()->isWorking)->toBeTrue()
        ->and($walking->followed())->toHaveCount(2)
        ->and($walking->followed()[1]->shown())->toBe(AStackThatWalksThrough::THE_JOB)
        ->and(theWalkLeftOn($left))->toBe('(nothing left running)');
});

it('shows where a walk left running got to when the screen is opened again, and starts nothing', function (): void {
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning());
    $returnedTo = theWalkthroughScreen($walking, left: aPhoneThatLeftAWalkRunning());
    $drawn = WhatTheDeviceWouldDraw::by($returnedTo);

    expect($returnedTo->took)->toBe(AStackThatWalksThrough::THE_JOB)
        ->and(everythingAStateSays($returnedTo->answer()))->toBe([
            'isSignedIn' => true, 'met' => '', 'wasStarted' => true, 'isWorking' => true, 'hasEnded' => false, 'record' => null, 'road' => [],
        ])
        ->and($drawn->said())->toContain(__('health.walkthrough.walking'), __('health.walkthrough.leaving'))
        ->and($drawn->said())->not->toContain(__('health.walkthrough.leaving_not_noted'))
        ->and($drawn->offers())->not->toContain(__('health.walkthrough.walk'))
        ->and($walking->walked())->toBe([])
        ->and($walking->followed())->toHaveCount(1)
        ->and($walking->followed()[0]->shown())->toBe(AStackThatWalksThrough::THE_JOB);
});

it('hears the stage a walk left running is at from the first wake after the screen is opened again', function (): void {
    // Opening the screen reads the device and waits on nothing, so the stream
    // is not opened on the way in; the wake that asks after the handle is the
    // first one that listens.
    $narrating = AStackThatNarrates::holdingOpen(aStepTheWalkSaid(WalkthroughStep::Downloading, 'Downloading Big Buck Bunny', '40% of 1.2 GB'));
    $returnedTo = theWalkthroughScreen(
        AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()),
        left: aPhoneThatLeftAWalkRunning(),
        narrating: $narrating,
    );

    $drawn = WhatTheDeviceWouldDraw::by($returnedTo)->said();

    // Nothing has failed before the first wake, so the stage is one not said
    // yet rather than one that could not be heard.
    expect($returnedTo->answer()->isWorking)->toBeTrue()
        ->and($narrating->asked())->toBe(0)
        ->and($drawn)->toContain(__('health.walkthrough.stage_not_said_yet'))
        ->and($drawn)->not->toContain(__('health.walkthrough.stage_unheard'));

    $returnedTo->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($returnedTo)->said())->toContain(
        __('health.walkthrough.at_stage_now', ['stage' => 'downloading']),
        'Downloading Big Buck Bunny',
    )
        ->and($narrating->asked())->toBe(1);
});

it('opens on the road where no walk was left running', function (): void {
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning());
    $screen = theWalkthroughScreen($walking, left: WorkLeftRunningInMemory::working());

    expect($screen->took)->toBeNull()
        ->and($screen->answer()->wasStarted)->toBeFalse()
        ->and($walking->followed())->toBe([]);
});

it('keeps a finished record for the next return', function (): void {
    $left = aPhoneThatLeftAWalkRunning();
    $screen = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatWorked())), left: $left);

    expect(theRecordOn($screen)->item)->toBe('Big Buck Bunny')
        ->and(theWalkLeftOn($left))->toBe(AStackThatWalksThrough::THE_JOB);
});

it('lets go of a walk the stack no longer knows, so the next opening offers a new one', function (): void {
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::ended());
    $left = aPhoneThatLeftAWalkRunning();
    $screen = theWalkthroughScreen($walking, left: $left);

    expect($screen->answer()->hasEnded)->toBeTrue()
        ->and(theWalkLeftOn($left))->toBe('(nothing left running)')
        ->and(theWalkthroughScreen($walking, left: $left)->answer()->wasStarted)->toBeFalse()
        ->and($walking->followed())->toHaveCount(1);
});

it('lets go of the walk before it when another is asked for, even where the start is refused', function (): void {
    $left = aPhoneThatLeftAWalkRunning('an-earlier-walk');
    $screen = theWalkthroughScreen(AStackThatWalksThrough::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), left: $left);

    // Opened on the earlier walk, and moved on from it by asking for another.
    expect($screen->took)->toBe('an-earlier-walk');

    $screen->walk();

    expect(theWalkLeftOn($left))->toBe('(nothing left running)')
        ->and($screen->took)->toBeNull();
});

it('keeps the walk left running where the stack cannot be reached or the session has ended', function (): void {
    // The walk is on the stack whoever can reach it, so neither is a reason to
    // lose the way back to it: reaching it again, or signing in again, finds it.
    $unreached = aPhoneThatLeftAWalkRunning();
    theWalkthroughScreen(AStackThatWalksThrough::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), left: $unreached)->answer();

    $signedOut = aPhoneThatLeftAWalkRunning();
    theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), signedIn: false, left: $signedOut)->answer();

    expect(theWalkLeftOn($unreached))->toBe(AStackThatWalksThrough::THE_JOB)
        ->and(theWalkLeftOn($signedOut))->toBe(AStackThatWalksThrough::THE_JOB);
});

it('says a walk the stack no longer knows has no outcome, which is not a failure', function (): void {
    $screen = aScreenThatWalked(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::ended()));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingAStateSays($screen->answer()))->toBe([
        'isSignedIn' => true, 'met' => '', 'wasStarted' => true, 'isWorking' => false, 'hasEnded' => true, 'record' => null, 'road' => [],
    ])
        ->and($drawn->said())->toContain(__('health.walkthrough.no_outcome'), __('health.walkthrough.no_outcome_action'))
        ->and($drawn->offers())->toContain(__('health.walkthrough.walk'));
});

it('says what stood in the way of starting one, and follows nothing', function (): void {
    $walking = AStackThatWalksThrough::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $screen = theWalkthroughScreen($walking);
    // A handle from an earlier walk is not one this start answered, so it is
    // not kept to be followed.
    $screen->took = 'an-earlier-walk';
    $screen->walk();

    expect(everythingAStateSays($screen->answer()))->toBe([
        'isSignedIn' => true, 'met' => KindOfObstacle::StackDidNotAnswer->said(), 'wasStarted' => true, 'isWorking' => false, 'hasEnded' => false, 'record' => null, 'road' => [],
    ])
        ->and($screen->answer()->went->remedy)->toEqual(KindOfObstacle::StackDidNotAnswer->remedy())
        ->and($screen->took)->toBeNull()
        ->and($walking->followed())->toBe([]);
});

it('lets go of a session the stack refused, before a walk, starting or following', function (): void {
    $before = AKeychainInMemory::working();
    $refusedBefore = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), $before, explaining: AStackThatExplainsItsWords::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)));

    expect($refusedBefore->answer()->went->isSignedIn)->toBeFalse()
        ->and($before->isHolding(theStackAWalkRunsOn()->id()))->toBeFalse();

    $starting = AKeychainInMemory::working();
    theWalkthroughScreen(AStackThatWalksThrough::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $starting)->walk();

    $following = AKeychainInMemory::working();
    $screen = theWalkthroughScreen(AStackThatWalksThrough::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $following);
    $screen->took = AStackThatWalksThrough::THE_JOB;

    expect(everythingAStateSays($screen->answer()))->toBe([
        'isSignedIn' => false, 'met' => '', 'wasStarted' => true, 'isWorking' => false, 'hasEnded' => false, 'record' => null, 'road' => [],
    ])
        ->and($starting->isHolding(theStackAWalkRunsOn()->id()))->toBeFalse()
        ->and($following->isHolding(theStackAWalkRunsOn()->id()))->toBeFalse();
});

it('starts and follows nothing on a phone whose session ended', function (): void {
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning());
    $screen = theWalkthroughScreen($walking, signedIn: false);
    $screen->walk();

    $following = theWalkthroughScreen($walking, signedIn: false);
    $following->took = AStackThatWalksThrough::THE_JOB;

    expect(everythingAStateSays($screen->answer()))->toBe([
        'isSignedIn' => false, 'met' => '', 'wasStarted' => true, 'isWorking' => false, 'hasEnded' => false, 'record' => null, 'road' => [],
    ])
        ->and(everythingAStateSays($following->answer()))->toBe(everythingAStateSays($screen->answer()))
        ->and($walking->walked())->toBe([])
        ->and($walking->followed())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('health.walkthrough.offer'));
});

it('is where it says it is', function (): void {
    $screen = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()));

    expect(NativeRouter::resolve(TheMenu::FollowADownload->screen()->forTheStack($screen->stack()->id())))->toHaveKey('params.stack', theStackAWalkRunsOn()->id()->stored())
        ->and(TheMenu::FollowADownload->screen()->forTheStack($screen->stack()->id()))->toBe(sprintf('/stacks/%s/walkthrough', theStackAWalkRunsOn()->id()->stored()));
});

it('refuses a route parameter that is not text', function (): void {
    // A parameter arrives as `mixed`, because the navigation stack's own
    // parameter array is untyped. Anything that is not a string names no stack.
    $screen = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});
