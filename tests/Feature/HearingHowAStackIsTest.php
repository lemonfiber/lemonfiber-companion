<?php

declare(strict_types=1);

use Modules\Kernel\Api\AboutWhat;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAffectedItem;
use Modules\Kernel\Api\AStoppage;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\HowItStopped;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatFollowedFromIt;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\View\Components\PortRow;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\AScreenListening;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// The one line on the screen the app opens a machine on, held from the core's
// event stream rather than read.
//
// What is asserted is what the operator would see and what the stack would be
// asked, wake by wake: the summary drawn as it arrives, a stream let go of
// whenever nobody can see it, silence past the contract's bound read as not
// knowing, and a broken stream opened again on the cadence the screen states.

/** The machine whose summary is being listened to. */
function theStackBeingListenedTo(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The shed'),
        Address::of('https://192.168.1.44:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** A summary counting one thing, with every part an item has. */
function aSummaryOfAFillingDisk(): TheHealthSummary
{
    return TheHealthSummary::of(
        HowItStands::Broken,
        1,
        'The disk is full',
        WhatStoppedMoving::nothing(),
        AnAffectedItem::of(
            Check::of('disk.space'),
            Severity::Error,
            'The disk is full',
            'Nothing new can be downloaded',
            Remedies::of(Remedy::of('Make room')),
            WhatFollowedFromIt::of('Imports are failing'),
        ),
    );
}

/** A run of checks with one finding, which the screen draws under the line. */
function aRunWithOneFinding(): Report
{
    return Report::of(Overall::Degraded, Findings::of(
        Finding::of(
            Check::of('disk.space'),
            Category::Storage,
            'The disk is nearly full',
            Conclusion::Warned,
            WhatTheCheckSaid::nothingWrong(),
            WhoPutItThere::bundled(),
        ),
    ));
}

/** The home screen, listening to a stream that says what a test scripts, in front of somebody. */
function aScreenListeningTo(AStackThatSpeaksUp $stream, ?ACaptureInMemory $window = null, bool $signedIn = true, ?Report $run = null): AScreenListening
{
    $stack = theStackBeingListenedTo();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $clock = FrozenClock::at(AScreenListening::secondsAfterOpening(0));
    $window ??= ACaptureInMemory::inFront();
    $standings = StandingsInMemory::working();

    $screen = new HowThisStackIs(
        AStackThatWasAsked::saying($run ?? aRunWithOneFinding()),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening($stream, $clock, $window, $standings),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return new AScreenListening($screen, $stream, $clock, $window, $keychain, $standings);
}

/**
 * Whether the screen's runloop would go round again.
 *
 * The framework keeps it to itself, and letting go of the stream must not be
 * all that stopping does: a screen that let go and went on running would be
 * the one on the glass still.
 */
function whetherTheRunloopGoesOn(NativeComponent $screen): bool
{
    $asked = Closure::bind(static fn(NativeComponent $running): bool => $running->nativeRunning, null, NativeComponent::class);

    return $asked($screen);
}

it('opens the stream once the screen is up, and draws the summary it hears', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAFillingDisk())));

    $listening->screen->mount();

    expect($listening->stream->asked())->toBe(1)
        ->and($listening->screen->summary()->said)->toBe(HowItStands::Broken->saidOnTheScreen())
        ->and($listening->screen->summary()->worst)->toBe('The disk is full')
        ->and($listening->screen->summary()->howMany)->toBe(1);
});

it('takes what arrived on every wake, and draws the newest summary', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(
        WhatWasHeard::nothing(),
        WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing())),
    ));

    $listening->wakesAt(0);

    expect($listening->screen->summary()->said)->toBe('health.summary.waiting');

    $listening->wakesAt(2);

    expect($listening->screen->summary()->said)->toBe(HowItStands::Healthy->saidOnTheScreen())
        ->and($listening->stream->asked())->toBe(2);
});

