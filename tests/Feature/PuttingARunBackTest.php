<?php

declare(strict_types=1);

use Modules\Kernel\Api\AChangeAndWhy;
use Modules\Kernel\Api\AChangePutBack;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ARunAgreedTo;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\Change;
use Modules\Kernel\Api\ChangesAndWhy;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\HowPuttingARunBackIsGoing;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\WhatGoingBackDoes;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatWentBack;
use Modules\Kernel\Api\WhenItWasMade;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Presenters\HowTheRecordReads;
use Modules\Operator\Internal\Screens\PuttingThatRunBack;
use Modules\Operator\Internal\ViewModels\AChangeAndWhyAsShown;
use Modules\Operator\Internal\ViewModels\AChangeGoneBackAsShown;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\HowPuttingARunBackWent;
use Modules\Operator\Internal\ViewModels\WhatOneRecordedChangeSays;
use Modules\Operator\Internal\ViewModels\WhatPuttingARunBackWouldShow;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatKeepsARecord;
use Tests\Support\Fakes\AStackThatPutsRunsBack;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\NoticingWhatIsNew;
use Tests\Support\WhatTheDeviceWouldDraw;

// Putting back one run the record shows: the record's own rows first, as the
// agreement; the yes, sent for that run and nothing else; and the stack's
// report of it, leading with what was left.
//
// Here rather than in the operator module's own tests because a screen
// renders, and rendering needs the application.

/** The moment every age on this screen is measured against. */
const A_RUN_IS_PUT_BACK_AT = 1_790_150_000;

/** The stamp of the run these cases put back: two hours before the screen reads. */
const THE_RUN_PUT_BACK = '1790142800';

/** The machine a run is put back on. */
function theStackARunGoesBackOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A change of the run these cases put back, or of another where a case says. */
function aChangeOfTheRun(
    string $did,
    HowFarItGoesBack $reversal = HowFarItGoesBack::Whole,
    int $alongside = 2,
    int $at = 1_790_142_800,
): Change {
    return Change::made($did, 'reconfigure', 'sonarr', WhenItWasMade::at(Instant::atEpochSeconds($at)), $reversal, $alongside);
}

/** A record holding the run, and one change of another run between its two. */
function aRecordHoldingTheRun(HowFarItGoesBack $second = HowFarItGoesBack::Whole): TheRecord
{
    return TheRecord::reaching(
        'the last 50 runs',
        aChangeOfTheRun('Pointed Sonarr at the new library'),
        aChangeOfTheRun('Set the time zone', alongside: 1, at: 1_790_140_000),
        aChangeOfTheRun('Made the library directory', $second),
    );
}

/** The screen, opened on a run, with a keychain holding whatever a case says. */
function thePuttingARunBackScreen(
    AStackThatKeepsARecord $history,
    AStackThatPutsRunsBack $puttingBack,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
    string $stamp = THE_RUN_PUT_BACK,
): PuttingThatRunBack {
    $stack = theStackARunGoesBackOn();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new PuttingThatRunBack(
        $history,
        $puttingBack,
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        FrozenClock::at(Instant::atEpochSeconds(A_RUN_IS_PUT_BACK_AT)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
        NoticingWhatIsNew::fromNothing(),
    );
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $stamp]);

    return $screen;
}

/** A report, leaving and noting whatever a case says. */
function aReportOfTheRun(
    WhetherItWasRehearsed $rehearsed = WhetherItWasRehearsed::CarriedOut,
    ?ChangesAndWhy $left = null,
    ?ChangesAndWhy $noted = null,
    ?WhatWentBack $reversed = null,
): ARunPutBack {
    return ARunPutBack::reported(
        $rehearsed,
        $reversed ?? WhatWentBack::these(
            AChangePutBack::against('lemonfiber', WhatGoingBackDoes::Restore),
            AChangePutBack::against('/srv/films', WhatGoingBackDoes::Delete),
        ),
        $left ?? ChangesAndWhy::these(),
        $noted ?? ChangesAndWhy::these(),
    );
}

