<?php

declare(strict_types=1);

use Modules\Connection\Api\ClearingWhatCannotBeRead;
use Modules\Connection\Api\Opening;
use Modules\Health\Api\KeepingTheLastReading;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\YourStacks;
use Tests\Support\ALockScreen;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\HealthReadingsInMemory;
use Tests\Support\Fakes\ReadingsKeptForInMemory;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatThePhoneKeeps;

// What the phone kept, drawn on opening for what it is.
//
// A stack's screen opens on the summary kept from an earlier session, with
// when it was read, before the stack is asked anything, and the fresh summary
// replaces it. The launch clears what was kept where the key that sealed it has
// gone, says so once, and does neither behind the lock.
//
// Asked of rendered frames, because every one of these is a claim about what
// the glass shows, and a template carries every branch it has.

/** The moment the screens here are opened at. Named for this file (`G10`). */
const WHEN_THE_STACK_WAS_OPENED = 1_790_000_000;

/** Two hours: how long before the opening the kept summary was heard. */
const TWO_HOURS_EARLIER = 7_200;

/** A day longer than a reading is kept for. */
const THIRTY_ONE_DAYS = 2_678_400;

/** The machine whose summary was kept. */
function theStackWhoseSummaryWasKept(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('f', Nonce::SHORTEST))),
        StackName::of('The attic'),
        Address::of('https://192.168.1.45:8443'),
        Fingerprint::of(str_repeat('e', Fingerprint::CHARACTERS)),
    );
}

/** What the phone kept of the attic's health, two hours before it was opened. */
function whatTheAtticKept(): KeepingTheLastReading
{
    $keeping = new KeepingTheLastReading(ASealInMemory::working(), HealthReadingsInMemory::empty(), ReadingsKeptForInMemory::standard(), FrozenClock::at(Instant::atEpochSeconds(0)));
    $keeping->keep(
        theStackWhoseSummaryWasKept()->id(),
        TheHealthSummary::of(HowItStands::Broken, 1, 'The disk that holds the photos is full', WhatStoppedMoving::nothing()),
        Instant::atEpochSeconds(WHEN_THE_STACK_WAS_OPENED - TWO_HOURS_EARLIER),
    );

    return $keeping;
}

/** The attic's screen, over a stream that says what a test scripts. */
function theAtticsScreen(KeepingTheLastReading $keeping, AStackThatSpeaksUp $stream): HowThisStackIs
{
    $stack = theStackWhoseSummaryWasKept();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new HowThisStackIs(
        AStackThatWasAsked::saying(Report::of(Overall::Healthy, Findings::of())),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        $stream,
        FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_STACK_WAS_OPENED)),
        ACaptureInMemory::inFront(),
        StandingsInMemory::working(),
        $keeping,
        new AppsSettingsThatOpen(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('draws the kept summary with its age on the first frame, and the fresh one replaces it', function (): void {
    $screen = theAtticsScreen(
        whatTheAtticKept(),
        AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing()))),
    );

    $first = WhatTheDeviceWouldDraw::whileItOpens($screen)->said();

    // Kept, so never current: the word is unknown, the age is beside it, and
    // what it last named is still the heading.
    expect($first)->toContain(__(HowItStands::Unknown->saidInAWord()))
        ->and($first)->toContain(__('health.summary.as_of', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($first)->toContain('The disk that holds the photos is full');

    $screen->mount();

    $fresh = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($fresh)->toContain(__(HowItStands::Healthy->saidOnTheScreen()))
        ->and($fresh)->not->toContain(__('health.summary.as_of', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($fresh)->not->toContain('The disk that holds the photos is full');
});

it('keeps the fresh summary for the next opening', function (): void {
    $keeping = whatTheAtticKept();
    $screen = theAtticsScreen(
        $keeping,
        AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing()))),
    );

    $screen->mount();

    $next = WhatTheDeviceWouldDraw::whileItOpens(theAtticsScreen($keeping, AStackThatSpeaksUp::holdingOpen()))->said();

    expect($next)->toContain(__('health.summary.as_of', ['ago' => trans_choice('health.ago.minutes', 0)]))
        ->and($next)->not->toContain('The disk that holds the photos is full');
});