it('lets go of the stream while nobody can see the screen, and does not ask it', function (): void {
    $window = ACaptureInMemory::inFront();
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAFillingDisk())), $window);

    $listening->wakesAt(0);
    $window->backgrounded();
    $listening->wakesAt(2)->wakesAt(4);

    expect($listening->stream->asked())->toBe(1)
        ->and($listening->stream->lettingsGo())->toBe(2)
        ->and($listening->screen->summary()->said)->toBe(HowItStands::Unknown->saidOnTheScreen())
        ->and($listening->screen->summary()->worst)->toBe('The disk is full');
});

it('opens the stream again on the first wake it is back in front, without waiting out a break', function (): void {
    $window = ACaptureInMemory::away();
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAFillingDisk())), $window);

    $listening->wakesAt(0);

    expect($listening->stream->asked())->toBe(0);

    $window->cameBack();
    $listening->wakesAt(1);

    expect($listening->stream->asked())->toBe(1)
        ->and($listening->screen->summary()->said)->toBe(HowItStands::Broken->saidOnTheScreen());
});

it('reads a stream that closed as not knowing, and opens it again only once the break is waited out', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::thenEnding(WhatWasHeard::said(aSummaryOfAFillingDisk())));

    $listening->wakesAt(0)->wakesAt(2);

    expect($listening->screen->summary()->said)->toBe(HowItStands::Unknown->saidOnTheScreen())
        ->and($listening->screen->summary()->ago->said)->toBe('health.ago.minutes');

    $listening->wakesAt(11);

    expect($listening->stream->asked())->toBe(2);

    $listening->wakesAt(12);

    expect($listening->stream->asked())->toBe(3);
});

it('lets go of a stream that has been silent past twice the heartbeat, and reads it as not knowing', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAFillingDisk())));

    $listening->wakesAt(0)->wakesAt(30);

    expect($listening->stream->lettingsGo())->toBe(0)
        ->and($listening->screen->summary()->said)->toBe(HowItStands::Broken->saidOnTheScreen());

    $listening->wakesAt(31);

    expect($listening->stream->lettingsGo())->toBe(1)
        ->and($listening->screen->summary()->said)->toBe(HowItStands::Unknown->saidOnTheScreen());
});

it('lets go of the session a stack refused on the stream, and keeps it for any other obstacle', function (): void {
    $refused = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))));
    $refused->wakesAt(0);

    $unanswered = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    $unanswered->wakesAt(0);

    expect($refused->keychain->isHolding(theStackBeingListenedTo()->id()))->toBeFalse()
        ->and($unanswered->keychain->isHolding(theStackBeingListenedTo()->id()))->toBeTrue()
        ->and($unanswered->screen->summary()->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($unanswered->screen->summary()->remedy)->toEqual(KindOfObstacle::StackDidNotAnswer->remedy());
});

it('does not listen without a session, and looks for one again after a break', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAFillingDisk())), signedIn: false);

    $listening->wakesAt(0)->wakesAt(2);

    expect($listening->stream->asked())->toBe(0)
        ->and($listening->screen->summary()->said)->toBe('health.summary.waiting');
});

it('lets go of the stream when the screen stops, and holds what it heard as not current', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAFillingDisk())));

    $listening->wakesAt(0);
    $listening->screen->stop();

    expect($listening->stream->lettingsGo())->toBe(1)
        ->and($listening->screen->summary()->said)->toBe(HowItStands::Unknown->saidOnTheScreen())
        ->and(whetherTheRunloopGoesOn($listening->screen))->toBeFalse();

    $listening->wakesAt(1);

    expect($listening->stream->asked())->toBe(2);
});

it('offers somebody to ask under what it counts, where an item said nothing to try', function (): void {
    $nothingToTry = TheHealthSummary::of(
        HowItStands::Broken,
        1,
        'The disk is full',
        WhatStoppedMoving::nothing(),
        AnAffectedItem::of(Check::of('disk.space'), Severity::Error, 'The disk is full', 'Nothing new can be downloaded', Remedies::none(), WhatFollowedFromIt::of()),
    );
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said($nothingToTry)));
    $listening->wakesAt(0);

    expect(WhatTheDeviceWouldDraw::by($listening->screen)->offers())->toContain(__('device.share_diagnostics'));
});