/**
 * Every field of the agreement as drawn, in one comparable row.
 *
 * @return array<string, mixed>
 */
function everythingTheAgreementShows(WhatPuttingARunBackWouldShow $shown): array
{
    return [
        'cameBack' => $shown->went->cameBack(),
        'isSignedIn' => $shown->went->isSignedIn,
        'met' => $shown->went->met,
        'namesARun' => $shown->namesARun,
        'isOnTheRecord' => $shown->isOnTheRecord,
        'goesBack' => $shown->goesBack,
        'when' => [$shown->whenSaid, $shown->whenCount],
        'alongside' => $shown->alongside,
        'changes' => array_map(static fn(WhatOneRecordedChangeSays $row): string => $row->did, $shown->changes),
    ];
}

/**
 * What an agreement with nothing in it says of every field, changed where a case says.
 *
 * @param  array<string, mixed> $changed
 * @return array<string, mixed>
 */
function nothingOfTheRunShown(array $changed): array
{
    return [
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'namesARun' => true,
        'isOnTheRecord' => false,
        'goesBack' => false,
        'when' => ['', 0],
        'alongside' => 0,
        'changes' => [],
        ...$changed,
    ];
}

/**
 * Every field of what became of the yes, in one comparable row.
 *
 * @return array<string, mixed>
 */
function everythingPuttingTheRunBackShows(HowPuttingARunBackWent $went): array
{
    return [
        'cameBack' => $went->went->cameBack(),
        'isSignedIn' => $went->went->isSignedIn,
        'met' => $went->went->met,
        'isWorking' => $went->isWorking,
        'hasEnded' => $went->hasEnded,
        'hasReport' => $went->hasReport,
        'rehearsed' => $went->rehearsed,
        'leftNothing' => $went->leftNothing,
        'said' => [$went->headline, $went->reversedSaid, $went->noneReversed],
        'reversed' => array_map(static fn(AChangeGoneBackAsShown $row): array => [$row->target, $row->doesSaid], $went->reversed),
        'left' => array_map(static fn(AChangeAndWhyAsShown $row): array => [$row->target, $row->because], $went->left),
        'noted' => array_map(static fn(AChangeAndWhyAsShown $row): array => [$row->target, $row->because], $went->noted),
        'refused' => $went->refused instanceof ARefusalAsShown ? [$went->refused->said, $went->refused->meaning, $went->refused->named] : null,
    ];
}

/**
 * What a yes with no report says of every field, changed where a case says.
 *
 * @param  array<string, mixed> $changed
 * @return array<string, mixed>
 */
function nothingReportedOfTheRun(array $changed): array
{
    return [
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'isWorking' => false,
        'hasEnded' => false,
        'hasReport' => false,
        'rehearsed' => false,
        'leftNothing' => false,
        'said' => ['', '', ''],
        'reversed' => [],
        'left' => [],
        'noted' => [],
        'refused' => null,
        ...$changed,
    ];
}

/**
 * Where on the frame a line is said, counted from the top; raised where it is not said at all.
 *
 * @param list<string> $said
 */
function whereOnTheFrameItIsSaid(array $said, mixed $line): int
{
    $at = array_search($line, $said, strict: true);

    if (! is_int($at)) {
        throw new RuntimeException(sprintf('The frame does not say `%s`, so where it says it cannot be compared.', is_string($line) ? $line : ''));
    }

    return $at;
}

/** The screen, agreed to and asked after once, against a stack reporting `$became`. */
function aRunAgreedToAndAskedAfter(HowPuttingARunBackIsGoing $became): PuttingThatRunBack
{
    $screen = thePuttingARunBackScreen(AStackThatKeepsARecord::with(aRecordHoldingTheRun()), AStackThatPutsRunsBack::saying($became));
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();

    return $screen;
}