it('opens on the platform\'s indicator, and on nothing kept, where nothing was kept', function (): void {
    $screen = theAtticsScreen(WhatThePhoneKeeps::nothingYet(), AStackThatSpeaksUp::holdingOpen());

    expect(WhatTheDeviceWouldDraw::whileItOpens($screen)->said())->toBe([]);
});

/** The launch screen, over a seal and a store in whatever state a test arranges. */
function theLaunchOver(ClearingWhatCannotBeRead $clearing, KeepingTheLastReading $keeping): YourStacks
{
    $stacks = StacksInMemory::holding(theStackWhoseSummaryWasKept());

    return new YourStacks(
        $stacks,
        AKeychainInMemory::working(),
        AShareSheetThatWasOffered::working(),
        StandingsInMemory::working(),
        FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_STACK_WAS_OPENED)),
        new Opening($stacks, ADeviceOnANetwork::connected()),
        $clearing,
        $keeping,
        AStackThatSpeaksUp::holdingOpen(),
        ACaptureInMemory::inFront(),
        WhatThePhoneKeeps::nothingToFinish(),
        AroundThePhone::alreadyOpened(),
        new AppsSettingsThatOpen(),
    );
}

/** A seal whose keys were made on an earlier launch, and have gone since. */
function aSealWhoseKeysHaveGone(): ASealInMemory
{
    $seal = ASealInMemory::working();
    $seal->standing();

    return $seal->losesItsKeys();
}

it('clears what the phone kept where its key has gone, and says so once', function (): void {
    $store = HealthReadingsInMemory::empty();
    $seal = aSealWhoseKeysHaveGone();
    $keeping = new KeepingTheLastReading($seal, $store, ReadingsKeptForInMemory::standard(), FrozenClock::at(Instant::atEpochSeconds(0)));
    $store->holdsOneALaterBuildWrote($seal->stack(theStackWhoseSummaryWasKept()->id()));

    $launch = theLaunchOver(new ClearingWhatCannotBeRead($seal, $store), $keeping);

    expect(WhatTheDeviceWouldDraw::by($launch)->said())->toContain(__('connection.saved_data_cleared'))
        ->and($store->forgetEverything()->howMany())->toBe(0);

    $again = theLaunchOver(new ClearingWhatCannotBeRead($seal, $store), $keeping);

    expect(WhatTheDeviceWouldDraw::by($again)->said())->not->toContain(__('connection.saved_data_cleared'));
});

it('touches nothing kept while the app is locked, and clears it once the lock is passed', function (): void {
    $store = HealthReadingsInMemory::empty();
    $seal = aSealWhoseKeysHaveGone();
    $attic = $seal->stack(theStackWhoseSummaryWasKept()->id());
    $store->holdsOneALaterBuildWrote($attic);
    $clearing = new ClearingWhatCannotBeRead($seal, $store);
    $keeping = new KeepingTheLastReading($seal, $store, ReadingsKeptForInMemory::standard(), FrozenClock::at(Instant::atEpochSeconds(0)));

    // While the lock stands the navigation stack builds the lock screen in
    // place of the launch, and the lock screen reads nothing kept.
    WhatTheDeviceWouldDraw::by(ALockScreen::over(ADeviceThatKnowsYou::refusing()));

    expect($store->newest($attic)->either(
        found: static fn(): Code => Code::of('found'),
        none: static fn(): Code => Code::of('none'),
        unreadable: static fn(): Code => Code::of('still kept'),
    )->shown())->toBe('still kept');

    expect(WhatTheDeviceWouldDraw::by(theLaunchOver($clearing, $keeping))->said())
        ->toContain(__('connection.saved_data_cleared'));
});

it('forgets on opening what was read longer ago than a reading is kept', function (): void {
    $store = HealthReadingsInMemory::empty();
    $keeping = new KeepingTheLastReading(ASealInMemory::working(), $store, ReadingsKeptForInMemory::standard(), FrozenClock::at(Instant::atEpochSeconds(0)));
    $keeping->keep(
        theStackWhoseSummaryWasKept()->id(),
        TheHealthSummary::of(HowItStands::Broken, 0, '', WhatStoppedMoving::nothing()),
        Instant::atEpochSeconds(WHEN_THE_STACK_WAS_OPENED - THIRTY_ONE_DAYS),
    );

    WhatTheDeviceWouldDraw::by(theLaunchOver(WhatThePhoneKeeps::nothingToClear(), $keeping));

    expect($store->forgetEverything()->howMany())->toBe(0);
});