it('offers nobody to ask under what it counts where every item names something to try', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAFillingDisk())));
    $listening->wakesAt(0);

    expect(WhatTheDeviceWouldDraw::by($listening->screen)->offers())->not->toContain(__('device.share_diagnostics'));
});

it('draws what it counts under the line, as a figure and each item with its remedies', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAFillingDisk())));
    $listening->wakesAt(0);

    $drawn = WhatTheDeviceWouldDraw::by($listening->screen)->said();

    // The worst thing is the heading, so the word's sentence is not said
    // beside it; a current line says no age and no cadence, because it is
    // being listened to.
    expect($drawn)->toContain('The disk is full')
        ->and($drawn)->not->toContain(__(HowItStands::Broken->saidOnTheScreen()))
        ->and($drawn)->toContain(__('health.summary.needs_you'))
        ->and($drawn)->toContain('1')
        ->and($drawn)->toContain(trans_choice('health.summary.wanting', 1))
        ->and($drawn)->not->toContain(__('health.summary.as_of', ['ago' => trans_choice('health.ago.minutes', 0)]))
        ->and($drawn)->toContain('Nothing new can be downloaded')
        ->and($drawn)->toContain('Make room')
        ->and($drawn)->toContain(__('health.summary.also', ['what' => 'Imports are failing']));
});

it('draws nothing it counts on a healthy line: no label, no figure, nothing to ask', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(
        WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing())),
    ));
    $listening->wakesAt(0);

    $drawn = WhatTheDeviceWouldDraw::by($listening->screen)->said();

    expect($drawn)->toContain(__(HowItStands::Healthy->saidOnTheScreen()))
        ->and($drawn)->not->toContain(__('health.summary.needs_you'))
        ->and(WhatTheDeviceWouldDraw::by($listening->screen)->offers())->not->toContain(__('device.share_diagnostics'));
});

/**
 * What a screen reader hears for each glyph on a frame, in the order drawn.
 *
 * @param array<array-key, mixed> $tree
 *
 * @return list<string>
 */
function whatAReaderHearsForAGlyph(array $tree): array
{
    $heard = [];

    array_walk_recursive($tree, static function (mixed $value, int|string $prop) use (&$heard): void {
        if ($prop === 'a11y_label' && is_string($value)) {
            $heard[] = $value;
        }
    });

    return $heard;
}

it('says how the stack stands in a word under a heading that names the worst thing, and to a reader of its glyph', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAFillingDisk())));
    $listening->wakesAt(0);

    $word = __(HowItStands::Broken->saidInAWord());

    expect(WhatTheDeviceWouldDraw::by($listening->screen)->said())->toContain('The disk is full')
        ->and(WhatTheDeviceWouldDraw::by($listening->screen)->said())->toContain($word)
        ->and(whatAReaderHearsForAGlyph(WhatTheDeviceWouldDraw::tree($listening->screen)))->toContain($word);
});

it('heads a line that names nothing with its own sentence, and says no age while it is current', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(
        WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing())),
    ));
    $listening->wakesAt(0);

    $drawn = WhatTheDeviceWouldDraw::by($listening->screen)->said();

    expect($drawn)->toContain(__(HowItStands::Healthy->saidOnTheScreen()))
        ->and($drawn)->not->toContain(trans_choice('health.summary.wanting', 1))
        ->and($drawn)->not->toContain(__('health.summary.as_of', ['ago' => trans_choice('health.ago.minutes', 0)]));
});

it('draws when a line that is not current was heard, and what stopped the stream', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(
        WhatWasHeard::said(aSummaryOfAFillingDisk()),
        WhatWasHeard::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
    ));

    $listening->wakesAt(0)->wakesAt(120);

    $drawn = WhatTheDeviceWouldDraw::by($listening->screen)->said();

    // What it last named is still the heading, with the unknown glyph and
    // when it was updated beside it.
    expect($drawn)->toContain('The disk is full')
        ->and($listening->screen->summary()->tone)->toBe('unknown')
        ->and($drawn)->toContain(__('health.summary.as_of', ['ago' => trans_choice('health.ago.minutes', 2)]))
        ->and($drawn)->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));
});

