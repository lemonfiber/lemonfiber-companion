<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ASearchThatSettles;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFarItGot;
use Modules\Kernel\Api\HowMuchOfItIsHere;
use Modules\Kernel\Api\HowSureTheTraceIs;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\TheMomentsInItsHistory;
use Modules\Kernel\Api\TheStagesItReached;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheTraceFound;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhereItGotTo;
use Modules\Kernel\Api\WhereTheServicesDisagree;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhereThisGotTo;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatSearches;
use Tests\Support\Fakes\AStackThatTraces;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TracesToFollow;
use Tests\Support\WhatTheDeviceWouldDraw;

// Where one item got to.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine this screen is about. */
function theStackWhoseItemIsFollowed(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A film that arrived, followed with certainty, with nothing else to say. */
function aFilmThatArrived(): WhereItGotTo
{
    return WhereItGotTo::followed('Dune', WhatTheTraceFound::traced(
        HowSureTheTraceIs::Certain,
        HowFarItGot::reached(Stage::Available, TheStagesItReached::of(), ''),
        TheMomentsInItsHistory::of(),
        WhereTheServicesDisagree::of(),
        HowMuchOfItIsHere::aWholeItem(),
    ));
}

/** The screen, following what the route names. Named for this file (`G10`). */
function theTraceScreen(AStackThatTraces $tracing, ?AKeychainInMemory $keychain = null, bool $signedIn = true, string $named = 'Severance', ?AStackThatSearches $searching = null, ?AStackThatExplainsItsWords $explaining = null): WhereThisGotTo
{
    $stack = theStackWhoseItemIsFollowed();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhereThisGotTo($tracing, $searching ?? AStackThatSearches::answering(WhatBecameOfTheWork::ended()), $explaining ?? AStackThatExplainsItsWords::with(TheGlossary::of()), $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $named]);

    return $screen;
}

it('follows what the route names, and says how sure it is before anything else', function (): void {
    $tracing = AStackThatTraces::with(TracesToFollow::aSeriesStuckDownloading());
    $drawn = WhatTheDeviceWouldDraw::by(theTraceScreen($tracing))->said();

    expect($tracing->followed())->toBe('Severance')
        ->and($drawn)->toContain(__('health.trace.following', ['item' => 'Severance']))
        ->and(array_keys($drawn, __(HowSureTheTraceIs::Uncertain->saidOnTheScreen()), strict: true))->toBe([5])
        ->and(array_keys($drawn, __('health.trace.following', ['item' => 'Severance']), strict: true))->toBe([4])
        ->and(array_keys($drawn, __('health.trace.furthest', ['stage' => 'downloading']), strict: true))->toBe([6]);
});

it('draws the furthest stage as the stack\'s word with the sentence beside it, and why it stopped', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTraceScreen(AStackThatTraces::with(TracesToFollow::aSeriesStuckDownloading())))->said();

    expect($drawn)->toContain(__('health.trace.furthest', ['stage' => 'downloading']))
        ->and($drawn)->toContain(__(Stage::Downloading->saidOnTheScreen()))
        ->and($drawn)->toContain(__('health.trace.stopped', ['why' => 'No peers have been seen for two days']));
});

it('shows a series season by season, with every outstanding episode at its stage', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTraceScreen(AStackThatTraces::with(TracesToFollow::aSeriesStuckDownloading())))->said();

    expect($drawn)->toContain(__('health.trace.series_here', ['have' => 8, 'wanted' => 10]))
        ->and($drawn)->toContain(trans_choice('health.trace.nobody_asked_for', 1))
        ->and($drawn)->toContain(__('health.trace.season', ['season' => 1, 'have' => 8, 'wanted' => 9]))
        ->and($drawn)->toContain(__('health.trace.episode', ['number' => 9, 'title' => 'The We We Are', 'stage' => 'downloading']))
        ->and($drawn)->toContain(__('health.trace.episode', ['number' => 1, 'title' => 'Hello, Ms. Cobel', 'stage' => 'searching']));
});

it('shows the way it came, what was tried in order, and where the services disagree', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTraceScreen(AStackThatTraces::with(TracesToFollow::aSeriesStuckDownloading())))->said();
    $failed = array_keys($drawn, __('health.trace.outcome.download-failed'), strict: true);

    expect($drawn)->toContain(__('health.trace.recorded_untimed', ['service' => 'sonarr']))
        ->and($drawn)->toContain(__('health.trace.recorded_at', ['service' => 'qbittorrent', 'at' => '2026-09-20T10:01:00Z']))
        ->and($failed)->toHaveCount(1)
        ->and(array_slice($drawn, $failed[0] ?? 0, 2))->toBe([__('health.trace.outcome.download-failed'), '2026-09-19T21:00:00Z'])
        ->and($drawn)->toContain('Jellyfin holds an episode no service is watching for');
});