it('shows what goes with the run from the record\'s own rows, before anything is agreed to', function (): void {
    $history = AStackThatKeepsARecord::with(aRecordHoldingTheRun());
    $puttingBack = AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning());
    $screen = thePuttingARunBackScreen($history, $puttingBack);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingTheAgreementShows($screen->answer()))->toBe([
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'namesARun' => true,
        'isOnTheRecord' => true,
        'goesBack' => true,
        'when' => [HowLongAgo::Hours->saidOnTheScreen(), 2],
        'alongside' => 2,
        'changes' => ['Pointed Sonarr at the new library', 'Made the library directory'],
    ])
        ->and($history->askings())->toBe(1)
        ->and($puttingBack->agreed())->toBe([])
        ->and($drawn->said())->toContain(trans_choice('stacks.run_back.goes_with_it', 2))
        ->and($drawn->said())->toContain('Made the library directory')
        ->and($drawn->said())->not->toContain('Set the time zone')
        ->and($drawn->said())->toContain(__('stacks.run_back.whole_or_nothing'))
        ->and($drawn->offers())->toContain(__('stacks.run_back.put_it_back'));
});

it('says a run of one change is the one change, and when the clock could not say', function (): void {
    $record = TheRecord::reaching('the last 50 runs', Change::made('Wrote the first configuration', 'seed', 'lemonfiber', WhenItWasMade::unreadable(), HowFarItGoesBack::Whole, 1));
    $screen = thePuttingARunBackScreen(AStackThatKeepsARecord::with($record), AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning()), stamp: '0');

    expect([$screen->answer()->whenSaid, $screen->answer()->whenCount])->toBe([HowTheRecordReads::CLOCK_UNREADABLE, 0])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(trans_choice('stacks.run_back.goes_with_it', 1));
});

it('offers nothing where a change of the run says it cannot go back, and sends no yes', function (): void {
    $puttingBack = AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning());
    $screen = thePuttingARunBackScreen(AStackThatKeepsARecord::with(aRecordHoldingTheRun(HowFarItGoesBack::None)), $puttingBack);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->goesBack)->toBeFalse()
        ->and($screen->answer()->isOnTheRecord)->toBeTrue()
        ->and($drawn->said())->toContain(__('stacks.run_back.cannot_go_back'))
        ->and($drawn->offers())->not->toContain(__('stacks.run_back.put_it_back'));

    $screen->agree();

    expect($puttingBack->agreed())->toBe([])
        ->and($screen->wasAgreedTo())->toBeFalse();
});

it('offers nothing for a stamp the record holds nothing under, and says why that can be', function (): void {
    $puttingBack = AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning());
    $screen = thePuttingARunBackScreen(AStackThatKeepsARecord::with(aRecordHoldingTheRun()), $puttingBack, stamp: '1790000000');
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingTheAgreementShows($screen->answer()))->toBe(nothingOfTheRunShown([]))
        ->and($drawn->said())->toContain(__('stacks.run_back.not_on_the_record'))
        ->and($drawn->offers())->not->toContain(__('stacks.run_back.put_it_back'))
        ->and($drawn->offers())->toContain(__('stacks.record.road_in'));

    $screen->agree();

    expect($puttingBack->agreed())->toBe([]);
});

it('names no run where it was opened on none, and asks the stack nothing', function (mixed $stamp): void {
    $history = AStackThatKeepsARecord::with(aRecordHoldingTheRun());
    $screen = thePuttingARunBackScreen($history, AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning()));
    $screen->setParams(['stack' => theStackARunGoesBackOn()->id()->stored(), 'service' => $stamp]);

    expect(everythingTheAgreementShows($screen->answer()))->toBe(nothingOfTheRunShown(['namesARun' => false]))
        ->and($history->askings())->toBe(0)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.run_back.names_no_run'));
})->with(['blank' => ['  '], 'not text' => [42]]);