/** What the list would read back for the stack being listened to, flattened to one string. */
function whatTheListHolds(StandingsInMemory $standings): string
{
    return $standings->lastKnownOf(theStackBeingListenedTo()->id())->either(
        waiting: static fn(): Code => Code::of('nothing-held'),
        holding: static fn(Reading $reading): Code => $reading->either(
            live: static fn(): Code => Code::of('live'),
            retained: static fn(object $standing, Instant $at): Code => Code::of(sprintf(
                '%s|%d',
                $standing instanceof HowItStands ? $standing->value : 'not-a-word',
                $at->epochSeconds() - AScreenListening::secondsAfterOpening(0)->epochSeconds(),
            )),
        ),
    )->shown();
}

it('keeps each word it hears for the list, with when it was heard', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(
        WhatWasHeard::said(aSummaryOfAFillingDisk()),
        WhatWasHeard::nothing(),
        WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing())),
    ));

    $listening->wakesAt(0);

    expect(whatTheListHolds($listening->standings))->toBe('broken|0');

    $listening->wakesAt(2);

    expect(whatTheListHolds($listening->standings))->toBe('broken|0');

    $listening->wakesAt(4);

    expect(whatTheListHolds($listening->standings))->toBe('healthy|4');
});

it('keeps nothing for the list where nothing was said', function (): void {
    $quiet = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::nothing(), WhatWasHeard::aSignOfLife()));
    $quiet->wakesAt(0)->wakesAt(2);

    $refused = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    $refused->wakesAt(0);

    $ended = aScreenListeningTo(AStackThatSpeaksUp::thenEnding());
    $ended->wakesAt(0);

    expect(whatTheListHolds($quiet->standings))->toBe('nothing-held')
        ->and(whatTheListHolds($refused->standings))->toBe('nothing-held')
        ->and(whatTheListHolds($ended->standings))->toBe('nothing-held');
});

it('draws the summary it heard where the device would not keep the word', function (): void {
    $stack = theStackBeingListenedTo();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new HowThisStackIs(
        AStackThatWasAsked::saying(aRunWithOneFinding()),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAFillingDisk())), FrozenClock::at(AScreenListening::secondsAfterOpening(0)), standings: StandingsInMemory::refusing()),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);
    $screen->listen();

    expect($screen->summary()->said)->toBe(HowItStands::Broken->saidOnTheScreen());
});

/**
 * Where on the frame a line is drawn, counted from the top.
 *
 * @param list<string> $said
 */
function whereOnTheHealthScreen(array $said, string $line): int
{
    $at = array_search($line, $said, strict: true);

    return is_int($at) ? $at : throw new RuntimeException(sprintf('"%s" is not drawn.', $line));
}

/**
 * Where on the frame the line a catalogue key names is drawn.
 *
 * @param list<string> $said
 */
function whereTheHeadingIsDrawn(array $said, string $key): int
{
    $line = __($key);

    return whereOnTheHealthScreen($said, is_string($line) ? $line : $key);
}

/** A summary whose queue has one cause holding twenty items, one orphan, and one slow download. */
function aSummaryOfAStoppedQueue(): TheHealthSummary
{
    return TheHealthSummary::of(
        HowItStands::Degraded,
        0,
        '',
        WhatStoppedMoving::of(
            AStoppage::of(HowItStopped::RepeatedImportFailure, 'Permission denied on /media/films', 20, 'Access to the path is denied.', 10_800),
            AStoppage::of(HowItStopped::Orphaned, 'Heat', 1, '', 172_800),
            AStoppage::of(HowItStopped::Slow, 'Dune', 1, '', 600),
        ),
    );
}