it('says so where nothing was tried, the services agree, and a film has no seasons to count', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTraceScreen(AStackThatTraces::with(aFilmThatArrived()), named: 'Dune'))->said();

    expect($drawn)->toContain(__('health.trace.nothing_tried'))
        ->and($drawn)->toContain(__('health.trace.agree'))
        ->and($drawn)->toContain(__('health.trace.no_stages'))
        ->and($drawn)->toContain(__(HowSureTheTraceIs::Certain->saidOnTheScreen()))
        ->and(implode("\n", $drawn))->not->toContain(__('health.trace.series_here', ['have' => 0, 'wanted' => 0]));
});

it('tells nobody asking for it apart from a trace that could not be read', function (): void {
    $nobody = WhatTheDeviceWouldDraw::by(theTraceScreen(AStackThatTraces::with(WhereItGotTo::nothingAskedFor('Severance'))))->said();
    $unread = theTraceScreen(AStackThatTraces::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)))->answer();

    expect($nobody)->toContain(__('health.trace.nothing_asked_for', ['item' => 'Severance']))
        ->and($unread->went->cameBack())->toBeFalse()
        ->and($unread->went->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($unread->item)->toBe('Severance')
        ->and([$unread->followed, $unread->isUncertain, $unread->sureSaid, $unread->stall, $unread->furthest])->toBe([false, false, '', '', null])
        ->and([$unread->stages, $unread->history, $unread->disagreements])->toBe([[], [], []]);
});

it('a trace the stack is sure of is not drawn as uncertain', function (): void {
    $sure = theTraceScreen(AStackThatTraces::with(TracesToFollow::aSeriesStuckDownloading(HowSureTheTraceIs::Certain)))->answer();
    $unsure = theTraceScreen(AStackThatTraces::with(TracesToFollow::aSeriesStuckDownloading()))->answer();

    expect($sure->isUncertain)->toBeFalse()
        ->and($sure->sureSaid)->toBe(HowSureTheTraceIs::Certain->saidOnTheScreen())
        ->and($unsure->isUncertain)->toBeTrue();
});

it('follows what was typed with the spaces around it left off, and hands the box what is typed, empty at first', function (): void {
    $tracing = AStackThatTraces::with(TracesToFollow::aSeriesStuckDownloading());
    $screen = theTraceScreen($tracing);

    expect($screen->render()->getData()['looking'] ?? null)->toBe('');

    $screen->looking = '  Dune  ';
    expect($screen->render()->getData())->toHaveKey('looking')
        ->and($screen->render()->getData()['looking'])->toBe('  Dune  ');
    $screen->follow();
    $screen->answer();

    expect($tracing->followed())->toBe('Dune');
});

it('follows what was typed instead, once asked to, and ignores a blank', function (): void {
    $tracing = AStackThatTraces::with(aFilmThatArrived());
    $screen = theTraceScreen($tracing);
    $screen->answer();

    $screen->looking = ' ';
    $screen->follow();
    $screen->answer();

    expect($tracing->followed())->toBe('Severance');

    $screen->looking = ' Dune ';
    $screen->follow();
    $screen->answer();

    expect($tracing->followed())->toBe('Dune')
        ->and($screen->looking)->toBe('')
        ->and($tracing->askings())->toBe(2);
});

it('asks nothing where nothing is named, and says what to do', function (): void {
    $tracing = AStackThatTraces::with(aFilmThatArrived());
    $drawn = WhatTheDeviceWouldDraw::by(theTraceScreen($tracing, named: ' '))->said();

    expect($drawn)->toContain(__('health.trace.nothing_named'))
        ->and($tracing->askings())->toBe(0);
});

it('asks once a frame, and again when asked', function (): void {
    $tracing = AStackThatTraces::with(aFilmThatArrived());
    $screen = theTraceScreen($tracing);

    $screen->answer();
    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($tracing->askings())->toBe(2)
        ->and($tracing->wasGivenASession())->toBeTrue()
        ->and($tracing->askedAbout()?->id()->stored())->toBe(theStackWhoseItemIsFollowed()->id()->stored());
});

it('reads the glossary on the frame after the trace, and asks for that frame at once', function (): void {
    $explaining = AStackThatExplainsItsWords::with(TheGlossary::of());
    $screen = theTraceScreen(AStackThatTraces::with(aFilmThatArrived()), explaining: $explaining);

    WhatTheDeviceWouldDraw::by($screen);
    $waited = $screen->waitsForTheNextFrame();
    $asked = $explaining->askings();
    WhatTheDeviceWouldDraw::by($screen);

    expect([$waited, $asked])->toBe([true, 0])
        ->and($screen->waitsForTheNextFrame())->toBeFalse()
        ->and($explaining->askings())->toBe(1);
});

