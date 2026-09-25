<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAffectedItem;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
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
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
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

/** A moment, counted in seconds from one a test starts at. */
function secondsAfterOpening(int $seconds): Instant
{
    return Instant::atEpochSeconds(1_790_000_000 + $seconds);
}

/** A summary counting one thing, with every part an item has. */
function aSummaryOfAFillingDisk(): TheHealthSummary
{
    return TheHealthSummary::of(
        HowItStands::Broken,
        1,
        'The disk is full',
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

/** Everything a test of the line reaches for: the screen, and what stands in for its ports. */
final readonly class AScreenListening
{
    public function __construct(
        public HowThisStackIs $screen,
        public AStackThatSpeaksUp $stream,
        public FrozenClock $clock,
        public ACaptureInMemory $window,
        public AKeychainInMemory $keychain,
    ) {}

    /** The screen wakes at a moment, as its poll would wake it. */
    public function wakesAt(int $seconds): self
    {
        $this->clock->moveTo(secondsAfterOpening($seconds));
        $this->screen->listen();

        return $this;
    }
}

/** The home screen, listening to a stream that says what a test scripts, in front of somebody. */
function aScreenListeningTo(AStackThatSpeaksUp $stream, ?ACaptureInMemory $window = null, bool $signedIn = true): AScreenListening
{
    $stack = theStackBeingListenedTo();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $clock = FrozenClock::at(secondsAfterOpening(0));
    $window ??= ACaptureInMemory::inFront();

    $screen = new HowThisStackIs(
        AStackThatWasAsked::saying(aRunWithOneFinding()),
        $keychain,
        StacksInMemory::holding($stack),
        $stream,
        $clock,
        $window,
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return new AScreenListening($screen, $stream, $clock, $window, $keychain);
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
        ->and($listening->screen->summary()->howMany)->toBe(1)
        ->and($listening->screen->cadence())->toBe(HowOften::WhileListening);
});

it('takes what arrived on every wake, and draws the newest summary', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(
        WhatWasHeard::nothing(),
        WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '')),
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
        ->and($listening->screen->summary()->ago->said)->toBe('health.ago.minutes')
        ->and($listening->screen->cadence())->toBe(HowOften::AfterABreak);

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
        ->and($listening->screen->summary()->said)->toBe(HowItStands::Unknown->saidOnTheScreen())
        ->and($listening->screen->cadence())->toBe(HowOften::AfterABreak);
});

it('lets go of the session a stack refused on the stream, and keeps it for any other obstacle', function (): void {
    $refused = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::met(Obstacle::CredentialWasRefused)));
    $refused->wakesAt(0);

    $unanswered = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::met(Obstacle::StackDidNotAnswer)));
    $unanswered->wakesAt(0);

    expect($refused->keychain->isHolding(theStackBeingListenedTo()->id()))->toBeFalse()
        ->and($unanswered->keychain->isHolding(theStackBeingListenedTo()->id()))->toBeTrue()
        ->and($unanswered->screen->summary()->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($unanswered->screen->summary()->remedy)->toBe(Obstacle::StackDidNotAnswer->remedy());
});

it('does not listen without a session, and looks for one again after a break', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAFillingDisk())), signedIn: false);

    $listening->wakesAt(0)->wakesAt(2);

    expect($listening->stream->asked())->toBe(0)
        ->and($listening->screen->summary()->said)->toBe('health.summary.waiting')
        ->and($listening->screen->cadence())->toBe(HowOften::AfterABreak);
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

it('opens the line out to what it counts, and folds it back', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(aSummaryOfAFillingDisk())));
    $listening->wakesAt(0);

    $folded = WhatTheDeviceWouldDraw::by($listening->screen)->said();

    $listening->screen->expand();
    $expanded = WhatTheDeviceWouldDraw::by($listening->screen)->said();

    $listening->screen->expand();

    expect($listening->screen->expanded)->toBeFalse()
        ->and($folded)->toContain(__(HowItStands::Broken->saidOnTheScreen()))
        ->and($folded)->toContain('The disk is full')
        ->and($folded)->toContain(trans_choice('health.summary.wanting', 1))
        ->and($folded)->not->toContain('Nothing new can be downloaded')
        ->and($expanded)->toContain('Nothing new can be downloaded')
        ->and($expanded)->toContain('Make room')
        ->and($expanded)->toContain(__('health.summary.also', ['what' => 'Imports are failing']))
        ->and($expanded)->toContain(__(HowOften::WhileListening->saidOnTheScreen(), ['count' => 2]));
});

it('draws when a line that is not current was heard, and what stopped the stream', function (): void {
    $listening = aScreenListeningTo(AStackThatSpeaksUp::holdingOpen(
        WhatWasHeard::said(aSummaryOfAFillingDisk()),
        WhatWasHeard::met(Obstacle::StackDidNotAnswer),
    ));

    $listening->wakesAt(0)->wakesAt(120);

    $drawn = WhatTheDeviceWouldDraw::by($listening->screen)->said();

    expect($drawn)->toContain(__(HowItStands::Unknown->saidOnTheScreen()))
        ->and($drawn)->toContain(__('health.summary.as_of', ['ago' => trans_choice('health.ago.minutes', 2)]))
        ->and($drawn)->toContain(__(Obstacle::StackDidNotAnswer->said()))
        ->and($drawn)->toContain(__(HowOften::AfterABreak->saidOnTheScreen(), ['count' => 10]));
});