it('asks for a session rather than the record where this device holds none', function (): void {
    $history = AStackThatKeepsARecord::with(aRecordHoldingTheRun());
    $screen = thePuttingARunBackScreen($history, AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning()), signedIn: false);

    expect(everythingTheAgreementShows($screen->answer()))->toBe(nothingOfTheRunShown(['cameBack' => false, 'isSignedIn' => false]))
        ->and($history->askings())->toBe(0)
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('connection.sign_in'));
});

it('offers nothing to agree to where the record could not be read, and lets go of a refused session', function (Obstacle $why, bool $stillSignedIn): void {
    $keychain = AKeychainInMemory::working();
    $puttingBack = AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning());
    $screen = thePuttingARunBackScreen(AStackThatKeepsARecord::met($why), $puttingBack, $keychain);

    expect($screen->answer()->went->cameBack())->toBeFalse()
        ->and($screen->answer()->went->isSignedIn)->toBe($stillSignedIn)
        ->and($keychain->isHolding(theStackARunGoesBackOn()->id()))->toBe($stillSignedIn)
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('stacks.run_back.put_it_back'));

    $screen->agree();

    expect($puttingBack->agreed())->toBe([]);
})->with([
    'not answering' => [Obstacle::of(KindOfObstacle::StackDidNotAnswer), true],
    'refused' => [Obstacle::of(KindOfObstacle::CredentialWasRefused), false],
]);

it('puts back exactly the run it showed, and says it is running without drawing progress nobody measured', function (): void {
    $puttingBack = AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning());
    $screen = thePuttingARunBackScreen(AStackThatKeepsARecord::with(aRecordHoldingTheRun()), $puttingBack);
    $screen->answer();
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(array_map(static fn(ARunAgreedTo $agreed): string => $agreed->run()->stamp(), $puttingBack->agreed()))->toBe([THE_RUN_PUT_BACK])
        ->and($screen->wasAgreedTo())->toBeTrue()
        ->and($screen->isWorking())->toBeTrue()
        ->and(everythingPuttingTheRunBackShows($screen->done()))->toBe(nothingReportedOfTheRun(['isWorking' => true]))
        ->and($drawn)->toContain(__('stacks.run_back.putting_back'))
        ->and($drawn)->toContain(__('stacks.run_back.no_progress_while_running'))
        ->and($puttingBack->followed())->toBe([]);
});

it('sends no yes before the record has been read', function (): void {
    $puttingBack = AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning());
    $screen = thePuttingARunBackScreen(AStackThatKeepsARecord::with(aRecordHoldingTheRun()), $puttingBack);
    $screen->agree();

    expect($puttingBack->agreed())->toBe([])
        ->and($screen->wasAgreedTo())->toBeFalse()
        ->and($screen->isWorking())->toBeFalse();
});

it('asks after a run on its cadence while it runs, by the handle the yes was answered with, and not while it shows the record', function (): void {
    $history = AStackThatKeepsARecord::with(aRecordHoldingTheRun());
    $puttingBack = AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning());
    $screen = thePuttingARunBackScreen($history, $puttingBack);
    $screen->answer();
    $screen->whileItRuns();
    $screen->answer();

    expect($history->askings())->toBe(1)
        ->and($puttingBack->followed())->toBe([]);

    $screen->agree();
    $screen->whileItRuns();
    $screen->done();

    expect($puttingBack->followed())->toHaveCount(1)
        ->and($puttingBack->followed()[0]->shown())->toBe(AStackThatPutsRunsBack::THE_JOB);
});

it('says all of it went back only where nothing was left, and what went back', function (): void {
    $screen = aRunAgreedToAndAskedAfter(HowPuttingARunBackIsGoing::done(aReportOfTheRun()));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingPuttingTheRunBackShows($screen->done()))->toBe(nothingReportedOfTheRun([
        'hasReport' => true,
        'leftNothing' => true,
        'said' => ['stacks.run_back.did.all', 'stacks.run_back.did.reversed', 'stacks.run_back.did.none_reversed'],
        'reversed' => [['lemonfiber', 'stacks.run_back.does.restore'], ['/srv/films', 'stacks.run_back.does.delete']],
    ]))
        ->and($drawn->said())->toContain(__('stacks.run_back.did.all'))
        ->and($drawn->said())->toContain(__('stacks.run_back.does.delete'))
        ->and($drawn->said())->not->toContain(__('stacks.run_back.a_rehearsal'))
        ->and($drawn->said())->not->toContain(__('stacks.run_back.noted'))
        ->and($drawn->offers())->toContain(__('stacks.record.road_in'));
});