it('a credential refused while the indexers are asked lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theTraceScreen(AStackThatTraces::with(aFilmASearchWouldSettle()), keychain: $keychain, named: 'Dune', searching: AStackThatSearches::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))));
    $screen->search();

    expect($keychain->isHolding(theStackWhoseItemIsFollowed()->id()))->toBeFalse();
});

it('a session that has ended asks nothing, and a refused one is let go', function (): void {
    $tracing = AStackThatTraces::with(aFilmThatArrived());
    $keychain = AKeychainInMemory::working();

    expect(theTraceScreen($tracing, signedIn: false)->answer()->went->isSignedIn)->toBeFalse()
        ->and($tracing->askings())->toBe(0)
        ->and(theTraceScreen(AStackThatTraces::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $keychain)->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseItemIsFollowed()->id()))->toBeFalse();
});

it('refuses a route parameter that is not text, and reads a term that is not text as nothing named', function (): void {
    $screen = theTraceScreen(AStackThatTraces::with(aFilmThatArrived()));
    $screen->setParams(['stack' => 42, 'service' => 'Dune']);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);

    $screen->setParams(['stack' => theStackWhoseItemIsFollowed()->id()->stored(), 'service' => 7]);

    expect($screen->answer()->item)->toBe('');
});

it('is reached by a route carrying the title as it was drawn, and goes back to health', function (): void {
    $screen = theTraceScreen(AStackThatTraces::with(aFilmThatArrived()));

    expect(NativeRouter::resolve($screen->goes()->traceOf(WhatToFollow::called('AC/DC: Live'))))->toHaveKey('params.service', 'AC/DC: Live')
        ->and(NativeRouter::resolve($screen->goes()->to(AStacksScreen::Health)))->not->toBeNull()
        ->and($screen->render()->name())->toBe('operator::where-this-got-to');
});

it('names what was typed without the spaces around it, even where the device holds no session', function (): void {
    $screen = theTraceScreen(AStackThatTraces::with(TracesToFollow::aSeriesStuckDownloading()), signedIn: false);
    $screen->looking = '  Dune  ';
    $screen->follow();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->item)->toBe('Dune');
});

/** A film monitored and never grabbed, which the stack says a search of the indexers would settle. */
function aFilmASearchWouldSettle(): WhereItGotTo
{
    return WhereItGotTo::followed('Dune', WhatTheTraceFound::traced(
        HowSureTheTraceIs::Certain,
        HowFarItGot::reached(Stage::Monitored, TheStagesItReached::of(), 'Monitored, never grabbed'),
        TheMomentsInItsHistory::of(),
        WhereTheServicesDisagree::of(),
        HowMuchOfItIsHere::aWholeItem(),
    )->settledBy(ASearchThatSettles::spending('One search of each indexer\'s daily allowance', 'Everything the curator wants at the quality in force')));
}

it('offers asking the indexers only where the stack says a search would settle the trace, with what it spends and covers first', function (): void {
    $searching = AStackThatSearches::answering(WhatBecameOfTheWork::ended());
    $offered = WhatTheDeviceWouldDraw::by(theTraceScreen(AStackThatTraces::with(aFilmASearchWouldSettle()), named: 'Dune', searching: $searching));
    $notOffered = WhatTheDeviceWouldDraw::by(theTraceScreen(AStackThatTraces::with(aFilmThatArrived()), named: 'Dune'));

    expect($offered->said())->toContain(
        __('health.trace.search_would_settle'),
        __('health.trace.search_spends', ['spends' => 'One search of each indexer\'s daily allowance']),
        __('health.trace.search_covers', ['covers' => 'Everything the curator wants at the quality in force']),
        __('health.trace.search_not_a_rehearsal'),
    )
        ->and($offered->offers())->toContain(__('health.trace.search'))
        ->and($notOffered->offers())->not->toContain(__('health.trace.search'))
        ->and($searching->searched())->toBe([]);
});

it('asks the indexers about what the trace followed, follows the search, and draws the trace it came back with', function (): void {
    $searching = AStackThatSearches::answering(WhatBecameOfTheWork::answered(aFilmThatArrived()));
    $screen = theTraceScreen(AStackThatTraces::with(aFilmASearchWouldSettle()), named: 'Dune', searching: $searching);
    $screen->search();

    expect($searching->searched())->toBe(['Dune'])
        ->and($screen->awaitsAnOutcome())->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.trace.searching'));

    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->awaitsAnOutcome())->toBeFalse()
        ->and(array_map(static fn(Job $job): string => $job->shown(), $searching->followed()))->toBe([AStackThatSearches::THE_SEARCH])
        ->and($drawn->said())->toContain(__('health.trace.searched'), __('health.trace.furthest', ['stage' => Stage::Available->value]))
        ->and($drawn->offers())->not->toContain(__('health.trace.search'));
});

