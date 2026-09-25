<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\AWalkthrough;
use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\HowTheImportLinked;
use Modules\Kernel\Api\HowTheWalkthroughIsGoing;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\TheLinesItSaid;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatComesNext;
use Modules\Kernel\Api\WhatCouldBeWalkedInstead;
use Modules\Kernel\Api\WhatElseItIsCalled;
use Modules\Kernel\Api\WhatTheServicesWereSaying;
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
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatWalksThrough;
use Tests\Support\Fakes\StacksInMemory;
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

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theWalkthroughScreen(
    AStackThatWalksThrough $walking,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
    ?AStackThatExplainsItsWords $explaining = null,
): WatchingOneArrive {
    $stack = theStackAWalkRunsOn();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WatchingOneArrive($walking, $explaining ?? AStackThatExplainsItsWords::with(TheGlossary::of()), $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** A screen that started a walkthrough of `Big Buck Bunny` and has asked after it once. */
function aScreenThatWalked(AStackThatWalksThrough $walking, ?AKeychainInMemory $keychain = null, ?AStackThatExplainsItsWords $explaining = null): WatchingOneArrive
{
    $screen = theWalkthroughScreen($walking, $keychain, explaining: $explaining);
    $screen->looking = 'Big Buck Bunny';
    $screen->walk();
    $screen->whileItRuns();

    return $screen;
}

/** One line carried out of an arm. */
final readonly class WhatTheScreenWasAsked
{
    public function __construct(public string $said) {}
}

/** What the screen was asked to walk, as the stack would be told. */
function whatItWasAskedToWalk(WhatToWalk $asked): string
{
    return $asked->either(
        named: static fn(string $item): WhatTheScreenWasAsked => new WhatTheScreenWasAsked($item),
        likeliest: static fn(): WhatTheScreenWasAsked => new WhatTheScreenWasAsked('(whatever is likely)'),
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
    $screen = theWalkthroughScreen($walking, explaining: AStackThatExplainsItsWords::met(Obstacle::StackDidNotAnswer));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everythingAStateSays($screen->answer()))->toBe([
        'isSignedIn' => true, 'met' => Obstacle::StackDidNotAnswer->said(), 'wasStarted' => true, 'isWorking' => false, 'hasEnded' => false, 'record' => null, 'road' => [],
    ])
        ->and($drawn->offers())->toContain(__('health.ask_again'))
        ->and($walking->walked())->toBe([]);
});

it('walks what was typed, empties the box, and says it is running and how often it looks', function (): void {
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
            __(HowOften::WhileWorkRuns->saidOnTheScreen(), ['count' => 5]),
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
        ->and($finished->followed())->toHaveCount(1)
        ->and($screen->cadence())->toBe(HowOften::WhileWorkRuns);
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

    // The one next step this app has a screen for leads there.
    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('health.walkthrough.where_to_watch'))
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
        WhatCouldBeWalkedInstead::of(),
        WhatComesNext::of(),
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
        WhatCouldBeWalkedInstead::of(),
        WhatComesNext::of(),
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
        WhatCouldBeWalkedInstead::of(),
        WhatComesNext::of(),
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
    $drawn = WhatTheDeviceWouldDraw::by(aScreenThatWalked(
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
    $watched = aScreenThatWalked($walking);
    $returnedTo = theWalkthroughScreen($walking);
    $returnedTo->took = AStackThatWalksThrough::THE_JOB;

    expect(WhatTheDeviceWouldDraw::by($returnedTo)->said())->toBe(WhatTheDeviceWouldDraw::by($watched)->said());
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
    $walking = AStackThatWalksThrough::met(Obstacle::StackDidNotAnswer);
    $screen = theWalkthroughScreen($walking);
    // A handle from an earlier walk is not one this start answered, so it is
    // not kept to be followed.
    $screen->took = 'an-earlier-walk';
    $screen->walk();

    expect(everythingAStateSays($screen->answer()))->toBe([
        'isSignedIn' => true, 'met' => Obstacle::StackDidNotAnswer->said(), 'wasStarted' => true, 'isWorking' => false, 'hasEnded' => false, 'record' => null, 'road' => [],
    ])
        ->and($screen->answer()->went->remedy)->toBe(Obstacle::StackDidNotAnswer->remedy())
        ->and($screen->took)->toBeNull()
        ->and($walking->followed())->toBe([]);
});

it('lets go of a session the stack refused, before a walk, starting or following', function (): void {
    $before = AKeychainInMemory::working();
    $refusedBefore = theWalkthroughScreen(AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning()), $before, explaining: AStackThatExplainsItsWords::met(Obstacle::CredentialWasRefused));

    expect($refusedBefore->answer()->went->isSignedIn)->toBeFalse()
        ->and($before->isHolding(theStackAWalkRunsOn()->id()))->toBeFalse();

    $starting = AKeychainInMemory::working();
    theWalkthroughScreen(AStackThatWalksThrough::met(Obstacle::CredentialWasRefused), $starting)->walk();

    $following = AKeychainInMemory::working();
    $screen = theWalkthroughScreen(AStackThatWalksThrough::met(Obstacle::CredentialWasRefused), $following);
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

    expect(NativeRouter::resolve($screen->goes()->ofItself()->walkthrough()))->toHaveKey('params.stack', theStackAWalkRunsOn()->id()->stored())
        ->and($screen->goes()->ofItself()->walkthrough())->toBe(sprintf('/stacks/%s/walkthrough', theStackAWalkRunsOn()->id()->stored()));
});