it('leads with what was left and why, before what went back, and says what going back also means', function (): void {
    $report = aReportOfTheRun(
        left: ChangesAndWhy::these(AChangeAndWhy::said('sonarr', 'the service that made it did not answer')),
        noted: ChangesAndWhy::these(AChangeAndWhy::said('lemonfiber', 'the library stays where it was moved to')),
    );
    $screen = aRunAgreedToAndAskedAfter(HowPuttingARunBackIsGoing::done($report));
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(everythingPuttingTheRunBackShows($screen->done())['left'])->toBe([['sonarr', 'the service that made it did not answer']])
        ->and(everythingPuttingTheRunBackShows($screen->done())['noted'])->toBe([['lemonfiber', 'the library stays where it was moved to']])
        ->and($screen->done()->leftNothing)->toBeFalse()
        ->and($said)->not->toContain(__('stacks.run_back.did.all'))
        ->and(whereOnTheFrameItIsSaid($said, __('stacks.run_back.did.not_all')))
        ->toBeLessThan(whereOnTheFrameItIsSaid($said, 'the service that made it did not answer'))
        ->and(whereOnTheFrameItIsSaid($said, 'the service that made it did not answer'))
        ->toBeLessThan(whereOnTheFrameItIsSaid($said, __('stacks.run_back.noted')))
        ->and(whereOnTheFrameItIsSaid($said, 'the library stays where it was moved to'))
        ->toBeLessThan(whereOnTheFrameItIsSaid($said, __('stacks.run_back.did.reversed')));
});

it('labels a rehearsal as one and says it in the tense of what would happen', function (): void {
    $report = aReportOfTheRun(
        WhetherItWasRehearsed::Rehearsed,
        left: ChangesAndWhy::these(AChangeAndWhy::said('sonarr', 'it goes back only where that service is answering')),
        reversed: WhatWentBack::these(),
    );
    $screen = aRunAgreedToAndAskedAfter(HowPuttingARunBackIsGoing::done($report));
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(everythingPuttingTheRunBackShows($screen->done())['said'])
        ->toBe(['stacks.run_back.would.not_all', 'stacks.run_back.would.reversed', 'stacks.run_back.would.none_reversed'])
        ->and($screen->done()->rehearsed)->toBeTrue()
        ->and($said)->toContain(__('stacks.run_back.a_rehearsal'))
        ->and($said)->toContain(__('stacks.run_back.would.not_all'))
        ->and($said)->toContain(__('stacks.run_back.would.none_reversed'))
        ->and($said)->not->toContain(__('stacks.run_back.did.reversed'));
});

it('says a rehearsal that would leave nothing would put all of it back', function (): void {
    $screen = aRunAgreedToAndAskedAfter(HowPuttingARunBackIsGoing::done(aReportOfTheRun(WhetherItWasRehearsed::Rehearsed)));

    expect($screen->done()->headline)->toBe('stacks.run_back.would.all')
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.run_back.would.all'));
});

it('says nothing went back where the stack put nothing back', function (): void {
    $report = aReportOfTheRun(left: ChangesAndWhy::these(AChangeAndWhy::said('sonarr', 'it did not answer')), reversed: WhatWentBack::these());
    $screen = aRunAgreedToAndAskedAfter(HowPuttingARunBackIsGoing::done($report));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.run_back.did.none_reversed'))
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.run_back.did.not_all'));
});