it('asks nothing where no search would settle the trace', function (): void {
    $searching = AStackThatSearches::answering(WhatBecameOfTheWork::ended());
    theTraceScreen(AStackThatTraces::with(aFilmThatArrived()), named: 'Dune', searching: $searching)->search();

    expect($searching->searched())->toBe([]);
});

it('reads the trace with the season it is narrowed to, and asks about that season', function (): void {
    $tracing = AStackThatTraces::with(aFilmASearchWouldSettle());
    $searching = AStackThatSearches::answering(WhatBecameOfTheWork::ended());
    $screen = theTraceScreen($tracing, searching: $searching);
    $screen->followSeason(2);
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->search();

    expect($tracing->followed())->toBe('Severance, season 2')
        ->and($drawn->said())->toContain(__('health.trace.in_season', ['season' => 2]))
        ->and($searching->searched())->toBe(['Severance, season 2']);

    $screen->followEverySeason();
    WhatTheDeviceWouldDraw::by($screen);

    expect($tracing->followed())->toBe('Severance');
});

it('offers following each season of a series on its own, and not the one it is narrowed to', function (): void {
    $screen = theTraceScreen(AStackThatTraces::with(TracesToFollow::aSeriesStuckDownloading()));

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('health.trace.follow_season', ['season' => 1]), __('health.trace.follow_season', ['season' => 2]));

    $screen->followSeason(1);

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('health.trace.follow_season', ['season' => 1]))
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('health.trace.follow_season', ['season' => 2]), __('health.trace.every_season'));
});

it('says the stack did not ask the indexers, in its own words', function (): void {
    $refusal = ARefusalInItsWords::said('Every indexer has spent its daily allowance', '', WhatTheRefusalNamed::nothing(), 'Try again tomorrow');
    $screen = theTraceScreen(AStackThatTraces::with(aFilmASearchWouldSettle()), named: 'Dune', searching: AStackThatSearches::sayingAtOnce(WhatBecameOfTheWork::refused($refusal)));
    $screen->search();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('health.trace.search_refused'), 'Every indexer has spent its daily allowance');
});

it('sends a search once, however often it is tapped while the indexers are asked', function (): void {
    $searching = AStackThatSearches::answering(WhatBecameOfTheWork::ended());
    $screen = theTraceScreen(AStackThatTraces::with(aFilmASearchWouldSettle()), named: 'Dune', searching: $searching);
    $screen->search();
    $screen->search();

    expect($searching->searched())->toBe(['Dune']);
});

it('says the stack has no answer for a search it no longer knows, and what stood in the way of one', function (): void {
    $ended = theTraceScreen(AStackThatTraces::with(aFilmASearchWouldSettle()), named: 'Dune', searching: AStackThatSearches::answering(WhatBecameOfTheWork::ended()));
    $ended->search();
    $ended->whileItRuns();
    $met = theTraceScreen(AStackThatTraces::with(aFilmASearchWouldSettle()), named: 'Dune', searching: AStackThatSearches::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    $met->search();

    expect(WhatTheDeviceWouldDraw::by($ended)->said())->toContain(__('health.trace.search_no_outcome'))
        ->and($met->howTheSearchIsGoing()->stands->went->cameBack())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($met)->offers())->not->toContain(__('health.trace.search'));
});

it('asks the indexers nothing once the session has ended', function (): void {
    $keychain = AKeychainInMemory::working();
    $searching = AStackThatSearches::answering(WhatBecameOfTheWork::ended());
    $screen = theTraceScreen(AStackThatTraces::with(aFilmASearchWouldSettle()), keychain: $keychain, named: 'Dune', searching: $searching);
    $screen->answer();
    $keychain->forget(theStackWhoseItemIsFollowed()->id());
    $screen->search();

    expect($searching->searched())->toBe([])
        ->and($screen->howTheSearchIsGoing()->stands->went->isSignedIn)->toBeFalse();
});

it('follows season nought, where a series keeps what falls outside its seasons', function (): void {
    $tracing = AStackThatTraces::with(aFilmThatArrived());
    $screen = theTraceScreen($tracing);
    $screen->followSeason(0);
    WhatTheDeviceWouldDraw::by($screen);

    expect($screen->season)->toBe(0)
        ->and($tracing->followed())->toBe('Severance, season 0');
});

it('follows no season below none', function (): void {
    $tracing = AStackThatTraces::with(aFilmThatArrived());
    $screen = theTraceScreen($tracing, named: 'Dune');
    $screen->followSeason(-1);
    WhatTheDeviceWouldDraw::by($screen);

    expect($screen->season)->toBeNull()
        ->and($tracing->followed())->toBe('Dune');
});