it('draws what stopped moving by kind, one row per cause, with the service\'s words and how long', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAStoppedQueue())));
    $listening->wakesAt(0);

    $drawn = WhatTheDeviceWouldDraw::by($listening->screen)->said();
    $said = explode(PortRow::BETWEEN, implode(PortRow::BETWEEN, $drawn));

    expect($said)->toContain(__('health.stopped_heading'))
        ->and($said)->toContain(__('health.stopped.repeated-import-failure'))
        ->and($said)->toContain('Permission denied on /media/films')
        ->and($said)->toContain(trans_choice('health.stopped_stands_for', 20))
        ->and($said)->toContain(__('health.stopped_blocking', ['words' => 'Access to the path is denied.']))
        ->and($said)->toContain(trans_choice('health.held_for.hours', 3))
        ->and($said)->toContain(__('health.stopped.orphaned'))
        ->and($said)->toContain(trans_choice('health.held_for.days', 2));
});

it('draws slow apart from and after what is stuck, under a heading that says it needs no fix', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAStoppedQueue())));
    $listening->wakesAt(0);

    $said = WhatTheDeviceWouldDraw::by($listening->screen)->said();

    expect($said)->toContain(__('health.slow_explained'))
        ->and(whereOnTheHealthScreen($said, 'Heat'))->toBeGreaterThan(whereTheHeadingIsDrawn($said, 'health.stopped_heading'))
        ->and(whereOnTheHealthScreen($said, 'Heat'))->toBeLessThan(whereTheHeadingIsDrawn($said, 'health.slow_heading'))
        ->and(whereOnTheHealthScreen($said, 'Dune'))->toBeGreaterThan(whereTheHeadingIsDrawn($said, 'health.slow_heading'));
});

it('offers the trace of a stopped item, and of no cause several items share', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAStoppedQueue())));
    $listening->wakesAt(0);

    $offers = WhatTheDeviceWouldDraw::by($listening->screen)->offers();

    $roads = array_map(static fn(string $offer): string => explode(PortRow::BETWEEN, $offer)[0], $offers);

    expect($roads)->toContain(__('health.trace.road_in', ['item' => 'Heat']))
        ->and($roads)->toContain(__('health.trace.road_in', ['item' => 'Dune']))
        ->and($roads)->not->toContain(__('health.trace.road_in', ['item' => 'Permission denied on /media/films']))
        ->and(NativeRouter::resolve($listening->screen->traceOf('Heat')))->toHaveKey('params.service', 'Heat');
});

it('draws no stopped heading and no slow heading where nothing has stopped', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAFillingDisk())));
    $listening->wakesAt(0);

    $said = WhatTheDeviceWouldDraw::by($listening->screen)->said();

    expect($said)->not->toContain(__('health.stopped_heading'))
        ->and($said)->not->toContain(__('health.slow_heading'));
});

it('reads a service that stopped plainly at the top, with its standing, and carries its exit code to its logs alone', function (): void {
    $gluetun = TheHealthSummary::of(
        HowItStands::Critical,
        1,
        'Gluetun stopped with an error',
        WhatStoppedMoving::nothing(),
        AnAffectedItem::ofAServiceThatExited(
            Check::of('service.gluetun'),
            Severity::Critical,
            'Gluetun stopped with an error',
            'what depends on Gluetun is not safe to keep running while it is down',
            Remedies::of(Remedy::of('start Gluetun again')),
            WhatFollowedFromIt::of(),
            1,
        ),
    );
    $listening = aScreenListeningTo(
        AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said($gluetun)),
        run: Report::of(Overall::Broken, Findings::of(
            Finding::of(
                Check::of('service.gluetun'),
                Category::Services,
                'Gluetun',
                Conclusion::Failed,
                WhatTheCheckSaid::nothingWrong(),
                WhoPutItThere::bundled(),
            )->about(AboutWhat::theNamedService('gluetun', 'Gluetun')),
        )),
    );

    $listening->screen->mount();
    $drawn = WhatTheDeviceWouldDraw::by($listening->screen)->said();

    expect($drawn)->toContain('Gluetun stopped with an error', whatTheTopCalls('health.standing_short.critical'))
        ->and(implode("\n", $drawn))->not->toContain('exit')
        ->and($listening->screen->findings()[0]->carriedToTheLogs())->toBe(['exited' => '1', 'called' => 'Gluetun']);
});

/** What the catalogue says for a key, as the text it is. */
function whatTheTopCalls(string $key): string
{
    $said = __($key);

    return is_string($said) ? $said : '';
}