it('says a run the stack has no outcome for is not known, rather than failed', function (): void {
    $screen = aRunAgreedToAndAskedAfter(HowPuttingARunBackIsGoing::ended());
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingPuttingTheRunBackShows($screen->done()))->toBe(nothingReportedOfTheRun(['hasEnded' => true]))
        ->and($drawn->said())->toContain(__('stacks.run_back.no_outcome'))
        ->and($drawn->said())->toContain(__('stacks.run_back.no_outcome_action'))
        ->and($drawn->offers())->toContain(__('stacks.record.road_in'));
});

it('says what stood in the way of a yes the stack refused, and reads the record afresh', function (): void {
    $history = AStackThatKeepsARecord::with(aRecordHoldingTheRun());
    $puttingBack = AStackThatPutsRunsBack::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $screen = thePuttingARunBackScreen($history, $puttingBack);
    $screen->answer();
    $screen->agree();

    expect(everythingPuttingTheRunBackShows($screen->done()))->toBe(nothingReportedOfTheRun(['cameBack' => false, 'met' => KindOfObstacle::StackDidNotAnswer->said()]))
        ->and($screen->isWorking())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));

    $screen->again();

    expect($screen->wasAgreedTo())->toBeFalse()
        ->and($screen->answer()->went->cameBack())->toBeTrue()
        ->and($history->askings())->toBe(2)
        ->and($puttingBack->followed())->toBe([]);
});

it('says a run the stack would not put back in its words, apart from a stack that could not be reached, and does not offer asking again', function (): void {
    $why = ARefusalInItsWords::said(
        "A region lemonfiber wrote into one of the stack's files could not be taken out",
        'Everything before it was put back; this region is still in the file.',
        WhatTheRefusalNamed::as('/srv/stack/compose.yaml: permission denied'),
    );
    $screen = aRunAgreedToAndAskedAfter(HowPuttingARunBackIsGoing::refused($why));
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $unreachable = thePuttingARunBackScreen(AStackThatKeepsARecord::with(aRecordHoldingTheRun()), AStackThatPutsRunsBack::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $unreachable->answer();
    $unreachable->agree();

    expect(everythingPuttingTheRunBackShows($screen->done()))->toBe(nothingReportedOfTheRun(['refused' => [
        "A region lemonfiber wrote into one of the stack's files could not be taken out",
        'Everything before it was put back; this region is still in the file.',
        '/srv/stack/compose.yaml: permission denied',
    ]]))
        ->and($screen->isWorking())->toBeFalse()
        ->and($drawn->said())->toContain(__('stacks.run_back.refused'))
        ->and($drawn->said())->toContain("A region lemonfiber wrote into one of the stack's files could not be taken out")
        ->and($drawn->said())->toContain('Everything before it was put back; this region is still in the file.')
        ->and($drawn->said())->toContain(__('stacks.refusal.named', ['named' => '/srv/stack/compose.yaml: permission denied']))
        ->and($drawn->said())->toContain(__('stacks.run_back.refused_same_answer'))
        ->and($drawn->said())->not->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($drawn->offers())->toContain(__('stacks.record.road_in'))
        ->and($drawn->offers())->not->toContain(__('health.ask_again'))
        ->and(WhatTheDeviceWouldDraw::by($unreachable)->said())->not->toContain(__('stacks.run_back.refused'))
        ->and(WhatTheDeviceWouldDraw::by($unreachable)->offers())->toContain(__('health.ask_again'));
});

it('draws only the stack\'s sentence where it meant and named nothing more', function (): void {
    $why = ARefusalInItsWords::said('Nothing was changed at 1790150000', '', WhatTheRefusalNamed::nothing());
    $said = WhatTheDeviceWouldDraw::by(aRunAgreedToAndAskedAfter(HowPuttingARunBackIsGoing::refused($why)))->said();

    expect(whereOnTheFrameItIsSaid($said, __('stacks.run_back.refused_same_answer')) - whereOnTheFrameItIsSaid($said, __('stacks.run_back.refused')))
        ->toBe(2)
        ->and(whereOnTheFrameItIsSaid($said, 'Nothing was changed at 1790150000'))
        ->toBe(whereOnTheFrameItIsSaid($said, __('stacks.run_back.refused')) + 1);
});

it('asks after the same handle when asked again after a yes the stack took on', function (): void {
    $history = AStackThatKeepsARecord::with(aRecordHoldingTheRun());
    $puttingBack = AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning());
    $screen = thePuttingARunBackScreen($history, $puttingBack);
    $screen->answer();
    $screen->agree();
    $screen->again();
    $screen->done();

    expect($screen->wasAgreedTo())->toBeTrue()
        ->and($puttingBack->followed())->toHaveCount(1)
        ->and($history->askings())->toBe(1);
});

it('reads the record afresh when asked again before a yes', function (): void {
    $history = AStackThatKeepsARecord::with(aRecordHoldingTheRun());
    $puttingBack = AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning());
    $screen = thePuttingARunBackScreen($history, $puttingBack);
    $screen->answer();
    $screen->again();
    $screen->agree();

    expect($puttingBack->agreed())->toBe([])
        ->and($screen->answer()->went->cameBack())->toBeTrue()
        ->and($history->askings())->toBe(2);
});

it('lets go of a session refused while putting back or asking after', function (bool $whileAsking): void {
    $keychain = AKeychainInMemory::working();
    $puttingBack = $whileAsking
        ? AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)))
        : AStackThatPutsRunsBack::met(Obstacle::of(KindOfObstacle::CredentialWasRefused));
    $screen = thePuttingARunBackScreen(AStackThatKeepsARecord::with(aRecordHoldingTheRun()), $puttingBack, $keychain);
    $screen->answer();
    $screen->agree();

    if ($whileAsking) {
        $screen->whileItRuns();
    }

    expect($screen->done()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackARunGoesBackOn()->id()))->toBeFalse();
})->with(['putting back' => [false], 'asking after' => [true]]);

it('asks for a session where it is gone by the time the yes is sent, or asked after', function (bool $afterTheYes): void {
    $keychain = AKeychainInMemory::working();
    $puttingBack = AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning());
    $screen = thePuttingARunBackScreen(AStackThatKeepsARecord::with(aRecordHoldingTheRun()), $puttingBack, $keychain);
    $screen->answer();

    if ($afterTheYes) {
        $screen->agree();
    }

    $keychain->forget(theStackARunGoesBackOn()->id());

    if (! $afterTheYes) {
        $screen->agree();
    }

    $screen->whileItRuns();

    expect(everythingPuttingTheRunBackShows($screen->done()))->toBe(nothingReportedOfTheRun(['cameBack' => false, 'isSignedIn' => false]));
})->with(['before the yes' => [false], 'after it' => [true]]);

it('has nothing to report for a yes nobody gave', function (): void {
    $puttingBack = AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning());
    $screen = thePuttingARunBackScreen(AStackThatKeepsARecord::with(aRecordHoldingTheRun()), $puttingBack);

    expect(everythingPuttingTheRunBackShows($screen->done()))->toBe(nothingReportedOfTheRun(['hasEnded' => true]))
        ->and($puttingBack->followed())->toBe([]);
});

it('refuses a route parameter that is not text as naming a stack', function (): void {
    $screen = thePuttingARunBackScreen(AStackThatKeepsARecord::with(aRecordHoldingTheRun()), AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning()));
    $screen->setParams(['stack' => 42, 'service' => THE_RUN_PUT_BACK]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('renders its own view, and the way back to the machine is a route', function (): void {
    $screen = thePuttingARunBackScreen(AStackThatKeepsARecord::with(aRecordHoldingTheRun()), AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning()));

    expect($screen->render()->name())->toBe('operator::putting-that-run-back')
        ->and($screen->stampNamed())->toBe(THE_RUN_PUT_BACK)
        ->and(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull();
});
